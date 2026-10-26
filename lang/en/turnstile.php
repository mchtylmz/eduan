<?php

return [
    'settings' => 'Cloudflare Turnstile',
    'feature' => 'Form security check',
    'settings_help' => 'Select Managed mode in Cloudflare. The check protects guests on login, registration, password recovery, new password and contact forms. Newsletter and signed-in actions are excluded.',
    'site_key' => 'Site key',
    'secret_key' => 'Secret key',
    'secret_help' => 'The saved key is never displayed. Leave blank to keep it. The key is stored encrypted.',
    'host_help' => 'Allow this hostname in Cloudflare: :host. For local tests, use the same hostname as APP_URL.',
    'invalid_settings' => 'Invalid security settings.',
    'keys_required' => 'Enter both keys before enabling the security check.',
    'failed' => 'The security check could not be completed. Please verify again and submit.',
    'unavailable' => 'The security check could not load. Check your connection and retry.',
    'retry' => 'Try again',
    'newsletter_limit' => 'You can subscribe once per minute. Please wait and try again.',
];
