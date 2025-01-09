<?php

namespace App\Http\Controllers;

use App\Models\Users;
use App\Models\Withdrawals;
use Illuminate\Http\Request;

class WithdrawalsController extends Controller
{
    public function index(Request $request)
    {
        // Get the status filter from the query string, default to 1 (Pending)
        $status = $request->get('status', 0);

        $withdrawals = Withdrawals::with('users') // Assuming a relation with Users model
        ->when($status, function ($query, $status) {
            return $query->where('status', $status);
        })
        ->when($request->get('search'), function ($query, $search) {
            $query->where('transaction_id', 'like', '%' . $search . '%')
                  ->orWhereHas('user', function ($query) use ($search) {
                      $query->where('name', 'like', '%' . $search . '%')
                            ->orWhere('mobile', 'like', '%' . $search . '%');
                  });
        })
        ->get();
    
    return view('withdrawals.index', compact('withdrawals'));
    
    }
    public function bulkUpdateStatus(Request $request)
    {
        // Validate the request to ensure withdrawal IDs are provided
        $request->validate([
            'withdrawal_ids' => 'required|array',
            'withdrawal_ids.*' => 'exists:withdrawals,id',
        ]);
    
        // Update the status of selected withdrawals to "Paid" (1)
        Withdrawals::whereIn('id', $request->withdrawal_ids)->update(['status' => 1]);
    
        // Redirect back with a success message
        return redirect()->route('withdrawals.index')->with('success', __('Selected withdrawals have been marked as Paid.'));
    }
    
  
}
