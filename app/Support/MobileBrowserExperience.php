<?php

namespace App\Support;

use Illuminate\Http\Request;

final class MobileBrowserExperience
{
    /**
     * Detect common mobile browser user agents for the mobile-browser product scope.
     * Desktop browsers and automated PHPUnit requests are not treated as mobile.
     */
    public static function isMobileBrowser(?Request $request = null): bool
    {
        $request ??= request();
        $ua = strtolower((string) $request->userAgent());

        if ($ua === '') {
            return false;
        }

        foreach (['iphone', 'ipod', 'android', 'mobile', 'webos', 'blackberry', 'opera mini', 'iemobile'] as $token) {
            if (str_contains($ua, $token)) {
                return true;
            }
        }

        return false;
    }
}
