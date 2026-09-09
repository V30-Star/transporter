<?php

namespace App\Http\Middleware;

use App\Models\LogUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureSingleSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            $logId = session('login_log_id');
            $sessionToken = session('session_device_token');
            $cookieToken = $request->cookie('app_session_device_token');
            $activeToken = $user ? \Illuminate\Support\Facades\Cache::get("user_active_device_token:{$user->fsysuserid}") : null;

            $isInvalid = false;

            // 1. Verify cookie & session device token if sessionToken is set
            if ($sessionToken) {
                // If cookie is missing or mismatch with session token or active cache token
                if (! $cookieToken || $cookieToken !== $sessionToken) {
                    $isInvalid = true;
                } elseif ($activeToken && $activeToken !== $cookieToken) {
                    $isInvalid = true;
                }
            }

            // 2. Verify database log record status
            if (! $isInvalid && $logId) {
                $currentLog = LogUser::find($logId);
                if (! $currentLog || $currentLog->log_out_date !== null) {
                    $isInvalid = true;
                }
            }

            if ($isInvalid) {
                cookie()->queue(cookie()->forget('app_session_device_token'));
                Auth::guard('sysuser')->logout();
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Sesi Anda telah berakhir karena akun ini telah login di perangkat lain atau verifikasi cookie tidak valid.',
                    ], 401);
                }

                return redirect()->route('login')->with('status', 'Sesi Anda telah berakhir karena akun ini telah login di perangkat lain.');
            }
        }

        return $next($request);
    }
}
