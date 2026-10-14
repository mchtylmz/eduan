<?php

use App\Helpers\FrontendCache;
use App\Helpers\TableCache;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $roles = DB::table('role_has_permissions')->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                ->where('permissions.guard_name', 'web')->whereIn('permissions.name', ['user-type:admin', 'user-type:teacher'])->distinct()->pluck('role_id');
            foreach (['communications:view', 'communications:send'] as $name) {
                DB::table('permissions')->insertOrIgnore(['name' => $name, 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
                $id = DB::table('permissions')->where('name', $name)->where('guard_name', 'web')->value('id');
                foreach ($roles as $role) {
                    DB::table('role_has_permissions')->insertOrIgnore(['role_id' => $role, 'permission_id' => $id]);
                }
            }
            foreach (['notificationsEnabled' => '0', 'oneSignalAppId' => 'ff8879de-3bdc-4aae-b07f-de6522f1be82',
                'oneSignalSafariId' => 'web.onesignal.auto.103c5ae1-79d5-4292-a45e-cec7ddd48c52', 'oneSignalOrigin' => 'https://hypotenuse.be'] as $name => $value) {
                if (! DB::table('settings')->whereNull('settingable_type')->where('name', $name)->exists()) {
                    DB::table('settings')->insert(['name' => $name, 'value' => $value, 'created_at' => now(), 'updated_at' => now()]);
                }
            }
            if (! DB::table('pages')->where('slug', 'notification-permission')->exists()) {
                $titles = ['tr' => 'Bildirimler', 'nl' => 'Meldingen', 'en' => 'Notifications', 'fr' => 'Notifications'];
                $texts = [
                    'tr' => '<p>Bildirimlere izin verirseniz öğretmen veya yöneticinizin hedef ve çalışma kayıtlarınızdaki değişiklikleri ile size gönderilen bilgilendirme mesajlarını alabilirsiniz. Reklam bildirimi gönderilmez.</p><p>Bildirim hizmeti OneSignal ile sağlanır. Bu tarayıcının aboneliği hesabınızla eşleştirilir. İzin vermek isteğe bağlıdır; cihaz aboneliklerinizi bu ekrandan kapatabilirsiniz.</p>',
                    'nl' => '<p>Als u meldingen toestaat, ontvangt u wijzigingen aan uw doelen en studiewerk door uw leerkracht of beheerder en persoonlijke informatieve berichten. Er worden geen reclamemeldingen verstuurd.</p><p>OneSignal levert deze dienst. Het abonnement van deze browser wordt aan uw account gekoppeld. Toestemming is vrijwillig; u kunt uw apparaatabonnementen op deze pagina uitschakelen.</p>',
                    'en' => '<p>If you allow notifications, you can receive changes to your goals and study records made by your teacher or administrator and personal information messages. No advertising notifications are sent.</p><p>This service uses OneSignal. This browser subscription is linked to your account. Permission is optional; you can disable your device subscriptions on this page.</p>',
                    'fr' => '<p>Si vous autorisez les notifications, vous pouvez recevoir les modifications de vos objectifs et de vos activités par votre enseignant ou administrateur ainsi que des messages personnels d’information. Aucune notification publicitaire n’est envoyée.</p><p>Ce service utilise OneSignal. L’abonnement de ce navigateur est associé à votre compte. L’autorisation est facultative ; vous pouvez désactiver vos abonnements sur cette page.</p>',
                ];
                DB::table('pages')->insert(['slug' => 'notification-permission', 'title' => json_encode($titles), 'content' => json_encode($texts),
                    'menu' => 'notifications', 'type' => 'custom', 'status' => 'active', 'sort' => 1, 'created_at' => now(), 'updated_at' => now()]);
            }
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        TableCache::invalidate('roles', 'pages', 'options');
        FrontendCache::invalidate('pages', 'settings');
    }

    public function down(): void
    {
        // Do not remove permission grants, authored content or configured settings.
    }
};
