<?php

namespace App\Http\Controllers;

use App\Models\Users;
use App\Models\Notifications;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Berkayk\OneSignal\OneSignalClient;

class NotificationsController extends Controller
{
    protected $oneSignalClient;

    public function __construct(OneSignalClient $oneSignalClient)
    {
        $this->oneSignalClient = $oneSignalClient;
    }

    // List all notifications with optional search functionality
    public function index(Request $request)
    {
        $search = $request->get('search');
        $notifications = Notifications::when($search, function ($query, $search) {
            $query->where('title', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
        })->orderBy('datetime', 'desc')->get();
    
        $users = Users::all();
    
        return view('notifications.index', compact('notifications', 'users'));
    }

    // Show the form to create a new notification
    public function create()
    {
        return view('notifications.create');
    }

    public function store(Request $request)
    {
        // Validate input
        $validated = $request->validate([
            'title' => 'required|string|max:5000',
            'description' => 'required|string|max:5000',
            'gender' => 'required|string|in:all,male,female',
            'language' => 'required|string|in:all,Hindi,Telugu,Malayalam,Kannada,Punjabi,Tamil',
        ]);
    
        // Create notification record
        $notification = Notifications::create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'gender' => $validated['gender'],
            'language' => $validated['language'],
            'datetime' => now(),
        ]);
    
        if (!$notification) {
            return redirect()->back()->with('error', 'Something went wrong while creating the notification.');
        }
    
        try {
            // Query users based on gender and language selection
            $usersQuery = Users::query();

            if ($validated['gender'] !== 'all') {
                $usersQuery->where('gender', $validated['gender']); // Filter by gender
            }

            if ($validated['language'] !== 'all') {
                $usersQuery->where('language', $validated['language']); // Filter by language
            }

            $users = $usersQuery->get(); // Get filtered users

            if ($users->count() > 0) {
                // Extract user IDs or push notification tokens for OneSignal
                $userIds = $users->pluck('onesignal_player_id')->filter()->toArray(); // Ensure IDs are valid
                
                if (!empty($userIds)) {
                    // Proper OneSignal notification payload with targeting
                    $response = $this->oneSignalClient->sendNotificationCustom([
                        'contents' => ['en' => $validated['description']], // Description first
                        'headings' => ['en' => $validated['title']], // Title second
                        'include_player_ids' => $userIds, // Target specific users
                    ]);

                    // Log OneSignal response
                    Log::info('OneSignal response: ', ['response' => $response]);

                    Log::info("Notification sent successfully to selected users.");
                } else {
                    Log::warning("No valid OneSignal player IDs found for the selected criteria.");
                }
            } else {
                Log::warning("No users found for the selected criteria (Gender: {$validated['gender']}, Language: {$validated['language']}).");
            }

        } catch (\Exception $e) {
            Log::error('Error sending notification: ', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Error sending notification: ' . $e->getMessage());
        }
    
        return redirect()->route('notifications.index')->with('success', 'Notification created and sent successfully.');
    }
    
    
    
    // Edit notification
    public function edit($id)
    {
        $notification = Notifications::findOrFail($id);
        return view('notifications.edit', compact('notification'));
    }

    // Update notification
    public function update(Request $request, $id)
    {
        $notification = Notifications::findOrFail($id);

        // Validate input data
        $validated = $request->validate([
            'title' => 'required|string|max:5000',
            'description' => 'required|string|max:5000',
        ]);

        // Update notification details
        $notification->update($validated);

        return redirect()->route('notifications.index')->with('success', 'Notification successfully updated.');
    }

    // Delete notification
    public function destroy($id)
    {
        $notification = Notifications::findOrFail($id);
        $notification->delete();

        return redirect()->route('notifications.index')->with('success', 'Notification successfully deleted.');
    }

    // Search users via AJAX
    public function searchUsers(Request $request)
    {
        $search = $request->get('q');

        $users = Users::where('name', 'like', '%' . $search . '%')
                      ->orWhere('mobile', 'like', '%' . $search . '%')
                      ->get(['id', 'name', 'mobile']);

        return response()->json($users);
    }
}
