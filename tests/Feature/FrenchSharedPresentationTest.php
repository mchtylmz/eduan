<?php

namespace Tests\Feature;

use App\Helpers\TranslationHelper;
use App\Livewire\Frontend\Leagues\LeagueTable;
use App\Models\League;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class FrenchSharedPresentationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_account_and_table_groups_cover_existing_source_keys_in_french(): void
    {
        foreach (['account', 'tables', 'menus'] as $group) {
            $source = Arr::dot(require lang_path('tr/'.$group.'.php'));
            $french = Arr::dot(require lang_path('fr/'.$group.'.php'));
            $this->assertEqualsCanonicalizing(array_keys($source), array_keys($french));
            foreach ($french as $key => $value) {
                $this->assertNotSame('', trim($value));
                $this->assertNotSame($source[$key], $value, $group.'.'.$key);
            }
        }
        $this->assertSame('Ligue 2', __('account.league_number', ['number' => 2], 'fr'));
    }

    public function test_french_league_header_navigation_and_account_return_do_not_use_turkish_fallback(): void
    {
        app()->setLocale('fr');
        $league = League::create(['code' => 'fr-shared-'.fake()->uuid(), 'start_at' => '2026-10-24', 'end_at' => '2026-10-30']);
        Livewire::test(LeagueTable::class, ['selectedLeague' => $league])
            ->assertSee('Résultats de ligue')->assertSee('Résultats des ligues précédentes')->assertSee('Ligue 1')
            ->assertDontSee('Lig Sonuçları')->assertDontSee('Geçmiş Lig Sonuçları');
        $this->assertSame('Mes informations personnelles', __('account.profile'));
        $this->assertSame('Filtrer', __('Filtrele'));
        $this->assertSame('Voir', __('Görüntüle'));
    }

    public function test_french_panel_uses_french_bootstrap_locale(): void
    {
        $actor = User::factory()->create();
        foreach (['dashboard:access', 'leagues:view'] as $name) {
            $actor->givePermissionTo(Permission::findOrCreate($name, 'web'));
        }
        $this->actingAs($actor)->withSession(['locale' => 'fr']);
        app()->setLocale('fr');
        $this->blade('<x-admin.server-table id="fr-table" url="/test" />')
            ->assertSee('data-locale="fr-FR"', false);
        $this->assertFileExists(public_path('backend/assets/js/plugins/bootstrap-table/dist/locale/bootstrap-table-fr-FR.min.js'));
    }

    public function test_translation_build_preserves_french_defaults_and_filled_custom_values(): void
    {
        $old = lang_path();
        $root = sys_get_temp_dir().'/eduan-fr-shared-'.bin2hex(random_bytes(8));
        File::ensureDirectoryExists($root.'/fr');
        File::copy($old.'/fr.json', $root.'/fr.json');
        File::copy($old.'/fr/account.php', $root.'/fr/account.php');
        try {
            app()->useLangPath($root);
            TranslationHelper::build('fr', ['Lig Sonuçları' => '', 'Görüntüle' => 'Vue personnalisée']);
            $labels = json_decode(File::get($root.'/fr.json'), true, flags: JSON_THROW_ON_ERROR);
            $this->assertSame('Résultats de ligue', $labels['Lig Sonuçları']);
            $this->assertSame('Vue personnalisée', $labels['Görüntüle']);
        } finally {
            app()->useLangPath($old);
            app('translator')->setLoaded([]);
            File::deleteDirectory($root);
        }
    }
}
