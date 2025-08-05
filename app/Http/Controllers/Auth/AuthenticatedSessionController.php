<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use App\Providers\RouteServiceProvider;
use App\Models\RoleAccess; // Pastikan RoleAccess model diimport

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    // In the AuthenticatedSessionController, after login
    public function store(LoginRequest $request)
    {
        $credentials = [
            'fsysuserid' => $request->fsysuserid,
            'password' => $request->password
        ];

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            $user = Auth::user();

            $restrictedPermissions = RoleAccess::where('fuserid', $user->fuid)
                ->pluck('fpermission')
                ->implode(',');

            session(['user_restricted_permissions' => $restrictedPermissions]);

            session([
                'fsysuserid' => $user->fsysuserid,
                'fname' => $user->fname,
                'fuserlevel' => $user->fuserlevel,
                'fcabang' => $user->fcabang,
            ]);

            return redirect()->intended(RouteServiceProvider::HOME);
        }

        return back()->withErrors([
            'fsysuserid' => __('auth.failed'),
        ]);
    }
    
    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
