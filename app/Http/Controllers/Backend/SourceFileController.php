<?php

namespace App\Http\Controllers\Backend;

use App\Actions\Auth\AuthorizePanelForm;
use App\Actions\Sources\SourceUploads;
use App\Helpers\ServerTable;
use App\Helpers\Sources\SourceSettings;
use App\Http\Controllers\Controller;
use App\Models\SourceFile;
use Illuminate\Http\Request;

class SourceFileController extends Controller
{
    public function index()
    {
        AuthorizePanelForm::run('files', 'view');

        return view('backend.sources.index', ['title' => __('sources.title')]);
    }

    public function data(Request $request)
    {
        AuthorizePanelForm::run('files', 'view');
        $sorts = ['title' => 'title', 'size' => 'size', 'created_at' => 'created_at'];
        $p = ServerTable::parameters($request, $sorts, 'created_at', ['type' => ['nullable', 'in:'.implode(',', array_keys(SourceSettings::TYPES))]]);
        if (! $request->has('order')) {
            $p['order'] = 'desc';
        }
        $query = SourceFile::with('uploader:id,name,surname')->when($p['search'], fn ($q, $search) => $q->where(fn ($q) => $q->where('title', 'like', '%'.$search.'%')->orWhere('original_name', 'like', '%'.$search.'%')))
            ->when($p['type'] ?? '', fn ($q, $type) => $q->where('extension', $type));
        $result = ServerTable::page('files', $query, $p, $sorts, fn ($source) => [
            'id' => $source->code, 'title' => $source->title, 'name' => $source->original_name,
            'type' => strtoupper($source->extension), 'size' => $this->formatSize($source->size),
            'uploader' => $source->uploader?->display_name ?? '—', 'created_at' => dateFormat($source->created_at, 'd/m/Y H:i'),
            'status' => __('sources.status_'.$source->status), 'view_url' => $source->status === 'ready' ? $source->viewerUrl() : null,
            'raw_url' => $source->status === 'ready' ? $source->rawUrl() : null,
            'delete_url' => auth()->user()->can('files:delete') ? route('admin.files.delete', $source->code) : null,
        ], cache: false);

        return response()->json($result)->header('Cache-Control', 'private, no-store');
    }

    public function prepare(Request $request, SourceUploads $uploads)
    {
        return response()->json($uploads->prepare($request->all()))->header('Cache-Control', 'private, no-store');
    }

    public function finish(Request $request, string $code, SourceUploads $uploads)
    {
        AuthorizePanelForm::run('files', 'add');
        $request->validate(['file' => 'nullable|file', 'fallback' => 'sometimes|boolean']);
        $source = $uploads->finish(SourceFile::where('code', $code)->firstOrFail(), $request->file('file'), $request->boolean('fallback'));

        return response()->json(['url' => $source->viewerUrl(), 'raw_url' => $source->rawUrl()])->header('Cache-Control', 'private, no-store');
    }

    public function delete(string $code, SourceUploads $uploads)
    {
        AuthorizePanelForm::run('files', 'delete');
        $uploads->delete(SourceFile::where('code', $code)->firstOrFail());

        return response()->json(['message' => __('sources.deleted')]);
    }

    private function formatSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        $kilobytes = $bytes < 1024 * 1024;

        return number_format($bytes / ($kilobytes ? 1024 : 1024 * 1024), 2, '.', '').($kilobytes ? ' KB' : ' MB');
    }
}
