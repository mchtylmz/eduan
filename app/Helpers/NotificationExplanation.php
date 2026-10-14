<?php

namespace App\Helpers;

class NotificationExplanation
{
    public static function forDisplay(string $content): string
    {
        // Existing authored Pages stay intact; omit the old provider introduction in this screen.
        $content = str_ireplace([
            'Bildirim hizmeti OneSignal ile sağlanır. ',
            'OneSignal levert deze dienst. ',
            'This service uses OneSignal. ',
            'Ce service utilise OneSignal. ',
        ], '', $content);

        return str_ireplace('OneSignal', __('notifications.service_label'), $content);
    }
}
