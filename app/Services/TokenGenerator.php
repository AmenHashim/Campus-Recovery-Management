<?php

namespace App\Services;

use App\Models\Claim;

class TokenGenerator
{
    /** e.g. UNI-4F2A-9B7C — generated and persisted server-side, never trusted from the client. */
    public static function claimToken(): string
    {
        do {
            $token = self::generate();
        } while (Claim::where('token', $token)->exists());

        return $token;
    }

    protected static function generate(): string
    {
        $chars = '0123456789ABCDEF';
        $token = 'UNI-';

        for ($i = 0; $i < 4; $i++) {
            $token .= $chars[random_int(0, 15)];
        }
        $token .= '-';
        for ($i = 0; $i < 4; $i++) {
            $token .= $chars[random_int(0, 15)];
        }

        return $token;
    }
}
