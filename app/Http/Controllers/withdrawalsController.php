<?php

namespace App\Http\Controllers;

use App\Models\Users;
use App\Models\Withdrawals;
use Illuminate\Http\Request;

class WithdrawalsController extends Controller
{
    public function index(Request $request)
    {
        // Get the filters from the query string
        $status = $request->get('status', 0); // Default to Pending
        $transferType = $request->get('transfer_type'); // No default
    
        $withdrawals = Withdrawals::with('users')
            ->when($status !== null, function ($query) use ($status) {
                return $query->where('status', $status);
            })
            ->when($transferType, function ($query) use ($transferType) {
                return $query->where('type', $transferType); // Assuming 'type' is the column for transfer type
            })
            ->when($request->get('search'), function ($query, $search) {
                $query->where('transaction_id', 'like', '%' . $search . '%')
                      ->orWhereHas('users', function ($query) use ($search) {
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
