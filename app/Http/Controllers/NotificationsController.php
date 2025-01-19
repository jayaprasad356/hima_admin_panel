<?php

namespace App\Http\Controllers;

use App\Models\Users;
use App\Models\Notifications;
use Illuminate\Http\Request;

class NotificationsController extends Controller
{
    // List all speech texts with optional search functionality
    public function index(Request $request)
    {
        $search = $request->get('search');
        $userGender = auth()->user()->gender;  // Assuming user is logged in
        $notifications = Notifications::when($search, function ($query, $search) {
            $query->where('title', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
        })->orderBy('datetime', 'desc')->get();
    
        $users = Users::all();
    
        return view('notifications.index', compact('notifications', 'users','userGender'));
    }
    
    

    // Show the form to create a new speech text
    public function create()
    {
        $users = \App\Models\Users::all(); // Fetch all users
        return view('notifications.create', compact('users'));
    }

    public function store(Request $request)
    {
        // Validate the input data
        $validated = $request->validate([
            'title' => 'required|string|max:5000',
            'description' => 'required|string|max:5000',
            'gender' => 'required|string|max:5000',
        ]);
    
        // Create the notification record
        Notifications::create($validated);
    
        return redirect()->route('notifications.index')->with('success', 'Notifications successfully created.');
    }
    

    public function edit($id)
    {
        $notification = Notifications::findOrFail($id);
        $users = Users::all(); // Fetch all users
        return view('notifications.edit', compact('notification', 'users'));
    }
    
    
    public function update(Request $request, $id)
    {
        $notification = Notifications::findOrFail($id);
    
        // Validate the input data
        $validated = $request->validate([
            'title' => 'required|string|max:5000',
            'description' => 'required|string|max:5000',
            'gender' => 'required|string|max:5000',
        ]);
    
        // Update notification details
        $notification->update($validated);
    
        return redirect()->route('notifications.index')->with('success', 'Notification successfully updated.');
    }
    

    // Delete a speech text
    public function destroy($id)
    {
        $notification = Notifications::findOrFail($id);
        $notification->delete();

        return redirect()->route('notifications.index')->with('success', 'Notifications successfully deleted.');
    }
    public function searchUsers(Request $request)
{
    $search = $request->get('q'); // Get the query parameter

    $users = \App\Models\Users::where('name', 'like', '%' . $search . '%')
                             ->orWhere('mobile', 'like', '%' . $search . '%')
                             ->get(['id', 'name', 'mobile']); // Select only necessary fields

    return response()->json($users);
}


}
