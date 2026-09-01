<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\LogUser;
use App\Models\RoleAccess;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Pastikan RoleAccess model diimport
use Illuminate\View\View; // Pastikan RoleAccess model diimport

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
     * Generate 4-character captcha image (large and clear SVG).
     */
    public function captcha()
    {
        $chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $code = '';
        for ($i = 0; $i < 4; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }

        session(['captcha_code' => strtolower($code)]);

        $width = 150;
        $height = 50;

        $colors = ['#1e40af', '#4338ca', '#047857', '#b45309', '#be123c', '#6d28d9'];
        shuffle($colors);

        $svgLines = '';
        // Subtle background grid / decorative lines
        for ($i = 0; $i < 4; $i++) {
            $x1 = random_int(5, 30);
            $y1 = random_int(5, 45);
            $x2 = random_int(120, 145);
            $y2 = random_int(5, 45);
            $stroke = $colors[$i % count($colors)];
            $svgLines .= "<line x1='{$x1}' y1='{$y1}' x2='{$x2}' y2='{$y2}' stroke='{$stroke}' stroke-width='1.5' stroke-opacity='0.25' stroke-dasharray='4 3' />";
        }

        // Noise dots
        $svgDots = '';
        for ($i = 0; $i < 25; $i++) {
            $cx = random_int(5, 145);
            $cy = random_int(5, 45);
            $r = random_int(1, 2);
            $svgDots .= "<circle cx='{$cx}' cy='{$cy}' r='{$r}' fill='#94a3b8' opacity='0.35' />";
        }

        // 4 large, clear characters with slight random angle
        $svgText = '';
        $positions = [24, 58, 92, 126];
        for ($i = 0; $i < 4; $i++) {
            $char = $code[$i];
            $color = $colors[$i % count($colors)];
            $angle = random_int(-10, 10);
            $x = $positions[$i];
            $y = 35 + random_int(-2, 2);
            $svgText .= "<text x='{$x}' y='{$y}' fill='{$color}' font-size='28' font-weight='800' font-family='ui-monospace, Consolas, Monaco, monospace' text-anchor='middle' transform='rotate({$angle}, {$x}, {$y})'>{$char}</text>";
        }

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}">
    <rect width="100%" height="100%" fill="#f8fafc" rx="8" />
    <rect width="100%" height="100%" fill="none" stroke="#e2e8f0" stroke-width="1" rx="8" />
    {$svgLines}
    {$svgDots}
    {$svgText}
</svg>
SVG;

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    // In the AuthenticatedSessionController, after login
    public function store(LoginRequest $request)
    {
        $request->authenticate();

        $request->session()->regenerate();
        $user = Auth::user();
        if ($user && method_exists($user, 'touch')) {
            $user->touch();
        }

        $restrictedPermissions = RoleAccess::where('fusercreate', $user->fuid)
            ->pluck('fpermission')
            ->implode(',');

        session(['user_restricted_permissions' => $restrictedPermissions]);

        session([
            'fsysuserid' => $user->fsysuserid,
            'fname' => $user->fname,
            'fuserlevel' => $user->fuserlevel,
            'fcabang' => $user->fcabang,
        ]);

        LogUser::create([
            'ip' => $request->ip(),
            'akun' => $user->fsysuserid,
            'komp' => gethostname(),
            'login_date' => now(),
            'log_out_date' => null,
        ]);

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $user = auth('sysuser')->user();

        LogUser::where('akun', $user->fsysuserid)
            ->whereNull('log_out_date')
            ->latest('login_date')
            ->first()
            ?->update(['log_out_date' => now()]);

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
