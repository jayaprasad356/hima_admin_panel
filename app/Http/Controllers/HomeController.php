<?php

namespace App\Http\Controllers;

use App\Models\AccountList;
use App\Models\Announcement;
use App\Models\AttendanceEmployee;
use App\Models\Employee;
use App\Models\Event;
use App\Models\LandingPageSection;
use App\Models\Meeting;
use App\Models\Job;
use App\Models\Order;
use App\Models\Payees;
use App\Models\Avatars;
use App\Models\Users;
use App\Models\Withdrawals;
use App\Models\Payer;
use App\Models\Plan;
use App\Models\Ticket;
use App\Models\Admin;
use App\Models\Transactions;
use App\Models\Utility;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {

        $avatar_count = Avatars::count();
        $users_count = Users::count();
        $today = date('Y-m-d');
        $male_users_count = Users::where('gender', 'male')->whereDate('datetime', $today)->count();
        $female_users_count = Users::where('gender', 'female')->whereDate('datetime', $today)->count();
        $active_audio_users_count = Users::where('audio_status', 1)->count();
        $active_video_users_count = Users::where('video_status', 1)->count();
        $today_recharge_count = Transactions::where('type', 'add_coins')->whereDate('datetime', $today)->sum('amount');
        $pending_withdrawals = Withdrawals::where('status', 0)->count();
             
                return view('dashboard.dashboard', compact('avatar_count','users_count','male_users_count','female_users_count','active_audio_users_count','active_video_users_count','today_recharge_count','pending_withdrawals'));
            }
       
    }

    

