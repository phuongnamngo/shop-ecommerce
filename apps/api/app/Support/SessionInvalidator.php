<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

final class SessionInvalidator
{
    public static function forgetUserSessions(int $userId): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $userId)
            ->delete();
    }
}
