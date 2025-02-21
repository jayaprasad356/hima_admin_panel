<?php

namespace App\Http\Controllers;

use App\Models\Users;
use App\Models\Notifications;
use Illuminate\Http\Request;
use Berkayk\OneSignal\OneSignalFacade as OneSignal;

class NotificationsController extends Controller
{
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
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:5000',
                'description' => 'required|string|max:5000',
                'gender' => 'required|string|in:all,male,female',
                'language' => 'required|string|in:all,Hindi,Telugu,Malayalam,Kannada,Punjabi,Tamil,English',
            ]);
    
            $notification = Notifications::create([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'gender' => $validated['gender'],
                'language' => $validated['language'],
                'datetime' => now(),
            ]);
    
            if (!$notification) {
                return redirect()->back()->with('error', 'Failed to save notification.');
            }
    
            $gender = $notification->gender;
            $language = $notification->language;
    
            $targetUsers = Users::when($language !== 'all', function ($query) use ($language) {
                    return $query->where('language', $language);
                })
                ->when($gender !== 'all', function ($query) use ($gender) {
                    return $query->where('gender', $gender);
                })
                ->get();
    
            if ($targetUsers->count() > 0 || $gender === 'all' || $language === 'all') {
                $message = "{$notification->title}\n{$notification->description}";
                $payload = [
                    "app_id" => "5cd4154a-1ece-4c3b-b6af-e88bafee64cd",
                    "filters" => [
                        ["field" => "tag", "key" => "gender_language", "relation" => "=", "value" => "{$gender}_{$language}"]
                    ],
                    "content_available" => true,  // Enables background delivery
                    "mutable_content" => true,    // Allows processing in the background
                    "priority" => 5,              // Low priority, ensures silent delivery
                    "data" => [
                        "custom_key" => "hello", // Add any extra data here
                        "another_key" => "another_value"
                    ]
                ];
                OneSignal::sendNotificationCustom($payload);
    
                try {
                    OneSignal::sendNotificationCustom($payload);
                } catch (\Exception $e) {
                    return redirect()->back()->with('error', 'Error sending notification: ' . $e->getMessage());
                }
            }
    
            return redirect()->route('notifications.index')->with('success', 'Notification created and sent successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
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
