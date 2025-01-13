<?php
namespace App\Http\Controllers;

use App\Models\UserCalls;
use App\Models\Users;
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

    public function updateuser(Request $request)
    {
        // Validate the input
        $request->validate([
            'audio_status' => 'nullable|in:0,1,2,3',
            'video_status' => 'nullable|in:0,1,2,3',
        ]);

        $data = $request->only(['audio_status', 'video_status']);

        // Update the users table based on the provided input
        $updated = Users::query()->update(array_filter($data, fn($value) => $value !== null));

        if ($updated) {
            if ($request->has('audio_status')) {
                return redirect()->back()->with('success', 'Audio status updated successfully!');
            } elseif ($request->has('video_status')) {
                return redirect()->back()->with('success', 'Video status updated successfully!');
            }
        }

        return redirect()->back()->with('error', 'Failed to update status.');
    }

}
