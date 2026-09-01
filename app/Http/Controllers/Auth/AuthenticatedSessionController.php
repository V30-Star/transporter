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
     * Generate 4-character captcha image.
     */
    public function captcha()
    {
        $chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $code = '';
        for ($i = 0; $i < 4; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }

        session(['captcha_code' => strtolower($code)]);

        $width = 110;
        $height = 38;
        $image = imagecreatetruecolor($width, $height);

        // Background color (light gray #f3f4f6)
        $bgColor = imagecolorallocate($image, 243, 244, 246);
        imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);

        // Noise lines
        for ($i = 0; $i < 4; $i++) {
            $lineColor = imagecolorallocate($image, random_int(180, 215), random_int(180, 215), random_int(180, 215));
            imageline($image, random_int(0, $width), random_int(0, $height), random_int(0, $width), random_int(0, $height), $lineColor);
        }

        // Noise dots
        for ($i = 0; $i < 40; $i++) {
            $dotColor = imagecolorallocate($image, random_int(160, 200), random_int(160, 200), random_int(160, 200));
            imagesetpixel($image, random_int(0, $width), random_int(0, $height), $dotColor);
        }

        // Text colors
        $textColors = [
            imagecolorallocate($image, 37, 99, 235),  // blue
            imagecolorallocate($image, 79, 70, 229),  // indigo
            imagecolorallocate($image, 13, 148, 136), // teal
            imagecolorallocate($image, 217, 119, 6),  // amber
            imagecolorallocate($image, 220, 38, 38),  // red
        ];

        for ($i = 0; $i < 4; $i++) {
            $char = $code[$i];
            $color = $textColors[array_rand($textColors)];
            $x = 16 + ($i * 22);
            $y = random_int(9, 13);
            imagestring($image, 5, $x, $y, $char, $color);
        }

        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();
        imagedestroy($image);

        return response($imageData, 200, [
            'Content-Type' => 'image/png',
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
