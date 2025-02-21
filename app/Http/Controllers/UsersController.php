<?php

namespace App\Http\Controllers;

use App\Models\Users;
use App\Models\Avatars;
use App\Models\Transactions;
use Illuminate\Http\Request;
use Carbon\Carbon;

class UsersController extends Controller
{
    // List all users with optional search functionality
    public function index(Request $request)
    {
        $search = $request->get('search');
        $filterDate = $request->get('filter_date') ?: now()->toDateString(); // Defaults to today's date
        $gender = $request->get('gender');
        $language = $request->get('language');
    
        $users = Users::query()
            ->when(!$search, function ($query) use ($filterDate) {
                // Filter only users created today (without time)
                return $query->whereDate('created_at', $filterDate);
            })
            ->when($search, function ($query) use ($search) {
                return $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%' . $search . '%')
                          ->orWhere('mobile', 'like', '%' . $search . '%')
                          ->orWhere('language', 'like', '%' . $search . '%');
                });
            })
            ->when($gender, function ($query) use ($gender) {
                return $query->where('gender', $gender);
            })
            ->when($language, function ($query) use ($language) {
                return $query->where('language', $language);
            })
            ->orderBy('created_at', 'desc')
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
        $user->status = $request->status;
        $user->interests = $request->interests;
        $user->describe_yourself = $request->describe_yourself;
        $user->voice = $request->voice; 
        $user->audio_status = $request->audio_status;
        $user->video_status = $request->video_status; 
        $user->balance = $request->balance; 
        $user->attended_calls = $request->attended_calls;
        $user->describe_yourself = $request->describe_yourself;
        $user->missed_calls = $request->missed_calls; 
        $user->avg_call_percentage = $request->avg_call_percentage; 
        $user->blocked = $request->blocked; 
        $user->coins = $request->coins; 
        $user->total_coins = $request->total_coins;
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
     // Handle Add Coins form submission
     public function addBalance(Request $request, $id)
     {
         // Validate the input
         $request->validate([
             'balance' => 'required|numeric|min:1',
         ]);
 
         $user = Users::findOrFail($id); // Retrieve the user by ID
 
         // Update the user's coins
         $user->balance += $request->input('balance');
         $user->save();
 
         // Create a new transaction record
         Transactions::create([
             'user_id' => $user->id,
             'type' => 'admin_bonus',
             'amount' => $request->input('balance'),
             'payment_type' => 'Credit',
             'datetime' => now(),
         ]);
 
         return redirect()->route('users.index')->with('success', 'Balance Added Successfully.');
     }
    
}
