<?php

namespace App\Http\Controllers;

use App\Models\Users;
use App\Models\Withdrawals;
use App\Models\Transactions;
use Illuminate\Http\Request;
use App\Exports\WithdrawalsExport; 
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;

class WithdrawalsController extends Controller
{
    public function index(Request $request)
    {
        // Get the filters from the query string
        $status = $request->get('status'); // Default to Pending
        $transferType = $request->get('transfer_type'); // No default
        $filterDate = $request->get('filter_date');

        // Query to fetch withdrawals based on the filters
        $withdrawals = Withdrawals::with('users')
            ->when($status !== null, function ($query) use ($status) {
                return $query->where('status', $status);
            })
            ->when($transferType, function ($query) use ($transferType) {
                return $query->where('type', $transferType); // Assuming 'type' is the column for transfer type
            })
            ->when($filterDate, function ($query) use ($filterDate) {
                return $query->whereDate('datetime', $filterDate); // Filter withdrawals by selected date
            })
            ->when($request->get('search'), function ($query, $search) {
                $query->where('transaction_id', 'like', '%' . $search . '%')
                      ->orWhereHas('users', function ($query) use ($search) {
                          $query->where('name', 'like', '%' . $search . '%')
                                ->orWhere('mobile', 'like', '%' . $search . '%');
                      });
            })
            ->orderBy('datetime', 'desc') // Order by latest data
            ->get();

        // Return the view with the filtered withdrawals
        return view('withdrawals.index', compact('withdrawals'));
    }

    public function edit($id)
    {
        // Fetch withdrawal and associated user
        $withdrawal = Withdrawals::with('users')->findOrFail($id);
        $user = $withdrawal->users; // Get the associated user
    
        // Pass both withdrawal and user to the view
        return view('withdrawals.edit', compact('withdrawal', 'user'));
    }
    
    public function update(Request $request, $id)
    {
        $request->validate([
            'bank' => 'required|string|max:255',
            'branch' => 'required|string|max:255',
            'ifsc' => 'required|string|max:20',
            'account_num' => 'required|string|max:30',
            'holder_name' => 'required|string|max:255',
        ]);
    
        $withdrawal = Withdrawals::findOrFail($id);
        $user = Users::findOrFail($withdrawal->user_id);
    
        // Update user's bank details
        $user->update([
            'bank' => $request->bank,
            'branch' => $request->branch,
            'ifsc' => $request->ifsc,
            'account_num' => $request->account_num,
            'holder_name' => $request->holder_name,
        ]);
    
        // Redirect with success message
        return redirect()->route('withdrawals.index')->with('success', 'Bank details updated successfully.');
    }
    
    
    public function bulkUpdateStatus(Request $request)
    {
        // Validate the request to ensure withdrawal IDs and status are provided
        $request->validate([
            'withdrawal_ids' => 'required|array',
            'withdrawal_ids.*' => 'exists:withdrawals,id',
            'new_status' => 'required|integer|in:1,2', // Only allow 1 (Paid) or 2 (Cancelled)
        ]);

        $status = (int) $request->new_status;
        $successMessage = '';
        $errorMessage = '';

        // Use a database transaction to ensure atomic updates
        DB::transaction(function () use ($request, $status, &$successMessage, &$errorMessage) {
            foreach ($request->withdrawal_ids as $withdrawalId) {
                $withdrawal = Withdrawals::find($withdrawalId);

                if ($withdrawal) {
                    // Check if the withdrawal is already cancelled (status 2)
                    if ($withdrawal->status == 2) {
                        // If the withdrawal is already cancelled, and trying to cancel again
                        if ($status === 2) {
                            $errorMessage = "The withdrawal with ID {$withdrawalId} is already cancelled. It cannot be cancelled again.";
                            continue; // Skip processing this withdrawal
                        }

                        // If the withdrawal is already cancelled, and trying to mark as paid
                        if ($status === 1) {
                            $errorMessage = "The withdrawal with ID {$withdrawalId} is already cancelled. It cannot be paid again.";
                            continue; // Skip processing this withdrawal
                        }
                    }

                    // Handle the case where the status is set to Cancelled (2)
                    if ($status === 2) {
                        $user = Users::find($withdrawal->user_id);

                        if ($user) {
                            // Refund the amount to the user's balance only if it is not already canceled
                            $user->increment('balance', $withdrawal->amount);

                            // Log the cancellation in the transactions table
                            Transactions::create([
                                'user_id' => $user->id,
                                'type' => 'cancelled',
                                'coins' => 0,
                                'amount' => $withdrawal->amount ?? 0,
                                'datetime' => now(),
                            ]);
                        }

                        // Update the withdrawal status to Cancelled
                        $withdrawal->update(['status' => 2]);
                        $successMessage = "The withdrawal with ID {$withdrawalId} has been successfully cancelled.";
                    }

                    // Handle the case where the status is set to Paid (1)
                    if ($status === 1) {
                        // Update the withdrawal status to Paid
                        $withdrawal->update(['status' => 1]);
                        $successMessage = "The withdrawal with ID {$withdrawalId} has been successfully marked as paid.";
                    }
                }
            }
        });

        // Return the response with the appropriate success or error message
        if ($errorMessage) {
            return redirect()->route('withdrawals.index')->with('error', $errorMessage);
        }

        if ($successMessage) {
            return redirect()->route('withdrawals.index')->with('success', $successMessage);
        }

        return redirect()->route('withdrawals.index')->with('info', 'No withdrawals were updated.');
    }

  
    public function export(Request $request)
{
    // Get the status from the request if provided
    $filters = $request->only('status', 'filter_date');

    return Excel::download(new WithdrawalsExport($filters), 'withdrawals.xlsx');
}

  
}


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
    $filterDate = $request->get('filter_date');
    $gender = $request->get('gender');
    $language = $request->get('language');

    // If filters are applied, reset pagination to page 1
    if ($request->hasAny(['search', 'filter_date', 'gender', 'language']) && !$request->has('page')) {
        return redirect()->route('users.index', array_merge($request->except('page'), ['page' => 1]));
    }

    $users = Users::when($filterDate, function ($query) use ($filterDate) {
            return $query->whereDate('created_at', $filterDate);
        })
        ->when($gender, function ($query) use ($gender) {
            return $query->where('gender', $gender);
        })
        ->when($language, function ($query) use ($language) {
            return $query->where('language', $language);
        })
        ->when($search, function ($query, $search) {
            return $query->where(function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%')
                      ->orWhere('mobile', 'like', '%' . $search . '%')
                      ->orWhere('language', 'like', '%' . $search . '%');
            });
        })
        ->orderBy('created_at', 'desc')
        ->paginate();

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
