<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FirebaseService;
use App\Models\Users;
use App\Models\fcm_tokens;

class FcmNotificationController extends Controller
{
    protected $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

   public function index()
    {
        $fcm_tokens = fcm_tokens::with('users')->get(); // Ensure 'users' relationship is loaded
    
        return view('fcm_token.index', compact('fcm_tokens'));
    }
    
    public function sendNotification(Request $request)
    {
        $request->validate([
            'senderId' => 'required|string',
            'receiverId' => 'required|string',
            'callType' => 'required|string',
            'channelName' => 'required|string',
            'message' => 'required|string',
        ]);

        $receiverId = $request->input('receiverId');

        // Check if receiver exists
        $receiver = Users::find($receiverId);
        if (!$receiver) {
            return response()->json([
                'message' => 'Receiver not found',
                'success' => false
            ], 404);
        }

        // Get FCM token of receiver
        $fcmToken = fcm_tokens::where('user_id', $receiverId)->value('token');
        if (!$fcmToken) {
            return response()->json([
                'message' => 'Receiver does not have an FCM token',
                'success' => false
            ], 404);
        }

        // Prepare notification data
        $data = [
            'senderId' => $request->input('senderId'),
            'receiverId' => $receiverId,
            'callType' => $request->input('callType'),
            'channelName' => $request->input('channelName'),
            'message' => $request->input('message'),
        ];

        try {
            // Send notification using Firebase service
            $response = $this->firebaseService->sendNotification($fcmToken, $data);
        
            return response()->json([
                'message' => 'Notification sent successfully',
                'response' => $response,  // Include Firebase response details
                'data_sent' => $data,  // Include the data that was sent
                'fcm_token' => $fcmToken,  // Include the FCM token used
                'success' => true
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send notification',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
        
    }
}
