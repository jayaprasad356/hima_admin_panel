<?php

namespace App\Http\Controllers;

use App\Models\ScreenNotifications;
use Illuminate\Http\Request;

class ScreenNotificationsController extends Controller
{
    // List all speech texts with optional search functionality
    public function index(Request $request)
    {
        $search = $request->get('search');
        $filterDate = $request->get('filter_date');

        // Search speech texts by text content or language
        $screen_notifications = ScreenNotifications::when($search, function ($query, $search) {
            $query->where('text', 'like', '%' . $search . '%')
                  ->orWhere('language', 'like', '%' . $search . '%');
        })
        ->when($filterDate, function ($query) use ($filterDate) {
            return $query->whereDate('datetime', $filterDate); // Make sure column name matches
        })
        ->orderBy('datetime', 'desc') // Order by latest data
        ->get();

        return view('screen_notifications.index', compact('screen_notifications'));
    }

    // Show the form to create a new speech text
    public function create()
    {
        return view('screen_notifications.create');
    }

    // Store a newly created speech text
    public function store(Request $request)
    {
        // Validate the input data
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'language' => 'required|string|max:255',
            'gender' => 'required|in:male,female',
            'datetime' => 'required|date',
        ]);

       

        // Create the speech text record
        ScreenNotifications::create($validated);

        return redirect()->route('screen_notifications.index')->with('success', 'Screen Notification successfully created.');
    }

    // Show the form to edit an existing speech text
    public function edit($id)
    {
        $screen_notifications = ScreenNotifications::findOrFail($id);
        return view('screen_notifications.edit', compact('screen_notifications'));
    }
    

    // Update an existing speech text
    public function update(Request $request, $id)
    {
        $screen_notifications = ScreenNotifications::findOrFail($id);

        // Validate the input data
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'language' => 'required|string|max:255',
            'gender' => 'required|in:male,female',
            'datetime' => 'required|date',
        ]);

        // Update speech text details
        $screen_notifications->update($validated);

        return redirect()->route('screen_notifications.index')->with('success', 'Screen Notifications successfully updated.');
    }

    // Delete a speech text
    public function destroy($id)
    {
        $screen_notifications = ScreenNotifications::findOrFail($id);
        $screen_notifications->delete();

        return redirect()->route('screen_notifications.index')->with('success', 'Screen Notifications successfully deleted.');
    }
}
