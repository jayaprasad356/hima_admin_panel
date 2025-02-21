<?php

namespace App\Jobs;

use App\Models\Users;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class UpdateUserStatsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $currentTime = Carbon::now();
        $users = Users::where('missed_calls', '>', 0)->orWhere('attended_calls', '>', 0)->get();

        foreach ($users as $user) {
            if ($user->last_audio_time_updated && $currentTime->diffInHours($user->last_audio_time_updated) >= 1) {
                $user->audio_status = 0;
                $user->missed_calls = 0;
                $user->attended_calls = 0;
            }

            if ($user->last_video_time_updated && $currentTime->diffInHours($user->last_video_time_updated) >= 1) {
                $user->video_status = 0;
                $user->missed_calls = 0;
                $user->attended_calls = 0;
            }

            $totalCalls = $user->attended_calls + $user->missed_calls;
            $user->avg_call_percentage = $totalCalls > 0 ? ($user->attended_calls / $totalCalls) * 100 : 0;
            $user->save();
        }
    }
}

