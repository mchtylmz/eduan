<?php

namespace App\Traits;

use App\Actions\Auth\AuthorizePanelForm;
use App\Actions\Users\AuthorizeUserMail;

trait AuthorizesPanelOverlay
{
    protected function authorizeOverlay(string|array $components, string $kind): void
    {
        $allowed = $kind === 'modal' ? [
            'tests.select-question-modal-form' => 'tests',
            'tests.select-pdf-modal-form' => 'tests',
            'tests.select-content-modal-form' => 'tests',
            'stats.send-email-form' => 'mail',
        ] : [
            'leagues.league-run-form' => 'leagues',
            'exams.question-table' => 'exams', 'exams.favorite-table' => 'exams',
            'exams.results-detail-table' => 'exams', 'tests.results-detail-table' => 'tests',
            'leagues.league-results-table' => 'leagues',
        ];
        $names = is_array($components) ? $components : [$components];
        abort_unless($names !== [], 403);
        foreach ($names as $name) {
            abort_unless(is_string($name) && isset($allowed[$name]), 403);
            if ($allowed[$name] === 'mail') {
                AuthorizeUserMail::run();
            } else {
                AuthorizePanelForm::run($allowed[$name], 'view');
            }
        }
    }
}
