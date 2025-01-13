<?php
namespace App\Http\Controllers;

use App\Models\UserCalls;
use Illuminate\Http\Request;

class UserCallsController extends Controller
{
    public function index(Request $request)
    {
        // Fetch user calls with optional type filter
        $type = $request->get('type'); // Get type from request
        
        $usercalls = UserCalls::with(['user', 'callusers']) // Load user and call_user relationships
            ->when($type, function ($query, $type) {
                return $query->where('type', $type); // Filter by type if provided
            })
            ->orderBy('datetime', 'desc') // Order by latest data
            ->get();

        return view('usercalls.index', compact('usercalls'));
    }
}
