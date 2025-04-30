<?php

namespace App\Http\Controllers;

use App\Models\Users;
use Illuminate\Http\Request;
use Berkayk\OneSignal\OneSignalFacade as OneSignal;

class UsersVerificationController extends Controller
{
    public function index(Request $request)
        {
            $status = $request->get('status', 1);
            $language = $request->get('language', '');
            $date = $request->get('date'); // Get selected date

            $languages = ['Hindi', 'Telugu', 'Malayalam', 'Kannada', 'Punjabi', 'Tamil'];

            // Search users by name, mobile, language, and filter by status
            $users = Users::with('avatar')
                ->when($status, function ($query, $status) {
                    return $query->where('status', $status);
                })
                ->when($language, function ($query, $language) {
                    return $query->where('language', $language);
                })
                ->when($request->get('search'), function ($query, $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', '%' . $search . '%')
                        ->orWhere('mobile', 'like', '%' . $search . '%')
                        ->orWhere('language', 'like', '%' . $search . '%');
                    });
                })
                ->when($date, function ($query, $date) {
                    return $query->whereDate('datetime', $date); // Filter by a single date
                })
                ->orderBy('datetime', 'desc')
                ->get();

            return view('users-verification.index', compact('users', 'languages', 'status', 'language', 'date'));
        }

    
        public function updateStatus(Request $request)
        {
            $validated = $request->validate([
                'user_ids' => 'required|array|min:1',
                'user_ids.*' => 'exists:users,id',
                'status' => 'required|in:1,2,3',
            ]);
        
            $status = $request->input('status');
            $userIds = $request->input('user_ids');
        
            // Get users who are currently verified (status = 2)
            $verifiedUsers = Users::whereIn('id', $userIds)
                ->where('status', 2)
                ->pluck('id') // Get IDs of already verified users
                ->toArray();
        
            // Users to be updated (allow updating unless trying to re-verify)
            $usersToUpdate = Users::whereIn('id', $userIds)
                ->where(function ($query) use ($status, $verifiedUsers) {
                    if ($status == 2) {
                        // Prevent re-verification (skip users already in status 2)
                        $query->whereNotIn('id', $verifiedUsers);
                    }
                })
                ->pluck('id')
                ->toArray();
        
            if (empty($usersToUpdate) && $status == 2) {
                return redirect()->back()->with('error', 'Selected users are already verified and cannot be re-verified.');
            }
        
                 Users::whereIn('id', $usersToUpdate)->update([
                                'status' => $status,
                                'datetime' => now(), // Ensure timestamp updates
                            ]);
        
            // Send notification ONLY if status is 2 and users were updated
            if ($status == 2 && !empty($usersToUpdate)) {
                OneSignal::sendNotificationCustom([
                    "app_id" => "2c7d72ae-8f09-48ea-a3c8-68d9c913c592",
                    "include_external_user_ids" => array_map('strval', $usersToUpdate), // Convert IDs to strings
                    "headings" => ["en" => "Your Hima account has been Verified!"],
                    "contents" => ["en" => "Now you can Enable Audio Call and Video Call Button."],
                    "small_icon" => "notification_icon",
                    "large_icon" => "https://himaapp.in/storage/uploads/logo/notification_icon.webp"
                ]);
            }
        
            return redirect()->back()->with('success', 'User status updated successfully!');
        }
        

    public function edit($id)
    {
        $userVerification = Users::findOrFail($id);
        return view('users-verification.edit', compact('userVerification'));
    }
    
    public function update(Request $request, $id)
    {
        $request->validate([
            'describe_yourself' => 'required|string',
        ]);
    
        $userVerification = Users::findOrFail($id);
        $user = Users::findOrFail($id);

    
        // Update user's bank details
        $user->update([
            'describe_yourself' => $request->describe_yourself,
        ]);
    
        // Redirect with success message
        return redirect()->route('users-verification.index')->with('success', 'Describe Yourself updated successfully.');
    }

}
