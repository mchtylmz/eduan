<?php

return [
    'settings' => 'Cloudflare Turnstile',
    'feature' => 'Vérification de sécurité des formulaires',
    'settings_help' => 'Choisissez le mode Managed dans Cloudflare. La vérification protège les visiteurs pour la connexion, l’inscription, la récupération et la création du mot de passe ainsi que le contact. La newsletter et les actions après connexion sont exclues.',
    'site_key' => 'Clé du site',
    'secret_key' => 'Clé secrète',
    'secret_help' => 'La clé enregistrée n’est jamais affichée. Laissez vide pour la conserver. Elle est stockée chiffrée.',
    'host_help' => 'Autorisez ce nom d’hôte dans Cloudflare : :host. Pour les tests locaux, utilisez le même nom d’hôte que APP_URL.',
    'invalid_settings' => 'Paramètres de sécurité invalides.',
    'keys_required' => 'Saisissez les deux clés avant d’activer la vérification de sécurité.',
    'failed' => 'La vérification de sécurité a échoué. Veuillez vérifier à nouveau puis envoyer le formulaire.',
    'unavailable' => 'La vérification de sécurité n’a pas pu charger. Vérifiez votre connexion et réessayez.',
    'retry' => 'Réessayer',
    'newsletter_limit' => 'Vous pouvez vous inscrire une fois par minute. Patientez puis réessayez.',
];
