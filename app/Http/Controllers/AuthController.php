<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\Users;
use App\Models\Upis;
use App\Models\Avatars;
use App\Models\Coins;
use App\Models\SpeechText;  
use App\Models\Appsettings; 
use App\Models\Ratings; 
use App\Models\Transactions;
use App\Models\DeletedUsers; 
use App\Models\Withdrawals;  
use App\Models\UserCalls;
use Carbon\Carbon;
use App\Models\News; 
use Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;


class AuthController extends Controller
{
    public function __construct(){
        $this->middleware('auth:api', ['except' => ['login','register','send_otp','avatar_list','speech_text','settings_list','appsettings_list','add_coins','cron_jobs']]);
    }
 
    public function register(Request $request)
    {
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'mobile' => 'required|digits:10|unique:users',
            'language' => 'required',
            'avatar_id' => 'required|exists:avatars,id',
            'gender' => 'required|in:Male,Female,male,female,MALE,FEMALE',
        ]);
    
        // If validation fails, return errors
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
    
        $mobile = $request->input('mobile');
        $language = $request->input('language');
        $name = $request->input('name');
        $avatar_id = $request->input('avatar_id');
        $gender = $request->input('gender');
        $age = $request->input('age');
        $interests = $request->input('interests');
        $describe_yourself = $request->input('describe_yourself');
    
        // Check if avatar exists
        $avatar = Avatars::find($avatar_id);
        if (!$avatar) {
            return response()->json([
                'success' => false,
                'message' => 'Avatar not found.',
            ], 200);
        }
    
        // Gender-specific validation for female users
        if (strtolower($gender) === 'female') {
            if (empty($age)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Age is required for female users.',
                ], 200);
            }
            if (empty($interests)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Interests are required for female users.',
                ], 200);
            }
            if (empty($describe_yourself)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Describe Yourself is required for female users.',
                ], 200);
            }
        }
    
        // Generate random name for female users if not provided
        if (strtolower($gender) === 'female' && empty($name)) {
            $name = $this->generateRandomFemaleName();
        } elseif (empty($name)) {
            // Fallback for male users or unspecified gender
            $name = $this->generateRandomName();
        }
    
        // Create the new user
        $users = new Users();
        $users->name = $name;
        $users->mobile = $mobile;
        $users->language = $language;
        $users->avatar_id = $avatar_id;
        $users->gender = $gender;
        $users->age = $age;
        $users->interests = $interests;
        $users->describe_yourself = $describe_yourself;
        $users->datetime = Carbon::now();
    
        $users->save();
    
        // Prepare the user details to return
        $avatar = Avatars::find($users->avatar_id);
        $imageUrl = ($avatar && $avatar->image) ? asset('storage/app/public/' . $avatar->image) : '';
        $voicePath = $users && $users->voice ? asset('storage/app/public/voices/' . $users->voice) : '';
    
        // Attempt to log the user in using the mobile number (no need for password)
        $credentials = ['mobile' => $mobile];
        config(['jwt.ttl' => 60 * 24 * 90]); // 90 days in minutes

        // Attempt authentication
        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        // Return the response
        $userDetails = [
            'id' => $users->id,
            'name' => $users->name,
            'user_gender' => $users->gender,
            'mobile' => $users->mobile,
            'language' => $users->language,
            'avatar_id' => (int) $users->avatar_id,
            'image' => $imageUrl ?? '',
            'gender' => $gender,
            'age' => (int) $users->age ?? '',
            'interests' => $users->interests,
            'describe_yourself' =>  $users->describe_yourself ?? '',
            'voice' =>  $voicePath ?? '',
            'status' => 0,
            'balance' => (int) $users->balance ?? '',
            'datetime' => Carbon::parse($users->datetime)->format('Y-m-d H:i:s'),
            'created_at' => Carbon::parse($users->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => Carbon::parse($users->updated_at)->format('Y-m-d H:i:s'),
        ];
    
        return response()->json([
            'success' => true,
            'message' => 'Registered successfully.',
            'token' => $token,
            'data' => $userDetails,
        ], 200);
    }
    
    private function generateRandomFemaleName(){
        // Fetch a random name from female_users table
        $randomFemaleName = DB::table('female_users')->inRandomOrder()->value('name');
        if (!$randomFemaleName) {
            $randomFemaleName = 'users'; // Default name if table is empty
        }

        // Append random 3 digits
        $randomDigits = substr(str_shuffle('0123456789'), 0, 3);
        return $randomFemaleName . $randomDigits;
    }

    private function generateRandomName(){
        $letters = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 5);
        $numbers = substr(str_shuffle('0123456789'), 0, 3);
        return $letters . $numbers;
    }
    

    public function login(Request $request)
    {
        $validator = Validator::make(request()->all(), [
            'mobile' => 'required|digits:10',
        ]);
 
        if($validator->fails()){
            return response()->json($validator->errors(), 400);
        }
        $mobile = request()->mobile;
        $credentials = request(['mobile']);
        
        $users = Users::where('mobile', $mobile)->first();
    
        // If user not found, return failure response
        if (!$users) {
            $response['success'] = true;
            $response['registered'] = false;
            $response['message'] = 'mobile not registered.';
            return response()->json($response, 200);
        }

        config(['jwt.ttl' => 60 * 24 * 90]); // 30 days in minutes

        // Attempt authentication
        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        $avatar = Avatars::find($users->avatar_id);
        $gender = $avatar ? $avatar->gender : '';

        $imageUrl = ($avatar && $avatar->image) ? asset('storage/app/public/' . $avatar->image) : '';
        $voicePath = $users && $users->voice ? asset('storage/app/public/voices/' . $users->voice) : '';

        return response()->json([
            'token' => $token,
            'success' => true,
            'registered' => true,
            'message' => 'Logged in successfully.',
            'data' => [
                'id' => $users->id,
                'name' => $users->name,
                'user_gender' => $users->gender,
                'language' => $users->language,
                'mobile' => $users->mobile,
                'avatar_id' => (int) $users->avatar_id,
                'image' => $imageUrl ?? '',
                'gender' => $gender,
                'age' => (int) $users-> age ?? '',
                'interests' => $users->interests ?? '',
                'describe_yourself' => $users->describe_yourself ?? '',
                'voice' => $voicePath ?? '',
                'status' => $users->status ?? '',
                'balance' =>(int) $users->balance ?? '',
                'coins' =>(int) $users->coins ?? '',
                'audio_status' =>(int) $users->audio_status ?? '',
                'video_status' =>(int) $users->video_status ?? '',
                'datetime' => Carbon::parse($users->datetime)->format('Y-m-d H:i:s'),
                'updated_at' => Carbon::parse($users->updated_at)->format('Y-m-d H:i:s'),
                'created_at' => Carbon::parse($users->created_at)->format('Y-m-d H:i:s'),
            ],
        ], 200);
    }

    public function update_profile(Request $request)
{
    $users = auth('api')->user(); // Retrieve the authenticated user
    
    if (empty($users)) {
        return response()->json([
            'success' => false,
            'message' => 'Unable to retrieve user details.',
        ], 200);
    }
    $user_id = $request->input('user_id');
    $avatar_id = $request->input('avatar_id');
    $interests = $request->input('interests');

    if (empty($user_id)) {
        return response()->json([
            'success' => false,
            'message' => 'user_id is empty.',
        ], 200);
    }

    if (empty($interests)) {
        return response()->json([
            'success' => false,
            'message' => 'interests is empty.',
        ], 200);
    }

    $user = Users::find($user_id);

    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'user not found.',
        ], 200);
    }
 
    if (empty($avatar_id)) {
        return response()->json([
            'success' => false,
            'message' => 'avatar_id is empty.',
        ], 200);
    }

    $avatar = Avatars::find($avatar_id);

    if (!$avatar) {
        return response()->json([
            'success' => false,
            'message' => 'avatar not found.',
        ], 200);
    }


    $name = $request->input('name');

    if (!empty($name) && Users::where('name', $name)->where('id', '!=', $user_id)->exists()) {
        return response()->json([
            'success' => false,
            'message' => 'The provided name already exists.',
        ], 200);
    }


    // Update user details
    if ($name !== null) {
        $user->name = $name;
    }
    $user->interests = $interests;
    $user->avatar_id = $avatar_id;
    $user->datetime = now(); 
    $user->save();

    $avatar = Avatars::find($user->avatar_id);
   $gender = $avatar ? $avatar->gender : '';

   $imageUrl = ($avatar && $avatar->image) ? asset('storage/app/public/' . $avatar->image) : '';
   $voicePath = $user && $user->voice ? asset('storage/app/public/voices/' . $user->voice) : '';

    return response()->json([
        'success' => true,
        'message' => 'User details updated successfully.',
        'data' => [
            'id' => $user->id,
            'name' => $user->name,
            'user_gender' => $user->gender,
            'language' => $user->language,
            'mobile' => $user->mobile,
            'avatar_id' => (int) $user->avatar_id,
            'image' => $imageUrl ?? '',
            'gender' => $gender,
             'age' => (int) $user-> age ?? '',
            'interests' => $user->interests,
            'describe_yourself' => $user-> describe_yourself ?? '',
             'voice' => $voicePath ?? '',
             'status' => $user->status ?? '',
             'balance' => (int) $user->balance ?? '',
             'coins' => (int) $user->coins ?? '',
             'audio_status' =>(int) $user->audio_status ?? '',
             'video_status' =>(int) $user->video_status ?? '',
            'datetime' => Carbon::parse($user->datetime)->format('Y-m-d H:i:s'),
            'updated_at' => Carbon::parse($user->updated_at)->format('Y-m-d H:i:s'),
            'created_at' => Carbon::parse($user->created_at)->format('Y-m-d H:i:s'),
        ],
    ], 200);
}
    public function userdetails(Request $request)
    {
        $authenticatedUser = auth('api')->user(); // Retrieve the authenticated user

        if (empty($authenticatedUser)) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to retrieve user details.',
            ], 200);
        }

        $user_id = $request->input('user_id');
        
        if (empty($user_id)) {
            return response()->json([
                'success' => false,
                'message' => 'user_id is empty.',
            ], 200);
        }

        $user = Users::find($user_id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 200);
        }

        $avatar = Avatars::find($user->avatar_id);
        $gender = $avatar ? $avatar->gender : '';

        $imageUrl = ($avatar && $avatar->image) ? asset('storage/app/public/' . $avatar->image) : '';
        $voicePath = $user && $user->voice ? asset('storage/app/public/voices/' . $user->voice) : '';

        $upi = Upis::where('user_id', $user->id)->first();
        $upi_id = $upi ? $upi->upi_id : '';

        return response()->json([
            'success' => true,
            'message' => 'User details retrieved successfully.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'user_gender' => $user->gender,
                'avatar_id' => (int) $user->avatar_id,
                'image' => $imageUrl ?? '',
                'gender' => $gender,
                'language' => $user->language,
                'age' => (int) $user->age ?? '',
                'mobile' => $user->mobile ?? '',
                'interests' => $user->interests ?? '',
                'describe_yourself' => $user->describe_yourself ?? '',
                'voice' => $voicePath ?? '',
                'status' => $user->status ?? '',
                'balance' => (int) $user->balance ?? '',
                'coins' => (int) $user->coins ?? '',
                'audio_status' => (int) $user->audio_status ?? '',
                'video_status' => (int) $user->video_status ?? '',
                'bank' => $user->bank ?? '',
                'account_num' => $user->account_num ?? '',
                'branch' => $user->branch ?? '',
                'ifsc' => $user->ifsc ?? '',
                'holder_name' => $user->holder_name ?? '',
                'upi_id' => $upi_id,
                'datetime' => Carbon::parse($user->datetime)->format('Y-m-d H:i:s'),
                'updated_at' => Carbon::parse($user->updated_at)->format('Y-m-d H:i:s'),
                'created_at' => Carbon::parse($user->created_at)->format('Y-m-d H:i:s'),
            ],
        ], 200);
    }
    public function coins_list(Request $request)
    {
        $authenticatedUser = auth('api')->user();
        if (!$authenticatedUser) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Please provide a valid token.',
            ], 401);
        }
    
        $user_id = $request->input('user_id');
        
        if (empty($user_id)) {
            return response()->json([
                'success' => false,
                'message' => 'user_id is empty.',
            ], 200);
        }
        $user = users::find($user_id);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'user not found.',
            ], 200);
        }
        $offset = $request->input('offset', 0);  // Default offset to 0 if not provided
        $limit = $request->input('limit', 10);  // Default limit to 10 if not provided
    
        $coins = Coins::orderBy('price', 'asc')
                      ->skip($offset)
                      ->take($limit)
                      ->get(); 
    
        if ($coins->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No coins data available.',
            ], 200);
        }
    
        $coinsData = $coins->map(function ($coin) {
            return [
                'id' => $coin->id,
                'price' => $coin->price,
                'coins' => $coin->coins,
                'save' => $coin->save,
                'popular' => $coin->popular,
                'best_offer' => $coin->best_offer,
                'updated_at' => Carbon::parse($coin->updated_at)->format('Y-m-d H:i:s'),
                'created_at' => Carbon::parse($coin->created_at)->format('Y-m-d H:i:s'),
            ];
        });
    
        return response()->json([
            'success' => true,
            'message' => 'Coins listed successfully.',
            'total' => Coins::count(),
            'data' => $coinsData,
        ], 200);
    }

    public function best_offers(Request $request)
{
    $authenticatedUser = auth('api')->user();
    if (!$authenticatedUser) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. Please provide a valid token.',
        ], 401);
    }

    $offset = $request->input('offset', 0);  // Default offset to 0 if not provided
    $limit = $request->input('limit', 10);  // Default limit to 10 if not provided

    // Fetch coins with best_offer = 1
    $coins = Coins::where('best_offer', 1) // Filter by best_offer
                  ->orderBy('price', 'asc')
                  ->skip($offset)
                  ->take($limit)
                  ->get();

    if ($coins->isEmpty()) {
        return response()->json([
            'success' => false,
            'message' => 'No Best Offer data available.',
        ], 200);
    }

    $coinsData = $coins->map(function ($coin) {
        return [
            'id' => $coin->id,
            'price' => $coin->price,
            'coins' => $coin->coins,
            'save' => $coin->save,
            'popular' => $coin->popular,
            'best_offer' => $coin->best_offer,
            'updated_at' => Carbon::parse($coin->updated_at)->format('Y-m-d H:i:s'),
            'created_at' => Carbon::parse($coin->created_at)->format('Y-m-d H:i:s'),
        ];
    });

    return response()->json([
        'success' => true,
        'message' => 'Best Offers listed successfully.',
        'total' => Coins::where('best_offer', 1)->count(), // Count only best_offer = 1
        'data' => $coinsData,
    ], 200);
}

    
    
    public function transaction_list(Request $request)
    {
        $authenticatedUser = auth('api')->user();
        if (!$authenticatedUser) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Please provide a valid token.',
            ], 401);
        }
    
        $user_id = $request->input('user_id');
        
        if (empty($user_id)) {
            return response()->json([
                'success' => false,
                'message' => 'user_id is empty.',
            ], 200);
        }

        $offset = $request->input('offset', 0);  // Default offset to 0 if not provided
        $limit = $request->input('limit', 10);  // Default limit to 10 if not provided
    
        $transactions = Transactions::where('user_id', $user_id)
                     ->orderBy('datetime', 'desc')
                     ->skip($offset)
                     ->take($limit)
                     ->get();
    
        if ($transactions->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No transactions found for this user.',
            ], 200);
        }
    
        $transactionsData = [];
        foreach ($transactions as $transaction) {
            $transactionsData[] = [
                'id' => $transaction->id,
                'user_id' => $transaction->user_id,
                'type' => $transaction->type,
                'amount' => $transaction->amount ?? '', 
                'coins' => $transaction->coins,
                'payment_type' => $transaction->payment_type ?? '',
                'datetime' => $transaction->datetime,
                'date' => Carbon::parse($transaction->datetime)->format('M d'),
            ];
        }
    
        return response()->json([
            'success' => true,
            'message' => 'User transaction list retrieved successfully.',
            'total' => Transactions::where('user_id', $user_id)->count(),
            'data' => $transactionsData,
        ], 200);
    }
    
    public function avatar_list(Request $request)
    {
        $gender = $request->input('gender'); 
        $offset = $request->input('offset', 0);  // Default offset to 0 if not provided
        $limit = $request->input('limit', 10);  // Default limit to 10 if not provided
    
        if (empty($gender)) {
            return response()->json([
                'success' => false,
                'message' => 'Gender is empty.',
            ], 200);
        }
    
        if (!in_array(strtolower($gender), ['male', 'female'])) {
            return response()->json([
                'success' => false,
                'message' => 'Gender must be either "male" or "female".',
            ], 200);
        }
    
        $avatars = Avatars::where('gender', strtolower($gender))
                          ->skip($offset)
                          ->take($limit)
                          ->get();
    
        if ($avatars->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No avatars found for the specified gender.',
            ], 200);
        }
    
        $avatarData = [];
        foreach ($avatars as $avatar) {
            $imageUrl = ($avatar && $avatar->image) ? asset('storage/app/public/' . $avatar->image) : '';
            $avatarData[] = [
                'id' => $avatar->id,
                'gender' => $avatar->gender,
                'image' => $imageUrl,
                'updated_at' => Carbon::parse($avatar->updated_at)->format('Y-m-d H:i:s'),
                'created_at' => Carbon::parse($avatar->created_at)->format('Y-m-d H:i:s'),
            ];
        }
    
        return response()->json([
            'success' => true,
            'message' => 'Avatars listed successfully.',
            'total' => Avatars::where('gender', strtolower($gender))->count(),
            'data' => $avatarData,
        ], 200);
    }
    
public function send_otp(Request $request)
{
    $mobile = $request->input('mobile'); 
    $country_code = $request->input('country_code');
    $otp = $request->input('otp');

    if (empty($mobile)) {
        $response['success'] = false;
        $response['message'] = 'Mobile is empty.';
        return response()->json($response, 200);
    }

    if (strlen($mobile) !== 10) {
        return response()->json([
            'success' => false,
            'message' => 'Mobile should be 10 digits.',
        ], 200);
    }

    if (empty($country_code)) {
        return response()->json([
            'success' => false,
            'message' => 'Country code is empty.',
        ], 200);
    }

    if (empty($otp)) {
        return response()->json([
            'success' => false,
            'message' => 'OTP is empty.',
        ], 200);
    }

    // Define the API URL and parameters for OTP sending
    $apiUrl = 'https://api.authkey.io/request'; 
    $authKey = '673e807e1f672335'; // Your authkey here
    $sid = '14324'; // SID, if applicable

    // Make the HTTP request to the OTP API
    $response = Http::get($apiUrl, [
        'authkey' => $authKey,
        'mobile' => $mobile,
        'country_code' => $country_code,
        'sid' => $sid,
        'otp' => $otp,
    ]);

    if ($response->successful()) {
        // Parse the API response
        $apiResponse = $response->json();
    
        if ($apiResponse['Message'] == 'Submitted Successfully') {
            return response()->json([
                'success' => true,
                'message' => 'OTP sent successfully.',
            ], 200);
        } else {
            return response()->json([
                'success' => false,
                'message' => $apiResponse['Message'] ?? 'Failed to send OTP.',
            ], 200);
        }
    } else {
        return response()->json([
            'success' => false,
            'message' => 'Error communicating with OTP service.',
        ], 500);
    }
}
public function settings_list(Request $request)
{
   
    // Retrieve all news settings
    $news = News::all();

    if ($news->isEmpty()) {
        return response()->json([
            'success' => false,
            'message' => 'No settings found.',
        ], 200);
    }

    // Prepare the data to be returned
    $newsData = [];
    foreach ($news as $item) {
        $newsData[] = [
            'id' => $item->id,
            'privacy_policy' => $item->privacy_policy,
            'support_mail' => $item->support_mail,
            'demo_video' => $item->demo_video,
            'minimum_withdrawals' => $item->minimum_withdrawals,
        ];
    }

    return response()->json([
        'success' => true,
        'message' => 'Settings listed successfully.',
        'data' => $newsData,
    ], 200);
}

public function appsettings_list(Request $request)
{
 
    // Retrieve all news settings
    $appsettings = Appsettings::all();

    if ($appsettings->isEmpty()) {
        return response()->json([
            'success' => false,
            'message' => 'No appsettings found.',
        ], 200);
    }

    // Prepare the data to be returned
    $appsettingsData = [];
    foreach ($appsettings as $item) {
        $appsettingsData[] = [
            'id' => $item->id,
            'link' => $item->link,
            'app_version' => $item->app_version,
            'description' => $item->description,
        ];
    }

    return response()->json([
        'success' => true,
        'message' => 'App Settings listed successfully.',
        'data' => $appsettingsData,
    ], 200);
}

public function delete_users(Request $request)
{
    $authenticatedUser = auth('api')->user();
    if (!$authenticatedUser) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. Please provide a valid token.',
        ], 401);
    }
    $user_id = $request->input('user_id');
    $delete_reason = $request->input('delete_reason');

    if (empty($user_id)) {
        return response()->json([
            'success' => false,
            'message' => 'user_id is empty.',
        ], 200);
    }

    if (empty($delete_reason)) {
        return response()->json([
            'success' => false,
            'message' => 'delete_reason is empty.',
        ], 200);
    }

    // Find the user to delete
    $user = users::find($user_id);
    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'user not found.',
        ], 200);
    }

    // Log user deletion in the DeletedUsers model
    $deleteduser = new DeletedUsers();
    $deleteduser->user_id = $user->id;
    $deleteduser->name = $user->name;
    $deleteduser->mobile = $user->mobile;
    $deleteduser->language = $user->language;
    $deleteduser->avatar_id = $user->avatar_id;
    $deleteduser->coins = $user->coins;
    $deleteduser->total_coins = $user->total_coins;
    $deleteduser->datetime = Carbon::now();
    $deleteduser->delete_reason = $delete_reason;
    $deleteduser->save();

    // Delete the user
    $user->delete();

    return response()->json([
        'success' => true,
        'message' => 'user deleted successfully.',
    ], 200);
}

public function user_validations(Request $request)
{
    $authenticatedUser = auth('api')->user();
    if (!$authenticatedUser) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. Please provide a valid token.',
        ], 401);
    }

    $user_id = $request->input('user_id');
    $name = $request->input('name');

    if (empty($user_id)) {
        return response()->json([
            'success' => false,
            'message' => 'user_id is empty.',
        ], 200);
    }

    if (empty($name)) {
        return response()->json([
            'success' => false,
            'message' => 'name is empty.',
        ], 200);
    }

    if (strlen($name) < 4 || strlen($name) > 10) {
        return response()->json([
            'success' => false,
            'message' => 'Name must be between 4 and 10 characters.',
        ], 200);
    }

    if (!preg_match('/^[a-zA-Z0-9]+$/', $name)) {
        return response()->json([
            'success' => false,
            'message' => 'Name can only contain letters (a-z) and numbers (0-9).',
        ], 200);
    }

    if (preg_match('/\d{3,}/', $name)) {
        return response()->json([
            'success' => false,
            'message' => 'Name cannot contain 3 or more consecutive numbers.',
        ], 200);
    }

    $user = users::find($user_id);
    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'user not found.',
        ], 200);
    }

    if (users::where('name', $name)->where('id', '!=', $user_id)->exists()) {
        return response()->json([
            'success' => false,
            'message' => 'The provided name already exists.',
        ], 200);
    }

    $user->name = $name;
    $user->datetime = now();
    $user->save();

    $avatar = Avatars::find($user->avatar_id);
    $gender = $avatar ? $avatar->gender : '';
    $imageUrl = $avatar && $avatar->image ? asset('storage/app/public/' . $avatar->image) : '';
    $voicePath = $user && $user->voice ? asset('storage/app/public/voices/' . $user->voice) : '';

    return response()->json([
        'success' => true,
        'message' => 'user details updated successfully.',
        'data' => [
            'id' => $user->id,
            'name' => $user->name,
            'user_gender' => $user->gender,
            'avatar_id' => (int) $user->avatar_id,
            'image' => $imageUrl ?? '',
            'gender' => $gender,
            'language' => $user->language,
            'age' => (int) $user->age ?? '',
            'mobile' => $user->mobile ?? '',
            'interests' => $user->interests ?? '',
            'describe_yourself' => $user->describe_yourself ?? '',
            'voice' => $voicePath ?? '',
            'status' => $user->status ?? '',
            'balance' => (int) $user->balance ?? '',
            'audio_status' => (int) $user->audio_status ?? '',
            'video_status' => (int) $user->video_status ?? '',
            'datetime' => Carbon::parse($user->datetime)->format('Y-m-d H:i:s'),
            'updated_at' => Carbon::parse($user->updated_at)->format('Y-m-d H:i:s'),
            'created_at' => Carbon::parse($user->created_at)->format('Y-m-d H:i:s'),
        ],
    ], 200);
}

public function update_voice(Request $request)
{
    $authenticatedUser = auth('api')->user();
    if (!$authenticatedUser) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. Please provide a valid token.',
        ], 401);
    }

    $user_id = $request->input('user_id');
    $voice = $request->file('voice');

    if (empty($user_id)) {
        return response()->json([
            'success' => false,
            'message' => 'user_id is empty.',
        ], 200);
    }

    if (empty($voice)) {
        return response()->json([
            'success' => false,
            'message' => 'voice is empty.',
        ], 200);
    }

    if ($voice->getClientOriginalExtension() !== 'mp3') {
        return response()->json([
            'success' => false,
            'message' => 'The voice file must be an MP3.',
        ], 200);
    }

    $user = users::find($user_id);
    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'user not found.',
        ], 200);
    }

    $voicePath = $voice->store('voices', 'public');

    $user->voice = basename($voicePath);
    $user->status = 1;
    $user->datetime = now();
    $user->save();

    $avatar = Avatars::find($user->avatar_id);
    $gender = $avatar ? $avatar->gender : '';
    $imageUrl = $avatar && $avatar->image ? asset('storage/app/public/' . $avatar->image) : '';
    $voicePath = $user && $user->voice ? asset('storage/app/public/voices/' . $user->voice) : '';

    return response()->json([
        'success' => true,
        'message' => 'user voice updated successfully.',
        'data' => [
            'id' => $user->id,
            'name' => $user->name,
            'user_gender' => $user->gender,
            'avatar_id' => (int) $user->avatar_id,
            'image' => $imageUrl ?? '',
            'gender' => $gender,
            'language' => $user->language,
            'age' => (int) $user->age ?? '',
            'mobile' => $user->mobile ?? '',
            'interests' => $user->interests ?? '',
            'describe_yourself' => $user->describe_yourself ?? '',
            'voice' => $voicePath,
            'status' => $user->status ?? '',
            'balance' => (int) $user->balance ?? '',
            'audio_status' => (int) $user->audio_status ?? '',
            'video_status' => (int) $user->video_status ?? '',
            'datetime' => Carbon::parse($user->datetime)->format('Y-m-d H:i:s'),
            'updated_at' => Carbon::parse($user->updated_at)->format('Y-m-d H:i:s'),
            'created_at' => Carbon::parse($user->created_at)->format('Y-m-d H:i:s'),
        ],
    ], 200);
}

public function speech_text(Request $request)
{
    $language = $request->input('language');
    $offset = $request->input('offset', 0);  // Default offset to 0 if not provided
    $limit = $request->input('limit', 10);  // Default limit to 10 if not provided

    if (empty($language)) {
        return response()->json([
            'success' => false,
            'message' => 'Language is empty.',
        ], 200);
    }

    $totalCount = SpeechText::where('language', $language)->count();

    $speech_texts = SpeechText::where('language', $language)
        ->inRandomOrder()
        ->skip($offset)
        ->take($limit)
        ->get();

    if ($speech_texts->isEmpty()) {
        return response()->json([
            'success' => false,
            'message' => 'No Speech Text found for the specified language.',
            'total' => $totalCount,
        ], 200);
    }

    $speechTextData = $speech_texts->map(function ($speech_text) {
        return [
            'id' => $speech_text->id,
            'text' => $speech_text->text,
            'language' => $speech_text->language,
        ];
    });

    return response()->json([
        'success' => true,
        'message' => 'Speech Text listed successfully.',
        'total' => $totalCount,
        'data' => $speechTextData,
    ], 200);
}

public function female_users_list(Request $request)
{
    $authenticatedUser = auth('api')->user();
    if (!$authenticatedUser) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. Please provide a valid token.',
        ], 401);
    }

    $offset = $request->input('offset', 0);
    $limit = $request->input('limit', 10);

    $totalCount = Users::where('gender', 'female')->where('status', 2)->count();

    // Retrieve paginated female users with status = 1 and active in audio or video
    $Users = Users::where('gender', 'female')
        ->where('status', 2)
        ->where(function($query) {
            $query->where('audio_status', 1)
                  ->orWhere('video_status', 1);
        })
        ->orderBy('datetime', 'desc')
        ->skip($offset)
        ->take($limit)
        ->with('avatar') // Only eager load the avatar relationship if necessary
        ->get();

    if ($Users->isEmpty()) {
        return response()->json([
            'success' => false,
            'message' => 'No female users found.',
            'total' => $totalCount, // Include total count even if no data found
        ], 200);
    }

    $usersData = [];
    foreach ($Users as $user) {
        $avatar = $user->avatar; // Use the avatar relationship to get the avatar
        $gender = $avatar ? $avatar->gender : '';
        $imageUrl = $avatar && $avatar->image ? asset('storage/app/public/' . $avatar->image) : '';
        $voicePath = $user->voice ? asset('storage/app/public/voices/' . $user->voice) : '';

        $usersData[] = [
            'id' => $user->id,
            'name' => $user->name,
            'user_gender' => $user->gender,
            'avatar_id' => (int) $user->avatar_id,
            'image' => $imageUrl ?? '',
            'gender' => $gender,
            'language' => $user->language,
            'age' =>(int) $user->age ?? '',
            'mobile' => $user->mobile ?? '',
            'interests' => $user->interests ?? '',
            'describe_yourself' => $user->describe_yourself ?? '',
            'voice' => $voicePath ?? '',
            'status' => $user->status ?? '',
            'balance' =>(int) $user->balance ?? '',
            'audio_status' =>(int) $user->audio_status ?? '',
            'video_status' =>(int) $user->video_status ?? '',
            'datetime' => Carbon::parse($user->datetime)->format('Y-m-d H:i:s'),
            'updated_at' => Carbon::parse($user->updated_at)->format('Y-m-d H:i:s'),
            'created_at' => Carbon::parse($user->created_at)->format('Y-m-d H:i:s'),
        ];
    }

    return response()->json([
        'success' => true,
        'message' => 'Female users listed successfully.',
        'total' => $totalCount, // Include total count in the response
        'data' => $usersData,
    ], 200);
}

public function withdrawals_list(Request $request)
{

    $authenticatedUser = auth('api')->user();
    if (!$authenticatedUser) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. Please provide a valid token.',
        ], 401);
    }
      $user_id = $request->input('user_id');
    $offset = $request->input('offset', 0);  // Default offset to 0 if not provided
    $limit = $request->input('limit', 10);  // Default limit to 10 if not provided

        // Check if user_id is provided
        if (empty($user_id)) {
            return response()->json([
                'success' => false,
                'message' => 'user_id is empty.',
            ], 200);
        }
    
    // Retrieve the total count of withdrawals for the given user_id
    $totalCount = Withdrawals::where('user_id', $user_id)->count();

    // Retrieve paginated withdrawals for the given user_id
    $withdrawals = Withdrawals::where('user_id', $user_id)
                 ->orderBy('datetime', 'desc')
                 ->skip($offset)  // Apply offset for pagination
                 ->take($limit)   // Apply limit for pagination
                 ->get();

    // Check if any withdrawals exist for this user
    if ($withdrawals->isEmpty()) {
        return response()->json([
            'success' => false,
            'message' => 'No withdrawals found for this user.',
            'total' => $totalCount, // Include total count even if no data found
        ], 200);
    }

    // Prepare the withdrawal data
    $withdrawalsData = [];
    foreach ($withdrawals as $withdrawal) {
        $withdrawalsData[] = [
            'id' => $withdrawal->id,
            'user_id' =>(int) $withdrawal->user_id,
            'amount' =>(int) $withdrawal->amount,
            'status' => $withdrawal->status,
            'type' => $withdrawal->type,
            'datetime' => $withdrawal->datetime, // Assuming this field exists
            
        ];
    }

    return response()->json([
        'success' => true,
        'message' => 'Withdrawals listed successfully.',
        'total' => $totalCount, // Include total count in the response
        'data' => $withdrawalsData,
    ], 200);
}


public function calls_status_update(Request $request)
{
    $authenticatedUser = auth('api')->user();
    if (!$authenticatedUser) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. Please provide a valid token.',
        ], 401);
    }
      $user_id = $request->input('user_id');

    $call_type = $request->input('call_type'); // Should be 'audio' or 'video'
    $status = $request->input('status');       // Should be 1 or 0

    // Validate user_id
    if (empty($user_id)) {
        return response()->json([
            'success' => false,
            'message' => 'user_id is empty.',
        ], 200);
    }

    // Find the user
    $user = users::find($user_id);
    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'user not found.',
        ], 200);
    }

    if (empty($call_type)) {
        return response()->json([
            'success' => false,
            'message' => 'call_type is empty.',
        ], 200);
    }

    // Validate call_type
    if (!in_array($call_type, ['audio', 'video'])) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid call_type. It must be either "audio" or "video".',
        ], 200);
    }

    if (!isset($status) || !in_array($status, [0, 1])) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid status. It must be either 0 or 1.',
        ], 200);
    }

   
    if ($call_type === 'audio') {
        $user->audio_status = $status;
    } elseif ($call_type === 'video') {
        $user->video_status = $status;
    }

    $user->datetime = now(); 
    $user->save(); 

    // Fetch additional details for response
    $avatar = Avatars::find($user->avatar_id);
    $gender = $avatar ? $avatar->gender : '';

    $imageUrl = $avatar && $avatar->image 
        ? asset('storage/app/public/' . $avatar->image) : '';
    $voicePath = $user && $user->voice 
        ? asset('storage/app/public/voices/' . $user->voice) : '';

    return response()->json([
        'success' => true,
        'message' => 'Call status updated successfully.',
        'data' => [
            'id' => $user->id,
            'name' => $user->name,
            'user_gender' => $user->gender,
            'avatar_id' => (int) $user->avatar_id,
            'image' => $imageUrl ?? '',
            'gender' => $gender,
            'language' => $user->language,
            'age' => (int) $user->age ?? '',
            'mobile' => $user->mobile ?? '',
            'interests' => $user->interests ?? '',
            'describe_yourself' => $user->describe_yourself ?? '',
            'voice' => $voicePath,
            'status' => $user->status ?? '',
            'balance' => (int) $user->balance ?? '',
            'audio_status' =>(int) $user->audio_status ?? '',
            'video_status' =>(int) $user->video_status ?? '',
            'datetime' => Carbon::parse($user->datetime)->format('Y-m-d H:i:s'),
            'updated_at' => Carbon::parse($user->updated_at)->format('Y-m-d H:i:s'),
            'created_at' => Carbon::parse($user->created_at)->format('Y-m-d H:i:s'),
        ],
    ], 200);
}

public function call_female_user(Request $request)
{
    $authenticatedUser = auth('api')->user();
    if (!$authenticatedUser) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. Please provide a valid token.',
        ], 401);
    }
      $user_id = $request->input('user_id');
    $call_user_id = $request->input('call_user_id');
    $call_type = $request->input('call_type');

    // Validate user_id
    if (empty($user_id)) {
        return response()->json([
            'success' => false,
            'message' => 'user_id is empty.',
        ], 200);
    }

    // Find the user
    $user = users::find($user_id);
    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'user not found.',
        ], 200);
    }

    // Validate call_user_id
    if (empty($call_user_id)) {
        return response()->json([
            'success' => false,
            'message' => 'call_user_id is empty.',
        ], 200);
    }

    // Ensure user_id and call_user_id are not the same
    if ($user_id == $call_user_id) {
        return response()->json([
            'success' => false,
            'message' => 'user cannot call themselves.',
        ], 200);
    }

    // Find the call user
    $call_user = users::find($call_user_id);
    if (!$call_user) {
        return response()->json([
            'success' => false,
            'message' => 'Call user not found.',
        ], 200);
    }

    // Validate call_type
    if (empty($call_type)) {
        return response()->json([
            'success' => false,
            'message' => 'call_type is empty.',
        ], 200);
    }

    if (!in_array($call_type, ['audio', 'video'])) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid call_type. It must be either "audio" or "video".',
        ], 200);
    }

    if ($call_type == 'video' && $user->coins < 60) {
        return response()->json([
            'success' => false,
            'message' => 'Insufficient coins for video call. Minimum 60 coins required.',
        ], 200);
    } elseif ($call_type == 'audio' && $user->coins < 10) {
        return response()->json([
            'success' => false,
            'message' => 'Insufficient coins for audio call. Minimum 10 coins required.',
        ], 200);
    }

    $balance_time = '';
    $coins = $user->coins;
    
    // Calculate balance time in minutes and seconds
    $minutes = floor($coins / 10); // For every 10 coins, 1 minute
    $seconds = 0; // Assume no partial seconds for simplicity
    $balance_time = sprintf('%d:%02d', $minutes, $seconds);


    // Insert call data into users_call table
    $usersCalls = UserCalls::create([
        'user_id' => $user->id,
        'call_user_id' => $call_user_id,
        'type' => $call_type,
        'datetime' => now(),
    ]);

    // Fetch inserted call data
    $insertedCallData = UserCalls::find($usersCalls->id);

    // Fetch names of the users from the users table
    $caller = users::find($insertedCallData->user_id);
    $receiver = users::find($insertedCallData->call_user_id);


    // Fetch avatar image for receiver
    $receiverAvatar = Avatars::find($receiver->avatar_id);
    $receiverImageUrl = ($receiverAvatar && $receiverAvatar->image) ? asset('storage/app/public/' . $receiverAvatar->image) : '';

       // Fetch avatar image for caller if needed
       $callerAvatar = Avatars::find($caller->avatar_id);
       $callerImageUrl = ($callerAvatar && $callerAvatar->image) ? asset('storage/app/public/' . $callerAvatar->image) : '';
   
   
    // Return response with success and inserted call data
    return response()->json([
        'success' => true,
        'message' => 'Data created successfully.',
        'data' => [
            'call_id' => $insertedCallData->id,
            'user_id' => $insertedCallData->user_id,
            'user_name' => $caller ? $caller->name : '',
            'user_avatar_image' => $callerImageUrl,
            'call_user_id' => $insertedCallData->call_user_id,
            'call_user_name' => $receiver ? $receiver->name : '',
            'call_user_avatar_image' => $receiverImageUrl,
            'type' => $insertedCallData->type,
            'started_time' => $insertedCallData->started_time ?? '',
            'ended_time' => $insertedCallData->ended_time ?? '',
            'coins_spend' => $insertedCallData->coins_spend ?? '',
            'income' => $insertedCallData->income?? '',
            'balance_time' => $balance_time,
            'date_time' => Carbon::parse($insertedCallData->date_time)->format('Y-m-d H:i:s'),
        ],
    ], 200);
}
public function random_user(Request $request)
{
    $authenticatedUser = auth('api')->user();
    if (!$authenticatedUser) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. Please provide a valid token.',
        ], 401);
    }
    $user_id = $request->input('user_id');
    $call_type = $request->input('call_type'); // Should be 'audio' or 'video'

    // Validate user_id
    if (empty($user_id)) {
        return response()->json([
            'success' => false,
            'message' => 'user_id is empty.',
        ], 200);
    }

    // Find the user
    $user = users::find($user_id);
    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'user not found.',
        ], 200);
    }

    if (empty($call_type)) {
        return response()->json([
            'success' => false,
            'message' => 'call_type is empty.',
        ], 200);
    }

    // Validate call_type
    if (!in_array($call_type, ['audio', 'video'])) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid call_type. It must be either "audio" or "video".',
        ], 200);
    }

    if ($call_type == 'video' && $user->coins < 60) {
        return response()->json([
            'success' => false,
            'message' => 'Insufficient coins for video call. Minimum 60 coins required.',
        ], 200);
    } elseif ($call_type == 'audio' && $user->coins < 10) {
        return response()->json([
            'success' => false,
            'message' => 'Insufficient coins for audio call. Minimum 10 coins required.',
        ], 200);
    }

    $balance_time = '';
    $coins = $user->coins;

    // Calculate balance time in minutes and seconds
    $minutes = floor($coins / 10); // For every 10 coins, 1 minute
    $seconds = 0; // Assume no partial seconds for simplicity
    $balance_time = sprintf('%d:%02d', $minutes, $seconds);

      // Filter female users with status = 1 based on call_type and exclude users from not_repeat_call_users
      $query = users::where('gender', 'female')
      ->where('id', '!=', $user_id)
      ->whereNotIn('id', function ($subquery) use ($user_id) {
          $subquery->select('call_user_id')
                   ->from('not_repeat_call_users')
                   ->where('user_id', $user_id);
      });

    if ($call_type == 'video') {
        $query->where('video_status', 1);
    } else { // 'audio'
        $query->where('audio_status', 1);
    }

    // Fetch random user
    $randomFemaleuser = $query->inRandomOrder()->first();

    // If no users are found, return a busy message
    if (!$randomFemaleuser) {
        return response()->json([
            'success' => false,
            'message' => 'users are busy right now.',
        ], 200);
    }

    // Insert call data into users_call table
    $usersCalls = UserCalls::create([
        'user_id' => $user->id,
        'call_user_id' => $randomFemaleuser->id,
        'type' => $call_type,
        'datetime' => now(),
    ]);

    // Fetch inserted call data
    $insertedCallData = UserCalls::find($usersCalls->id);

    // Fetch names of the users from the users table
    $caller = users::find($insertedCallData->user_id);
    $receiver = users::find($insertedCallData->call_user_id);

    // Fetch avatar image for receiver
    $receiverAvatar = Avatars::find($receiver->avatar_id);
    $receiverImageUrl = ($receiverAvatar && $receiverAvatar->image) ? asset('storage/app/public/' . $receiverAvatar->image) : '';

    // Fetch avatar image for caller if needed
    $callerAvatar = Avatars::find($caller->avatar_id);
    $callerImageUrl = ($callerAvatar && $callerAvatar->image) ? asset('storage/app/public/' . $callerAvatar->image) : '';

    // Return response with success and inserted call data
    return response()->json([
        'success' => true,
        'message' => 'Data created successfully.',
        'data' => [
            'call_id' => $insertedCallData->id,
            'user_id' => $insertedCallData->user_id,
            'user_name' => $caller ? $caller->name : '',
            'user_avatar_image' => $callerImageUrl,
            'call_user_id' => $insertedCallData->call_user_id,
            'call_user_name' => $receiver ? $receiver->name : '',
            'call_user_avatar_image' => $receiverImageUrl,
            'type' => $insertedCallData->type,
            'started_time' => $insertedCallData->started_time ?? '',
            'ended_time' => $insertedCallData->ended_time ?? '',
            'coins_spend' => $insertedCallData->coins_spend ?? '',
            'income' => $insertedCallData->income ?? '',
            'balance_time' => $balance_time,
            'date_time' => Carbon::parse($insertedCallData->date_time)->format('Y-m-d H:i:s'),
        ],
    ], 200);
}

public function update_connected_call(Request $request)
{
    $authenticatedUser = auth('api')->user();
    if (!$authenticatedUser) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. Please provide a valid token.',
        ], 401);
    }

    $user_id = $request->input('user_id');
    $call_id = $request->input('call_id'); 
    $started_time = $request->input('started_time'); 
    $ended_time = $request->input('ended_time'); 

    // Validate user_id
    if (empty($user_id)) {
        return response()->json([
            'success' => false,
            'message' => 'user_id is empty.',
        ], 200);
    }
    $user = users::find($user_id);
    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'User not found for the provided user_id.',
        ], 200);
    }

    if (empty($call_id)) {
        return response()->json([
            'success' => false,
            'message' => 'call_id is empty.',
        ], 200);
    }

    if (empty($started_time)) {
        return response()->json([
            'success' => false,
            'message' => 'started_time is empty.',
        ], 200);
    }

    if (empty($ended_time)) {
        return response()->json([
            'success' => false,
            'message' => 'ended_time is empty.',
        ], 200);
    }

    $timeFormat = 'H:i:s';
    
    if (!Carbon::hasFormat($started_time, $timeFormat)) {
        return response()->json([
            'success' => false, 
            'message' => 'started_time must be in H:i:s format (e.g., 14:00:00).'
        ], 200);
    }
    
    if (!Carbon::hasFormat($ended_time, $timeFormat)) {
        return response()->json([
            'success' => false, 
            'message' => 'ended_time must be in H:i:s format (e.g., 14:00:00).'
        ], 200);
    }

    $call = UserCalls::where('id', $call_id)->first();

    if (!$call) {
        return response()->json([
            'success' => false,
             'message' => 'Call not found.'
        ], 200);
    }

    if ($call->user_id != $user_id) {
        return response()->json([
            'success' => false,
             'message' => 'No matching record found for the provided call_id and user_id.'
        ], 200);
    }

    if (!empty($call->ended_time)) {
        return response()->json([
            'success' => false, 
            'message' => 'Call has already been updated.'
        ], 200);
    }
    $user = users::find($user_id);

    // Convert the times to Carbon instances with today's date
    $currentDate = Carbon::now()->format('Y-m-d'); 
    $startTime = Carbon::createFromFormat('Y-m-d H:i:s', "$currentDate $started_time");
    $endTime = Carbon::createFromFormat('Y-m-d H:i:s', "$currentDate $ended_time");

    // Calculate the duration in seconds
    $durationSeconds = $endTime->diffInSeconds($startTime);


    // Handle calls with less than 10 seconds duration
    if ($durationSeconds < 10) {
        DB::table('not_repeat_call_users')->insert([
            'user_id' => $user_id,
            'call_user_id' => $call->call_user_id,
            'reason' => 'Duration less than 10 seconds',
            'datetime' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }
    $callType = $call->type; // Assuming 'type' field in 'UserCalls' table is either 'audio' or 'video'

    // Calculate duration in minutes (ensure at least 1 minute)
    $durationMinutes = max($endTime->diffInMinutes($startTime), 1);

    
    if ($callType == 'audio') {
        $coinsPerMinute = 10;
        $incomePerMinute = 2;
    } elseif ($callType == 'video') {
        $coinsPerMinute = 60;
        $incomePerMinute = 10;
    }

    $coins_spend = $durationMinutes * $coinsPerMinute;
    $income = $durationMinutes * $incomePerMinute;

      // Only deduct coins if the duration is 10 seconds or more
      if ($durationSeconds >= 10) {
        $user->coins -= $coins_spend; // Deduct coins only if duration >= 10 seconds
        $user->save();
    } else {
        $coins_spend = 0; // No coins deducted if duration is less than 10 seconds
        $income = 0; // No coins deducted if duration is less than 10 seconds
    }

    // Update the balance of the call_user_id user
    $callUser = Users::find($call->call_user_id);
    if ($callUser) {
        $callUser->balance += $income;
        $callUser->total_income += $income;
        $callUser->save();

        // Record the transaction for the call_user_id user
        $transaction = new Transactions();
        $transaction->user_id = $callUser->id;
        $transaction->coins = 0;
        $transaction->type = 'call_income';
        $transaction->amount = $income; // Assuming no monetary amount for call income
        $transaction->datetime = now();
        $transaction->save();
    }

    // Update call details
    $call->started_time = $startTime->format('H:i:s');
    $call->ended_time = $endTime->format('H:i:s'); 
    $call->coins_spend = $coins_spend;
    $call->income = $income;
    $call->save();

    $receiver = users::find($call->call_user_id);

    return response()->json([
        'success' => true,
        'message' => 'Connected call updated successfully.',
        'data' => [
            'call_id' => $call->id,
            'user_id' => $call->user_id,
            'user_name' => $user->name,
            'call_user_id' => $call->call_user_id,
            'call_user_name' => $receiver ? $receiver->name : '',
            'coins_spend' => $call->coins_spend,
            'income' => $call->income,
            'started_time' => $call->started_time,
            'ended_time' => $call->ended_time,
            'date_time' => Carbon::parse($call->datetime)->format('Y-m-d H:i:s'),
        ],
    ], 200);
}

public function individual_update_connected_call(Request $request)
{
    $authenticatedUser = auth('api')->user();
    if (!$authenticatedUser) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. Please provide a valid token.',
        ], 401);
    }
    $user_id = $request->input('user_id');
    $call_id = $request->input('call_id'); 
    $started_time = $request->input('started_time'); 
    $ended_time = $request->input('ended_time'); 

    // Validate user_id
    if (empty($user_id)) {
        return response()->json([
            'success' => false,
            'message' => 'user_id is empty.',
        ], 200);
    }
    $user = users::find($user_id);
    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'User not found for the provided user_id.',
        ], 200);
    }

    if (empty($call_id)) {
        return response()->json([
            'success' => false,
            'message' => 'call_id is empty.',
        ], 200);
    }

    if (empty($started_time)) {
        return response()->json([
            'success' => false,
            'message' => 'started_time is empty.',
        ], 200);
    }

    if (empty($ended_time)) {
        return response()->json([
            'success' => false,
            'message' => 'ended_time is empty.',
        ], 200);
    }

    $timeFormat = 'H:i:s';
    
    if (!Carbon::hasFormat($started_time, $timeFormat)) {
        return response()->json([
            'success' => false, 
            'message' => 'started_time must be in H:i:s format (e.g., 14:00:00).'
        ], 200);
    }
    
    if (!Carbon::hasFormat($ended_time, $timeFormat)) {
        return response()->json([
            'success' => false, 
            'message' => 'ended_time must be in H:i:s format (e.g., 14:00:00).'
        ], 200);
    }

    $call = UserCalls::where('id', $call_id)->first();

    if (!$call) {
        return response()->json([
            'success' => false,
             'message' => 'Call not found.'
        ], 200);
    }

    if ($call->user_id != $user_id) {
        return response()->json([
            'success' => false,
             'message' => 'No matching record found for the provided call_id and user_id.'
        ], 200);
    }

    $user = users::find($user_id);

    // Convert the times to Carbon instances with today's date
    $currentDate = Carbon::now()->format('Y-m-d'); // Current date
    $startTime = Carbon::createFromFormat('Y-m-d H:i:s', "$currentDate $started_time"); // Add the date
    $endTime = Carbon::createFromFormat('Y-m-d H:i:s', "$currentDate $ended_time"); // Add the date

    // Calculate the duration in seconds
    $durationSeconds = $endTime->diffInSeconds($startTime);

    // Handle calls with less than 10 seconds duration
    if ($durationSeconds < 10) {
        DB::table('not_repeat_call_users')->insert([
            'user_id' => $user_id,
            'call_user_id' => $call->call_user_id,
            'reason' => 'Duration less than 10 seconds',
            'datetime' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    $callType = $call->type; // Assuming 'type' field in 'UserCalls' table is either 'audio' or 'video'

    // Calculate duration in minutes (ensure at least 1 minute)
    $durationMinutes = max($endTime->diffInMinutes($startTime), 1);

    if ($callType == 'audio') {
        $coinsPerMinute = 10;
        $incomePerMinute = 2;
    } elseif ($callType == 'video') {
        $coinsPerMinute = 60;
        $incomePerMinute = 10;
    }

    $coins_spend = $durationMinutes * $coinsPerMinute;
    $income = $durationMinutes * $incomePerMinute;

    // Only deduct coins if the duration is 10 seconds or more
    if ($durationSeconds >= 10) {
        $user->coins -= $coins_spend; // Deduct coins only if duration >= 10 seconds
        $user->save();
    } else {
        $coins_spend = 0; // No coins deducted if duration is less than 10 seconds
        $income = 0; // No coins deducted if duration is less than 10 seconds
    }

   
    // Update the balance of the call_user_id user
    $callUser = Users::find($call->call_user_id);
    if ($callUser) {
        $callUser->balance += $income;
        $callUser->total_income += $income;
        $callUser->save();

        // Record the transaction for the call_user_id user
        $transaction = new Transactions();
        $transaction->user_id = $callUser->id;
        $transaction->coins = 0;
        $transaction->type = 'call_income';
        $transaction->amount = $income; // Assuming no monetary amount for call income
        $transaction->datetime = now();
        $transaction->save();
    }

    // Update call details
    $call->started_time = $startTime->format('H:i:s');
    $call->ended_time = $endTime->format('H:i:s'); 
    $call->coins_spend = $coins_spend;
    $call->income = $income;
    $call->save();

    $receiver = users::find($call->call_user_id);

    return response()->json([
        'success' => true,
        'message' => 'Individual Connected call updated successfully.',
        'data' => [
            'call_id' => $call->id,
            'user_id' => $call->user_id,
            'user_name' => $user->name,
            'call_user_id' => $call->call_user_id,
            'call_user_name' => $receiver ? $receiver->name : '',
            'coins_spend' => $call->coins_spend,
            'income' => $call->income,
            'started_time' => $call->started_time,
            'ended_time' => $call->ended_time,
            'date_time' => Carbon::parse($call->datetime)->format('Y-m-d H:i:s'),
        ],
    ], 200);
}
public function calls_list(Request $request)
{
    $authenticatedUser = auth('api')->user();
    if (!$authenticatedUser) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. Please provide a valid token.',
        ], 401);
    }

    $user_id = $request->input('user_id');
    $gender = $request->input('gender');
    $offset = (int) $request->input('offset', 0); // Default offset to 0
    $limit = (int) $request->input('limit', 10); // Default limit to 10

    // Validate user_id
    if (empty($user_id)) {
        return response()->json([
            'success' => false,
            'message' => 'user_id is empty.',
        ], 200);
    }

    // Find the user
    $user = Users::find($user_id);
    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'User not found.',
        ], 200);
    }

    if (empty($gender)) {
        return response()->json([
            'success' => false,
            'message' => 'gender is empty.',
        ], 200);
    }

    // Validate gender
    if (!in_array($gender, ['male', 'female'])) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid gender. It must be either "male" or "female".',
        ], 200);
    }

    // Query calls based on gender
    $validCalls = [];
    if ($gender === 'male') {
        // Male: Get calls where user_id matches
        $callsQuery = UserCalls::where('user_id', $user_id)
            ->whereNotNull('started_time')
            ->where('started_time', '!=', '')
            ->orderBy('datetime', 'desc'); // Order by datetime

        // Fetch all calls and filter valid ones
        $calls = $callsQuery->get();
        foreach ($calls as $call) {
            $receiver = Users::find($call->call_user_id);
            if ($receiver) {
                $validCalls[] = $call;
            }
        }
    } else {
        // Female: Get calls where call_user_id matches
        $callsQuery = UserCalls::where('call_user_id', $user_id)
            ->whereNotNull('started_time')
            ->where('started_time', '!=', '')
            ->orderBy('datetime', 'desc'); // Order by datetime

        // Fetch all calls and filter valid ones
        $calls = $callsQuery->get();
        foreach ($calls as $call) {
            $caller = Users::find($call->user_id);
            if ($caller) {
                $validCalls[] = $call;
            }
        }
    }

    // Calculate total valid calls
    $totalCalls = count($validCalls);

    // Apply offset and limit to valid calls
    $calls = array_slice($validCalls, $offset, $limit);

    // Check if no calls found
    if (empty($calls)) {
        return response()->json([
            'success' => false,
            'message' => 'Data not found.',
        ], 200);
    }

    // Prepare the call data
    $callData = [];
    foreach ($calls as $call) {
        // Calculate duration
        $duration = '';
        if ($call->started_time && $call->ended_time) {
            $startTime = Carbon::parse($call->started_time);
            $endTime = Carbon::parse($call->ended_time);
            $durationSeconds = $startTime->diffInSeconds($endTime);
            $durationMinutes = ceil($durationSeconds / 60);
            $duration = sprintf('%d min', $durationMinutes);
        }

        // Prepare avatar and image URL
        $avatar = null;
        $imageUrl = '';
        if ($gender === 'male') {
            $receiver = Users::find($call->call_user_id);
            $avatar = Avatars::find($receiver->avatar_id);
            $imageUrl = ($avatar && $avatar->image) ? asset('storage/app/public/' . $avatar->image) : '';
        } elseif ($gender === 'female') {
            $caller = Users::find($call->user_id);
            $avatar = Avatars::find($caller->avatar_id);
            $imageUrl = ($avatar && $avatar->image) ? asset('storage/app/public/' . $avatar->image) : '';
        }

        // Add data to response array
        if ($gender === 'male') {
            $receiver = Users::find($call->call_user_id);
            $callData[] = [
                'id' => $call->call_user_id,
                'name' => $receiver ? $receiver->name : '',
                'image' => $imageUrl,
                'started_time' => $call->started_time ?? '',
                'duration' => $duration,
                'audio_status' => $receiver->audio_status ?? '',
                'video_status' => $receiver->video_status ?? '',
            ];
        } elseif ($gender === 'female') {
            $caller = Users::find($call->user_id);
            $callData[] = [
                'id' => $call->user_id,
                'name' => $caller ? $caller->name : '',
                'image' => $imageUrl,
                'started_time' => $call->started_time ?? '',
                'duration' => $duration,
                'income' => $call->income ?? '',
            ];
        }
    }

    // Return the response with valid data
    return response()->json([
        'success' => true,
        'message' => 'Calls listed successfully.',
        'total' => $totalCalls,
        'data' => $callData,
    ], 200);
}

public function female_call_attend(Request $request)
{
    $authenticatedUser = auth('api')->user();
    if (!$authenticatedUser) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. Please provide a valid token.',
        ], 401);
    }
    // Retrieve input values
    $call_id = $request->input('call_id');
    $user_id = $request->input('user_id');
    $started_time = $request->input('started_time');

    if (empty($call_id)) {
        return response()->json([
            'success' => false,
            'message' => 'call_id is empty.',
        ], 200);
    }

    if (empty($user_id)) {
        return response()->json([
            'success' => false,
            'message' => 'user_id is empty.',
        ], 200);
    }
    $user = users::find($user_id);
    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'User not found for the provided user_id.',
        ], 200);
    }

    $timeFormat = 'H:i:s';
    if (!Carbon::hasFormat($started_time, $timeFormat)) {
        return response()->json([
            'success' => false,
            'message' => 'started_time must be in H:i:s format (e.g., 14:00:00).',
        ], 200);
    }

    // Check if the call_id and user_id match in user_calls table
    $userCall = UserCalls::where('id', $call_id)
                         ->where('user_id', $user_id)
                         ->first();

    if (!$userCall) {
        return response()->json([
            'success' => false,
            'message' => 'No matching record found for the provided call_id and user_id.',
        ], 200);
    }
    if (!empty($userCall->started_time)) {
        return response()->json([
            'success' => false,
            'message' => 'started_time has already been updated.',
        ], 200);
    }

    // Update the started_time
    $userCall->started_time = $started_time;
    $userCall->save();

    // Find the user and fetch balance time
    $user = Users::find($user_id);
    $coins = $user ? $user->coins : 0;
    $minutes = floor($coins / 10); // For every 10 coins, 1 minute
    $seconds = 0;
    $balance_time = sprintf('%d:%02d', $minutes, $seconds);

    // Fetch names and avatar images for caller and receiver
    $caller = Users::find($userCall->user_id);
    $receiver = Users::find($userCall->call_user_id);

    $callerAvatar = Avatars::find($caller->avatar_id);
    $callerImageUrl = ($callerAvatar && $callerAvatar->image) ? asset('storage/app/public/' . $callerAvatar->image) : '';

    $receiverAvatar = Avatars::find($receiver->avatar_id);
    $receiverImageUrl = ($receiverAvatar && $receiverAvatar->image) ? asset('storage/app/public/' . $receiverAvatar->image) : '';

    // Return response
    return response()->json([
        'success' => true,
        'message' => 'started_time updated successfully.',
        'data' => [
            'call_id' => $userCall->id,
            'user_id' => $userCall->user_id,
            'user_name' => $caller ? $caller->name : '',
            'user_avatar_image' => $callerImageUrl,
            'call_user_id' => $userCall->call_user_id,
            'call_user_name' => $receiver ? $receiver->name : '',
            'call_user_avatar_image' => $receiverImageUrl,
            'type' => $userCall->type,
            'started_time' => $userCall->started_time,
            'ended_time' => $userCall->ended_time ?? '',
            'coins_spend' => $userCall->coins_spend ?? '',
            'income' => $userCall->income ?? '',
            'remaining_time' => $balance_time,
            'date_time' => Carbon::parse($userCall->date_time)->format('Y-m-d H:i:s'),
        ],
    ], 200);
}

public function get_remaining_time(Request $request)
{
    $authenticatedUser = auth('api')->user();
    if (!$authenticatedUser) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. Please provide a valid token.',
        ], 401);
    }
    $user_id = $request->input('user_id');
    $call_type = $request->input('call_type');


    if (empty($user_id)) {
        return response()->json([
            'success' => false,
            'message' => 'user_id is empty.',
        ], 200);
    }
    $user = users::find($user_id);
    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'User not found for the provided user_id.',
        ], 200);
    }

    if (empty($call_type)) {
        return response()->json([
            'success' => false,
            'message' => 'call_type is empty.',
        ], 200);
    }

    // Validate call_type
    if (!in_array($call_type, ['audio', 'video'])) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid call_type. It must be either "audio" or "video".',
        ], 200);
    }

    // Find the user and fetch balance time
    $user = Users::find($user_id);
    $coins = $user ? $user->coins : 0;
    $minutes = floor($coins / 10); // For every 10 coins, 1 minute
    $seconds = 0;
    $balance_time = sprintf('%d:%02d',$minutes, $seconds);

    // Return response
    return response()->json([
        'success' => true,
        'message' => 'Remaining Time Listed successfully.',
        'data' => [
            'remaining_time' => $balance_time,

        ],
    ], 200);
}

public function reports(Request $request)
{
    $authenticatedUser = auth('api')->user();
    if (!$authenticatedUser) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. Please provide a valid token.',
        ], 401);
    }

    // Use the authenticated user's ID
    $user_id = $request->input('user_id');

    // Fetch the user based on authenticated user's ID to check if the user is female
    $user = users::find($user_id);

    if (!$user) {
        return response()->json([ 
            'success' => false,
            'message' => 'User not found.',
        ], 200);
    }

    // Check if the user is female
    if ($user->gender !== 'female') {
        return response()->json([
            'success' => false,
            'message' => 'User is not female.',
        ], 200);
    }


    $callCount = UserCalls::where('call_user_id', $user_id)
        ->whereDate('datetime', now()->toDateString())
        ->count();

    // Get the total earnings today for this user
    $today_earnings = UserCalls::where('call_user_id', $user_id)
        ->whereDate('datetime', now()->toDateString())
        ->sum('income');

    // Prepare and return the response with the data
    return response()->json([
        'success' => true,
        'message' => 'Reports listed successfully.',
        'data' => [[
            'user_name' => $user->name,
            'today_calls' => $callCount,
            'today_earnings' => $today_earnings,
        ]],
    ], 200);
}
public function update_bank(Request $request)
{
    $authenticatedUser = auth('api')->user();
    if (!$authenticatedUser) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. Please provide a valid token.',
        ], 401);
    }
 
    $user_id = $request->input('user_id');
    $bank = $request->input('bank');
    $account_num = $request->input('account_num');
    $branch = $request->input('branch');
    $ifsc = $request->input('ifsc');
    $holder_name = $request->input('holder_name');

    if (empty($user_id)) {
        return response()->json([
            'success' => false,
            'message' => 'user_id is empty.',
        ], 200);
    }
    if (empty($bank)) {
        return response()->json([
            'success' => false,
            'message' => 'bank is empty.',
        ], 200);
    }

    if (empty($account_num)) {
        return response()->json([
            'success' => false,
            'message' => 'account_num is empty.',
        ], 200);
    }

    if (empty($branch)) {
        return response()->json([
            'success' => false,
            'message' => 'branch is empty.',
        ], 200);
    }

    if (empty($ifsc)) {
        return response()->json([
            'success' => false,
            'message' => 'ifsc is empty.',
        ], 200);
    }

    if (empty($holder_name)) {
        return response()->json([
            'success' => false,
            'message' => 'holder_name is empty.',
        ], 200);
    }

    $user = Users::find($user_id);

    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'user not found.',
        ], 200);
    }

    $user->bank = $bank;
    $user->account_num = $account_num;
    $user->branch = $branch;
    $user->ifsc = $ifsc;
    $user->holder_name = $holder_name;
    $user->datetime = now();
    $user->save();

    $upi = upis::where('user_id', $user_id)->first(); 
    $upi_id = $upi ? $upi->upi_id : "";

    $avatar = Avatars::find($user->avatar_id);
    $gender = $avatar ? $avatar->gender : '';

    $imageUrl = ($avatar && $avatar->image) ? asset('storage/app/public/' . $avatar->image) : '';
    $voicePath = $user && $user->voice ? asset('storage/app/public/voices/' . $user->voice) : '';

    return response()->json([
        'success' => true,
        'message' => 'User details updated successfully.',
        'data' => [
            'id' => $user->id,
            'name' => $user->name,
            'user_gender' => $user->gender,
            'language' => $user->language,
            'mobile' => $user->mobile,
            'avatar_id' => (int) $user->avatar_id,
            'image' => $imageUrl ?? '',
            'gender' => $gender,
            'age' => (int) $user->age ?? '',
            'interests' => $user->interests,
            'describe_yourself' => $user->describe_yourself ?? '',
            'voice' => $voicePath ?? '',
            'status' => $user->status ?? '',
            'balance' => (int) $user->balance ?? '',
            'coins' => (int) $user->coins ?? '',
            'audio_status' => (int) $user->audio_status ?? '',
            'video_status' => (int) $user->video_status ?? '',
            'bank' => $user->bank,
            'account_num' => $user->account_num,
            'branch' => $user->branch,
            'ifsc' => $user->ifsc,
            'holder_name' => $user->holder_name,
            'upi_id' => $upi_id,
            'datetime' => Carbon::parse($user->datetime)->format('Y-m-d H:i:s'),
            'updated_at' => Carbon::parse($user->updated_at)->format('Y-m-d H:i:s'),
            'created_at' => Carbon::parse($user->created_at)->format('Y-m-d H:i:s'),
        ],
    ], 200);
}

public function update_upi(Request $request)
{
    $authenticatedUser = auth('api')->user();
    if (!$authenticatedUser) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. Please provide a valid token.',
        ], 401);
    }
    $user_id = $request->input('user_id');
    $upi_id = $request->input('upi_id');

    if (empty($upi_id)) {
        return response()->json([
            'success' => false,
            'message' => 'upi_id is empty.',
        ], 200);
    }
    if (empty($user_id)) {
        return response()->json([
            'success' => false,
            'message' => 'user_id is empty.',
        ], 200);
    }

    $user = Users::find($user_id);

    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'user not found.',
        ], 200);
    }

    // Update or create UPI record
    $upi = upis::updateOrCreate(
        ['user_id' => $user_id],
        ['upi_id' => $upi_id]
    );

    $user->datetime = now();
    $user->save();

    $avatar = Avatars::find($user->avatar_id);
    $gender = $avatar ? $avatar->gender : '';

    $imageUrl = ($avatar && $avatar->image) ? asset('storage/app/public/' . $avatar->image) : '';
    $voicePath = $user && $user->voice ? asset('storage/app/public/voices/' . $user->voice) : '';

    return response()->json([
        'success' => true,
        'message' => 'UPI ID updated successfully.',
        'data' => [
            'id' => $user->id,
            'name' => $user->name,
            'user_gender' => $user->gender,
            'language' => $user->language,
            'mobile' => $user->mobile,
            'avatar_id' => (int) $user->avatar_id,
            'image' => $imageUrl ?? '',
            'gender' => $gender,
            'age' => (int) $user->age ?? '',
            'interests' => $user->interests,
            'describe_yourself' => $user->describe_yourself ?? '',
            'voice' => $voicePath ?? '',
            'status' => $user->status ?? '',
            'balance' => (int) $user->balance ?? '',
            'coins' => (int) $user->coins ?? '',
            'audio_status' => (int) $user->audio_status ?? '',
            'video_status' => (int) $user->video_status ?? '',
            'bank' => $user->bank,
            'account_num' => $user->account_num,
            'branch' => $user->branch,
            'ifsc' => $user->ifsc,
            'holder_name' => $user->holder_name,
            'upi_id' => $upi->upi_id,
            'datetime' => Carbon::parse($user->datetime)->format('Y-m-d H:i:s'),
            'updated_at' => Carbon::parse($user->updated_at)->format('Y-m-d H:i:s'),
            'created_at' => Carbon::parse($user->created_at)->format('Y-m-d H:i:s'),
        ],
    ], 200);
}
public function withdrawals(Request $request)
{
    // Authenticate user
    $authenticatedUser = auth('api')->user();
    if (!$authenticatedUser) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. Please provide a valid token.',
        ], 401);
    }

    // Retrieve the user ID from the request
    $user_id = $request->input('user_id');
    $amount = $request->input('amount');
    $type = $request->input('type');

    // Validate input fields
    if (empty($amount)) {
        return response()->json([
            'success' => false,
            'message' => 'Amount is empty.',
        ], 200);
    }
    
    if (empty($user_id)) {
        return response()->json([
            'success' => false,
            'message' => 'User ID is required.',
        ], 200);
    }
     // Retrieve the user by ID
     $user = Users::find($user_id);

     if (!$user) {
         return response()->json([
             'success' => false,
             'message' => 'User not found.',
         ], 404);
     }

    if (!is_numeric($amount) || $amount <= 0) {
        return response()->json([
            'success' => false,
            'message' => 'Amount must be a positive number.',
        ], 200);
    }

    if (empty($type)) {
        return response()->json([
            'success' => false,
            'message' => 'Type (bank_transfer or upi_transfer) is required.',
        ], 200);
    }

    if (!in_array($type, ['bank_transfer', 'upi_transfer'])) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid transfer type. Use either "bank_transfer" or "upi_transfer".',
        ], 200);
    }

    // Check user's balance
    if ($user->balance < $amount) {
        return response()->json([
            'success' => false,
            'message' => 'Insufficient balance.',
        ], 200);
    }

    // Check for pending withdrawals
    $pendingWithdrawal = Withdrawals::where('user_id', $user_id)
                                     ->where('status', 0) // Pending status
                                     ->first();

    if ($pendingWithdrawal) {
        return response()->json([
            'success' => false,
            'message' => 'Please wait, your existing withdrawal is pending.',
        ], 200);
    }

    // Handle bank transfer
    if ($type === 'bank_transfer') {
        if (empty($user->account_num) || empty($user->holder_name) || empty($user->bank) || empty($user->branch) || empty($user->ifsc)) {
            return response()->json([
                'success' => false,
                'message' => 'Please update your bank details before making a withdrawal.',
            ], 200);
        }
    }

    // Handle UPI transfer
    if ($type === 'upi_transfer') {
        $upi = upis::where('user_id', $user_id)->first();

        if (!$upi || empty($upi->upi_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Please update your UPI ID before making a withdrawal.',
            ], 200);
        }
        $deductedAmount = $amount - ($amount * 0.05);  // Deduct 5% from the withdrawal amount
        $amount = $deductedAmount;
    }

    // Deduct the withdrawal amount from the user's balance
    $user->balance -= $amount;
    $user->save();

    // Create the withdrawal record
    Withdrawals::create([
        'user_id' => $user_id,
        'amount' => $amount,
        'datetime' => now(),
        'status' => 0, // Pending
        'type' => $type,
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Withdrawal request submitted successfully.',
        'balance' => $user->balance,
    ], 200);
}


public function ratings(Request $request)
{
    $authenticatedUser = auth('api')->user();
    if (!$authenticatedUser) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. Please provide a valid token.',
        ], 401);
    }

    $user_id = $request->input('user_id');
    $call_user_id = $request->input('call_user_id');
    $ratings = $request->input('ratings');
    $description = $request->input('description');
    $title = $request->input('title');

    // Validate input
    if (empty($user_id)) {
        return response()->json([
            'success' => false, 
            'message' => 'user_id is empty.'
        ], 200);
    }
    if (empty($call_user_id)) {
        return response()->json([
            'success' => false,
             'message' => 'call_user_id is empty.'
        ], 200);
    }
    if (empty($ratings)) {
        return response()->json([
            'success' => false, 
            'message' => 'ratings is empty.'
        ], 200);
    }
    if (empty($title)) {
        return response()->json([
            'success' => false, 
            'message' => 'title is empty.'
        ], 200);
    }
    if (empty($description)) {
        return response()->json([
            'success' => false,
             'message' => 'description is empty.'
        ], 200);
    }

    // Validate users
    $user = Users::find($user_id);
    $callUser = Users::find($call_user_id);

    if (!$user) {
        return response()->json([
            'success' => false, 
            'message' => 'User not found.'
        ], 200);
    }
    if (!$callUser) {
        return response()->json([
            'success' => false, 
            'message' => 'Call user not found.'
        ], 200);
    }

    // Insert into ratings table
    $rating = new Ratings(); // Ensure you have a Rating model for the ratings table
    $rating->user_id = $user_id;
    $rating->call_user_id = $call_user_id;
    $rating->ratings = $ratings;
    $rating->title = $title;
    $rating->description = $description;

    if ($rating->save()) {
        return response()->json([
            'success' => true,
            'message' => 'Ratings inserted successfully.',
            'data' => [[
                'id' => $rating->id,
                'user_id' => $rating->user_id,
                'call_user_id' => $rating->call_user_id,
                'ratings' => number_format($rating->ratings, 1), // Format ratings to one decimal place
                'title' => $rating->title,
                'description' => $rating->description,
                'updated_at' => $rating->updated_at->format('Y-m-d H:i:s'),
                'created_at' => $rating->created_at->format('Y-m-d H:i:s'),
            ]],
        ], 200);
    }

    return response()->json([
        'success' => false, 
        'message' => 'Failed to insert ratings.'
    ], 500);
}

public function add_coins(Request $request)
{
  
    $user_id = $request->input('user_id'); 
    $coins_id = $request->input('coins_id');

    // Validate user_id
    if (empty($user_id)) {
        return response()->json([
            'success' => false,
            'message' => 'user_id is empty.',
        ], 200);
    }

    // Validate points_id
    if (empty($coins_id)) {
        return response()->json([
            'success' => false,
            'message' => 'coins_id is empty.',
        ], 200);
    }

    // Check if user exists
    $user = Users::find($user_id);
    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'User not found.',
        ], 200);
    }

    // Check if points entry exists
    $coins_entry = Coins::find($coins_id);
    if (!$coins_entry) {
        return response()->json([
            'success' => false,
            'message' => 'Coins entry not found.',
        ], 200);
    }

    // Get points from the points entry
    $coins = $coins_entry->coins;
    $price = $coins_entry->price;

    // Add points to the user's points field
    $user->coins += $coins;
    $user->total_coins += $coins;
    if (!$user->save()) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to update user coins.',
        ], 500);
    }

    // Record the transaction
    $transaction = new Transactions();
    $transaction->user_id = $user_id;
    $transaction->coins = $coins;
    $transaction->type = 'add_coins';
    $transaction->amount = $price;
    $transaction->datetime = now();

    if (!$transaction->save()) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to save transaction.',
        ], 500);
    }
    $user = Users::select('name', 'coins', 'total_coins')->find($user_id);
    // Return success response
    return response()->json([
        'success' => true,
        'message' => 'Coins added successfully.',
        'data' => [
            'name' => $user->name,
            'coins' => (string) $user->coins,
            'total_coins' => (string) $user->total_coins,
        ],
    ], 200);
}

public function cron_jobs(Request $request)
{
    // Get the current time
    $currentDateTime = Carbon::now();

    // Fetch users whose datetime is more than 1 hour ago
    $usersToDelete = DB::table('not_repeat_call_users')
        ->where('datetime', '<', $currentDateTime->subHour())
        ->get();

    // Delete those users
    DB::table('not_repeat_call_users')
        ->whereIn('id', $usersToDelete->pluck('id'))
        ->delete();

    return response()->json([
        'success' => true,
        'message' => 'Data Deleted successfully.',
        'deleted_users_count' => $usersToDelete->count(),
    ], 200);
}
}