<?php

namespace App\Http\Controllers\Frontend;

use App\Actions\Users\GetUserFromPasswordTokenAction;
use App\Enums\StatusEnum;
use App\Enums\YesNoEnum;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login()
    {
        return view('frontend.auth.login', [
            'title' => __('Hesabınıza Giriş Yapın')
        ]);
    }

    public function recoverPassword()
    {
        return view('frontend.auth.recover-password', [
            'title' => __('Parolamı Unuttum ?')
        ]);
    }

    public function createPassword(string $token)
    {
        if (!$user = GetUserFromPasswordTokenAction::run(token: $token)) {
            abort(404);
        }

        return view('frontend.auth.create-password', [
            'title' => __('Yeni Parola Oluştur'),
            'user' => $user
        ]);
    }

    public function register()
    {
        if (settings()->registerStatus != StatusEnum::ACTIVE->value) {
            return redirect()->route('frontend.home');
        }

        return view('frontend.auth.register', [
            'title' => __('Kayıt Ol')
        ]);
    }

    public function verify(User $user)
    {
        $user->update([
            'email_verified' => YesNoEnum::YES,
            'email_verified_token' => null,
            'email_verified_at' => now()
        ]);

        return redirect()->route('login')->with([
           'status' => 'success',
           'message' => __('Hesabınız başarıyla onaylandı, giriş yapabilirsiniz!')
        ]);
    }
}
