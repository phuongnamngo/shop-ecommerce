<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Contracts\Activity;

trait LogsAdminCauser
{
    public function tapActivity(Activity $activity, string $eventName): void
    {
        $admin = Auth::guard('admin')->user();
        if ($admin !== null) {
            $activity->causer()->associate($admin);
        }
    }
}
