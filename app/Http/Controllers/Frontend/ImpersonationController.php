<?php

namespace App\Http\Controllers\Frontend;

use App\Actions\Auth\ImpersonateUser;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class ImpersonationController extends Controller
{
    public function store(User $user)
    {
        ImpersonateUser::run($user);

        return redirect()->route('frontend.home')->header('Cache-Control', 'private, no-store');
    }

    public function destroy(Request $request)
    {
        $values = $request->validate(['return_token' => 'required|string|size:64']);
        app(ImpersonateUser::class)->restore($values['return_token']);

        return redirect()->route('admin.users.index')->header('Cache-Control', 'private, no-store');
    }
}
