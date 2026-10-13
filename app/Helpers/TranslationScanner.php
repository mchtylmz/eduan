<?php

namespace App\Helpers;

use App\Models\Language;
use App\Models\Translation;
use App\Models\TranslationSource;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class TranslationScanner
{
    /** Static strings only: never evaluate a translation argument. */
    public function calls(string $source, bool $blade = false): array
    {
        $calls = [];
        if ($blade) {
            // Keep line positions, including comments containing translation examples.
            $source = preg_replace_callback('/\{\{--.*?--\}\}|<!--.*?-->/s', fn ($m) => preg_replace('/[^\n]/', ' ', $m[0]), $source);
            $compiled = $this->calls(app('blade.compiler')->compileString($source));
            $available = [];
            foreach ($compiled as $item) {
                $signature = json_encode([$item['key'], str_contains($item['call'], 'choice') ? 'choice' : 'get']);
                $available[$signature] = ($available[$signature] ?? 0) + 1;
            }
            preg_match_all('/(?<![\w>$:])(?:__|trans|trans_choice)\s*\(|\b(?:[\\\\\w]*Lang)::(?:get|choice)\s*\(|@(lang|choice)\s*\(/', $source, $matches, PREG_OFFSET_CAPTURE);
            foreach ($matches[0] as [$call, $offset]) {
                $argument = substr($source, $offset + strlen($call));
                $tokens = token_get_all('<?php '.$argument);
                $item = $this->argument($tokens, 1, trim($call, " \n\t("), substr_count(substr($source, 0, $offset), "\n") + 1);
                $signature = json_encode([$item['key'], str_contains($item['call'], 'choice') ? 'choice' : 'get']);
                if (($available[$signature] ?? 0) > 0) {
                    $calls[] = $item;
                    $available[$signature]--;
                }
            }

            return $calls;
        }

        $tokens = token_get_all($source);
        foreach ($tokens as $index => $token) {
            if (! is_array($token) || ! in_array($token[0], [T_STRING, T_NAME_FULLY_QUALIFIED], true)) {
                continue;
            }
            $name = ltrim($token[1], '\\');
            $previous = $this->significant($tokens, $index - 1, -1);
            $next = $this->significant($tokens, $index + 1);
            $global = in_array($name, ['__', 'trans', 'trans_choice'], true)
                && ! in_array($tokens[$previous][0] ?? null, [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION], true);
            $facade = in_array($name, ['get', 'choice'], true)
                && ($tokens[$previous][0] ?? null) === T_DOUBLE_COLON;
            $translator = false;
            if (in_array($name, ['get', 'choice'], true) && ($tokens[$previous][0] ?? null) === T_OBJECT_OPERATOR) {
                $prefix = implode('', array_map(fn ($part) => is_array($part) ? $part[1] : $part, array_slice($tokens, max(0, $index - 12), min(12, $index))));
                $translator = (bool) preg_match('/app\(\s*[\'"]translator[\'"]\s*\)\s*->\s*$/', $prefix);
            }
            if ($facade) {
                $class = $this->significant($tokens, $previous - 1, -1);
                $facade = in_array(ltrim($tokens[$class][1] ?? '', '\\'), ['Lang', 'Illuminate\\Support\\Facades\\Lang'], true);
            }
            if (($global || $facade || $translator) && ($tokens[$next] ?? null) === '(') {
                $calls[] = $this->argument($tokens, $next + 1, $facade ? 'Lang::'.$name : $name, $token[2]);
            }
        }

        return $calls;
    }

    private function significant(array $tokens, int $index, int $direction = 1): int
    {
        while (isset($tokens[$index]) && is_array($tokens[$index]) && in_array($tokens[$index][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT, T_OPEN_TAG], true)) {
            $index += $direction;
        }

        return $index;
    }

    private function argument(array $tokens, int $index, string $call, int $line): array
    {
        $index = $this->significant($tokens, $index);
        $token = $tokens[$index] ?? null;
        $next = $this->significant($tokens, $index + 1);
        $key = is_array($token) && $token[0] === T_CONSTANT_ENCAPSED_STRING && in_array($tokens[$next] ?? '', [',', ')'], true)
            ? $this->literal($token[1]) : null;

        // translations.key is varchar(255); do not silently truncate or invent a dynamic key.
        return ['key' => $key !== null && mb_strlen($key) <= 255 && $key !== '' ? $key : null, 'call' => $call, 'line' => $line];
    }

    private function literal(string $token): string
    {
        $body = substr($token, 1, -1);
        if ($token[0] === "'") {
            return preg_replace('/\\\\([\\\\\'])/', '$1', $body);
        }

        return preg_replace_callback('/\\\\(u\{[0-9a-fA-F]+\}|x[0-9a-fA-F]{1,2}|[0-7]{1,3}|[nrtvef$"\\\\])/', function ($m) {
            $value = $m[1];
            if (str_starts_with($value, 'u{')) {
                return mb_chr(hexdec(substr($value, 2, -1)), 'UTF-8');
            }
            if (str_starts_with($value, 'x')) {
                return chr(hexdec(substr($value, 1)));
            }
            if (ctype_digit($value)) {
                return chr(octdec($value) % 256);
            }

            return ['n' => "\n", 'r' => "\r", 't' => "\t", 'v' => "\v", 'e' => "\e", 'f' => "\f"][$value] ?? $value;
        }, $body);
    }

    public function preview(): array
    {
        $files = [];
        foreach (['app', 'routes', 'config', 'resources/views'] as $directory) {
            foreach (File::allFiles(base_path($directory)) as $file) {
                $path = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname());
                if ($file->getExtension() === 'php' && ! str_starts_with($path, 'resources/views/vendor/')) {
                    $files[$path] = File::get($file->getPathname());
                }
            }
        }
        $routes = $this->routeMap($files);
        $groups = $this->groups();
        $sources = [];
        foreach ($files as $path => $source) {
            foreach ($this->calls($source, str_ends_with($path, '.blade.php')) as $call) {
                $key = $call['key'];
                $group = $groups[$key] ?? null;
                $associated = $routes['files'][$path] ?? [];
                foreach ($routes['controllers'][$path] ?? [] as $method) {
                    if ($call['line'] >= $method['start'] && $call['line'] <= $method['end']) {
                        $associated[] = $method['route'];
                    }
                }
                $sources[] = $this->source($path, $call, $associated, $group ? 'php' : ($key === null ? 'dynamic' : 'json'), $group['group'] ?? null, $group['original'] ?? $key);
            }
        }
        // Include declared groups (e.g. validation keys used dynamically by the framework).
        foreach ($groups as $key => $group) {
            $sources[] = $this->source($group['file'], ['key' => $key, 'call' => 'definition', 'line' => 1], [], 'php', $group['group'], $group['original']);
        }
        $keys = collect($sources)->whereNotNull('key')->unique('key')->count();

        return ['sources' => $sources, 'keys' => $keys, 'files' => count($files), 'dynamic' => collect($sources)->whereNull('key')->count()];
    }

    private function source(string $file, array $call, array $routes, string $kind, ?string $group, ?string $original): array
    {
        return [
            'identity' => hash('sha256', $file.'|'.$call['line'].'|'.$call['call'].'|'.$call['key']),
            ...$call, 'kind' => $kind, 'group' => $group, 'original' => $original, 'file' => $file,
            'surface' => str_contains(strtolower($file), '/frontend/') ? 'frontend' : (str_contains(strtolower($file), '/backend/') || str_starts_with($file, 'app/Livewire/') && ! str_contains($file, '/Frontend/') ? 'backend' : 'shared'),
            'routes' => array_values(array_unique($routes)),
        ];
    }

    public function groups(): array
    {
        $groups = [];
        // Turkish is the source; other locales only supply keys missing from the source files.
        foreach (array_unique(['tr', ...array_map('basename', File::directories(lang_path()))]) as $locale) {
            if ($locale === 'vendor' || ! is_dir(lang_path($locale))) {
                continue;
            }
            foreach (File::files(lang_path($locale)) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }
                $group = $file->getFilenameWithoutExtension();
                foreach (Arr::dot(require $file->getPathname()) as $key => $value) {
                    if (is_string($value)) {
                        $groups[$group.'.'.$key] ??= ['original' => $value, 'group' => $group, 'file' => 'lang/'.$locale.'/'.$file->getFilename()];
                    }
                }
            }
        }

        return $groups;
    }

    /** Only literal view/include/component edges and registered controller methods are mapped. */
    private function routeMap(array $files): array
    {
        $views = [];
        $components = [];
        foreach ($files as $path => $source) {
            if (str_starts_with($path, 'resources/views/')) {
                $views[str_replace('/', '.', substr($path, 16, -10))] = $path;
            }
            if (str_starts_with($path, 'app/Livewire/')) {
                $components[implode('.', array_map(fn ($part) => Str::kebab($part), explode('/', substr($path, 13, -4))))] = $path;
            }
        }
        $edges = [];
        foreach ($files as $path => $source) {
            foreach ($this->viewNames($source) as $name) {
                if (isset($views[$name])) {
                    $edges[$path][] = $views[$name];
                }
            }
            preg_match_all('/<livewire:([\w.-]+)|@livewire\(\s*[\'"]([\w.-]+)[\'"]/', $source, $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                if (isset($components[$match[1] ?: ($match[2] ?? '')])) {
                    $edges[$path][] = $components[$match[1] ?: $match[2]];
                }
            }
        }
        $map = [];
        $controllers = [];
        foreach (Route::getRoutes() as $route) {
            $action = $route->getActionName();
            if (! str_contains($action, '@')) {
                continue;
            }
            [$class, $method] = explode('@', $action, 2);
            if (! str_starts_with($class, 'App\\') || ! method_exists($class, $method)) {
                continue;
            }
            $reflection = new \ReflectionMethod($class, $method);
            $path = str_replace(base_path().DIRECTORY_SEPARATOR, '', $reflection->getFileName());
            $code = implode('', array_slice(file($reflection->getFileName()), $reflection->getStartLine() - 1, $reflection->getEndLine() - $reflection->getStartLine() + 1));
            $label = $route->getName() ?: $route->uri();
            $controllers[$path][] = ['start' => $reflection->getStartLine(), 'end' => $reflection->getEndLine(), 'route' => $label];
            $queue = array_values(array_intersect_key($views, array_flip($this->viewNames($code))));
            $visited = [];
            while ($queue) {
                $current = array_pop($queue);
                if (isset($visited[$current])) {
                    continue;
                }
                $visited[$current] = true;
                $map[$current][] = $label;
                array_push($queue, ...($edges[$current] ?? []));
            }
        }

        return ['files' => $map, 'controllers' => $controllers];
    }

    private function viewNames(string $source): array
    {
        preg_match_all('/(?:\bview|@(include|includeIf|extends))\s*\(\s*[\'"]([\w.-]+)[\'"]/', $source, $matches);

        return $matches[2];
    }

    /** Existing translation values and language activation are never changed by a scan. */
    public function apply(array $preview): array
    {
        return DB::transaction(function () use ($preview) {
            $scan = bin2hex(random_bytes(16));
            $sources = collect($preview['sources'])->unique('identity')->map(fn ($source) => [...$source, 'routes' => json_encode($source['routes']), 'active' => true, 'scan' => $scan]);
            foreach ($sources->chunk(200) as $chunk) {
                TranslationSource::upsert($chunk->all(), ['identity'], ['key', 'kind', 'group', 'original', 'file', 'line', 'surface', 'routes', 'call', 'active', 'scan']);
            }
            // Keep former usages for audit; obsolete usages are excluded from current filters.
            TranslationSource::where('scan', '!=', $scan)->update(['active' => false]);
            $keys = $sources->whereNotNull('key')->unique('key');
            $added = 0;
            foreach (Language::pluck('code') as $locale) {
                abort_unless(preg_match('/^[a-z]{2,3}(?:[_-][A-Za-z]{2,4})?$/D', $locale), 422);
                $json = is_file(lang_path($locale.'.json')) ? json_decode(File::get(lang_path($locale.'.json')), true, 512, JSON_THROW_ON_ERROR) : [];
                $existing = Translation::where('locale', $locale)->pluck('key')->flip();
                $rows = [];
                $labels = [];
                foreach ($keys as $source) {
                    if ($existing->has($source['key'])) {
                        continue;
                    }
                    $value = $json[$source['key']] ?? '';
                    if ($source['kind'] === 'php') {
                        $group = $source['group'];
                        if (! array_key_exists($group, $labels)) {
                            $file = lang_path($locale.'/'.$group.'.php');
                            $labels[$group] = is_file($file) ? require $file : [];
                        }
                        $value = Arr::get($labels[$group], substr($source['key'], strlen($group) + 1), '');
                    }
                    $rows[] = ['locale' => $locale, 'key' => $source['key'], 'value' => $value];
                }
                foreach (array_chunk($rows, 200) as $chunk) {
                    $added += Translation::insertOrIgnore($chunk);
                }
            }

            return ['keys' => $preview['keys'], 'files' => $preview['files'], 'dynamic' => $preview['dynamic'], 'added' => $added];
        });
    }
}
