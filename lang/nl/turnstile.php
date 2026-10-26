<?php

return [
    'settings' => 'Cloudflare Turnstile',
    'feature' => 'Beveiligingscontrole voor formulieren',
    'settings_help' => 'Kies de modus Managed in Cloudflare. De controle beschermt bezoekers bij aanmelden, registreren, wachtwoordherstel, een nieuw wachtwoord en contact. Nieuwsbrief en acties na aanmelden zijn uitgesloten.',
    'site_key' => 'Sitesleutel',
    'secret_key' => 'Geheime sleutel',
    'secret_help' => 'De opgeslagen sleutel wordt nooit getoond. Laat dit leeg om die te behouden. De sleutel wordt versleuteld opgeslagen.',
    'host_help' => 'Sta deze hostnaam toe in Cloudflare: :host. Gebruik voor lokale tests dezelfde hostnaam als APP_URL.',
    'invalid_settings' => 'Ongeldige beveiligingsinstellingen.',
    'keys_required' => 'Vul beide sleutels in voordat je de beveiligingscontrole inschakelt.',
    'failed' => 'De beveiligingscontrole is niet voltooid. Verifieer opnieuw en verstuur het formulier.',
    'unavailable' => 'De beveiligingscontrole kon niet laden. Controleer je verbinding en probeer opnieuw.',
    'retry' => 'Opnieuw proberen',
    'newsletter_limit' => 'Je kunt je eenmaal per minuut inschrijven. Wacht even en probeer opnieuw.',
];
