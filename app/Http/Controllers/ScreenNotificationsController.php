<?php

namespace App\Http\Controllers;

use App\Models\ScreenNotifications;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
            'gender' => 'required|in:all,male,female',
            'datetime' => 'required|date',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        // Handle file uploads
        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('logos', 'public');
        }

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('images', 'public');
        }

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

    public function update(Request $request, $id)
{
    $screen_notifications = ScreenNotifications::findOrFail($id);

    // Validate input data
    $validated = $request->validate([
        'title' => 'required|string|max:255',
        'description' => 'required|string|max:5000',
        'language' => 'required|string|max:255',
        'gender' => 'required|in:all,male,female',
        'datetime' => 'required|date',
        'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
    ]);

    // Handle logo upload
    if ($request->hasFile('logo')) {
        // Delete old logo if it exists
        if ($screen_notifications->logo) {
            Storage::disk('public')->delete($screen_notifications->logo);
        }

        // Store new logo and update path
        $validated['logo'] = $request->file('logo')->store('logos', 'public');
    }

    // Handle image upload
    if ($request->hasFile('image')) {
        // Delete old image if it exists
        if ($screen_notifications->image) {
            Storage::disk('public')->delete($screen_notifications->image);
        }

        // Store new image and update path
        $validated['image'] = $request->file('image')->store('images', 'public');
    }

    // Update the record with validated data
    $screen_notifications->update($validated);

    return redirect()->route('screen_notifications.index')->with('success', 'Screen Notification successfully updated.');
}

    
    // Delete a speech text
    public function destroy($id)
    {
        $screen_notifications = ScreenNotifications::findOrFail($id);

        // Delete the logo and image if exists
        if ($screen_notifications->logo) {
            Storage::disk('public')->delete($screen_notifications->logo);
        }

        if ($screen_notifications->image) {
            Storage::disk('public')->delete($screen_notifications->image);
        }

        $screen_notifications->delete();

        return redirect()->route('screen_notifications.index')->with('success', 'Screen Notifications successfully deleted.');
    }
}
