<?php

namespace App\Http\Middleware;

use App\Models\BrowserProfile;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProfileTokenAuth
{
    /**
     * Verify API token from Authorization header.
     * Attaches the BrowserProfile to the request on success.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json([
                'success' => false,
                'error' => 'unauthorized',
                'message' => 'API token required. Vui lòng đăng nhập từ extension.',
            ], 401);
        }

        $profile = BrowserProfile::findByToken($token);

        if (! $profile) {
            return response()->json([
                'success' => false,
                'error' => 'invalid_token',
                'message' => 'Token không hợp lệ hoặc đã hết hạn.',
            ], 401);
        }

        if ($profile->status === 'banned') {
            return response()->json([
                'success' => false,
                'error' => 'profile_banned',
                'message' => 'Profile đã bị khóa. Liên hệ admin.',
            ], 403);
        }

        // Attach profile to request for downstream use
        $request->merge(['browser_profile' => $profile]);
        $request->setUserResolver(fn () => $profile);

        // Auto-update heartbeat
        $profile->markOnline(
            extensionId: $request->header('X-Extension-Id'),
            userAgent: $request->userAgent()
        );

        return $next($request);
    }
}
