<?php

namespace App\Support;

use Illuminate\Http\Request;

class DeviceIdentifier
{
    public static function hashForRequest(Request $request): string
    {
        $ip = (string) $request->ip();
        $userAgent = (string) $request->userAgent();

        if ($ip === '') {
            $ip = 'unknown-ip';
        }

        if ($userAgent === '') {
            $userAgent = 'unknown-agent';
        }

        return hash('sha256', $ip . '|' . $userAgent);
    }
}
