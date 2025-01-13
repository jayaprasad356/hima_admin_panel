<?php

namespace App\Http\Controllers;

use App\Models\Users;
use App\Models\Avatars;
use App\Models\Transactions;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    // List all users with optional search functionality
    public function index(Request $request)
    {
        $search = $request->get('search');
    
        // Search users by name, mobile, or language and eager load the avatar relationship
        $users = Users::with('avatar')
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', '%' . $search . '%')
                      ->orWhere('mobile', 'like', '%' . $search . '%')
                      ->orWhere('language', 'like', '%' . $search . '%');
            })
            ->orderBy('datetime', 'desc') // Order by latest data
            ->get();
    
        return view('users.index', compact('users'));
    }
    // Show the form to edit an existing user
    public function edit($id)
    {
        $user = Users::findOrFail($id);
    
        // Fetch all avatars
        $avatars = Avatars::all();
    
        // Available languages
        $languages = ['Hindi', 'Telugu', 'Malayalam', 'Kannada', 'Punjabi', 'Tamil'];
    
        return view('users.edit', compact('user', 'avatars', 'languages'));
    }

    // Update an existing user
    public function update(Request $request, $id)
    {
        $user = Users::findOrFail($id);

        $user->name = $request->name;
        $user->avatar_id = $request->avatar_id;
        $user->mobile = $request->mobile;
        $user->language = $request->language; 
        $user->age = $request->age;
        $user->interests = $request->interests;
        $user->describe_yourself = $request->describe_yourself;
        $user->voice = $request->voice; 
        $user->audio_status = $request->audio_status;
        $user->video_status = $request->video_status; 
        $user->datetime = now();
        $user->save();

        return redirect()->route('users.index')->with('success', 'user successfully updated.');
    }

    // Delete a user
    public function destroy($id)
    {
        $user = Users::findOrFail($id);
        $user->delete();

        return redirect()->route('users.index')->with('success', 'user successfully deleted.');
    }

    // Handle Add Coins form submission
    public function addCoins(Request $request, $id)
    {
        // Validate the input
        $request->validate([
            'coins' => 'required|numeric|min:1',
        ]);

        $user = Users::findOrFail($id); // Retrieve the user by ID

        // Update the user's coins
        $user->coins += $request->input('coins');
        $user->total_coins += $request->input('coins');
        $user->save();

        // Create a new transaction record
        Transactions::create([
            'user_id' => $user->id,
            'type' => 'add_coins',
            'coins' => $request->input('coins'),
            'payment_type' => 'Credit',
            'datetime' => now(),
        ]);

        return redirect()->route('users.index')->with('success', 'Coins Added Successfully.');
    }
    public function updateStatus(Request $request)
{
    // Validate the input data
    $request->validate([
        'user_ids' => 'required|array',
        'user_ids.*' => 'exists:users,id', // Make sure user IDs are valid
        'status' => 'required|in:1,2,3', // Status must be one of 1 (Pending), 2 (Verified), or 3 (Rejected)
    ]);

    // Update the status of selected users to the given status
    $updated = Users::whereIn('id', $request->user_ids)
                        ->update(['status' => $request->status]);

    // If update is successful, return a success response
    if ($updated) {
        return response()->json(['success' => true]);
    }

    // If update fails, return an error response
    return response()->json(['success' => false]);
}



 

    
}
