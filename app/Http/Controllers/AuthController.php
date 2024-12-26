<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\Users;
use App\Models\Avatars;
use App\Models\Coins;
use App\Models\SpeechText;   
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
        $this->middleware('auth:api', ['except' => ['login','register','send_otp']]);
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
            $imageUrl = ($avatar && $avatar->image) ? asset('storage/app/public/avatars/' . $avatar->image) : '';
            $voicePath = $users && $users->voice ? asset('storage/app/public/voices/' . $users->voice) : '';
        
            // Attempt to log the user in using the mobile number (no need for password)
            $credentials = ['mobile' => $mobile];
            $token = auth('api')->attempt($credentials);
        
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

        if (! $token = auth('api')->attempt($credentials)) { 
            return response()->json(['error' => 'Unauthorized'], 401);
        } 
        
        $avatar = Avatars::find($users->avatar_id);
        $gender = $avatar ? $avatar->gender : '';

        $imageUrl = ($avatar && $avatar->image) ? asset('storage/app/public/avatars/' . $avatar->image) : '';
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

   $imageUrl = ($avatar && $avatar->image) ? asset('storage/app/public/avatars/' . $avatar->image) : '';
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
        $users = auth('api')->user(); // Retrieve the authenticated user
    
        if (empty($users)) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to retrieve user details.',
            ], 200);
        }
    
        $avatar = Avatars::find($users->avatar_id);
        $gender = $avatar ? $avatar->gender : '';
    
        $imageUrl = ($avatar && $avatar->image) ? asset('storage/app/public/avatars/' . $avatar->image) : '';
        $voicePath = $users && $users->voice ? asset('storage/app/public/voices/' . $users->voice) : '';
    
        return response()->json([
            'success' => true,
            'message' => 'user details retrieved successfully.',
            'data' => [
                'id' => $users->id,
                'name' => $users->name,
                'user_gender' => $users->gender,
                'avatar_id' => (int) $users->avatar_id,
                'image' => $imageUrl ?? '',
                'gender' => $gender,
                'language' => $users->language,
                'age' => (int) $users->age ?? '',
                'mobile' => $users->mobile ?? '',
                'interests' => $users->interests ?? '',
                'describe_yourself' => $users->describe_yourself ?? '',
                'voice' => $voicePath ?? '',
                'status' => $users->status ?? '',
                'balance' => (int) $users->balance ?? '',
                'coins' => (int) $users->coins ?? '',
                'audio_status' => (int) $users->audio_status ?? '',
                'video_status' => (int) $users->video_status ?? '',
                'datetime' => Carbon::parse($users->datetime)->format('Y-m-d H:i:s'),
                'updated_at' => Carbon::parse($users->updated_at)->format('Y-m-d H:i:s'),
                'created_at' => Carbon::parse($users->created_at)->format('Y-m-d H:i:s'),
            ],
        ], 200);
    }
    

    public function coins_list(Request $request)
    {
        $user = auth('api')->user(); // Retrieve the authenticated user
        
        if (empty($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to retrieve user details.',
            ], 200);
        }
    
        $user_id = $user->id; // Get the authenticated user's ID
    
        $coins = Coins::orderBy('price', 'asc')->get(); 
    
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
                'updated_at' => Carbon::parse($coin->updated_at)->format('Y-m-d H:i:s'),
                'created_at' => Carbon::parse($coin->created_at)->format('Y-m-d H:i:s'),
            ];
        });
    
        return response()->json([
            'success' => true,
            'message' => 'Coins listed successfully.',
            'data' => $coinsData,
        ], 200);
    }
    
    public function transaction_list(Request $request)
    {
        $user = auth('api')->user(); // Retrieve the authenticated user
    
        if (empty($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to retrieve user details.',
            ], 200);
        }
    
        $user_id = $user->id; // Get the authenticated user's ID
    
        $transactions = Transactions::where('user_id', $user_id)
                     ->orderBy('datetime', 'desc')
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
            'data' => $transactionsData,
        ], 200);
    }
    
    public function avatar_list(Request $request)
    {
        $user = auth('api')->user(); // Retrieve the authenticated user
    
        if (empty($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to retrieve user details.',
            ], 200);
        }
    
        $gender = $request->input('gender'); 
    
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
    
        $avatars = Avatars::where('gender', strtolower($gender))->get();
    
        if ($avatars->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No avatars found for the specified gender.',
            ], 200);
        }
    
        $avatarData = [];
        foreach ($avatars as $avatar) {
            $imageUrl = ($avatar && $avatar->image) ? asset('storage/app/public/avatars/' . $avatar->image) : '';
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
    // Retrieve the authenticated user
    $user = auth('api')->user(); // This checks if the user is authenticated

    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. Please provide a valid token.',
        ], 401); // Return a 401 Unauthorized if no user is authenticated
    }

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

public function delete_users(Request $request)
{
    // Retrieve the authenticated user
    $user = auth('api')->user();
    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. Please provide a valid token.',
        ], 401);
    }

    // Retrieve user_id and delete_reason from the request
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
    // Retrieve the authenticated user
    $user = auth('api')->user();
    if (!$user) {
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
    $imageUrl = $avatar && $avatar->image ? asset('storage/app/public/avatars/' . $avatar->image) : '';
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
    // Retrieve the authenticated user
    $user = auth('api')->user();
    if (!$user) {
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
    $imageUrl = $avatar && $avatar->image ? asset('storage/app/public/avatars/' . $avatar->image) : '';
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
    // Retrieve the authenticated user
    $user = auth('api')->user();
    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. Please provide a valid token.',
        ], 401);
    }

    $language = $request->input('language');
    if (empty($language)) {
        return response()->json([
            'success' => false,
            'message' => 'Language is empty.',
        ], 200);
    }

    $speech_text = SpeechText::where('language', $language)->inRandomOrder()->first();

    if (!$speech_text) {
        return response()->json([
            'success' => false,
            'message' => 'No Speech Text found for the specified language.',
        ], 200);
    }

    return response()->json([
        'success' => true,
        'message' => 'Speech Text listed successfully.',
        'data' => [
            'id' => $speech_text->id,
            'text' => $speech_text->text,
            'language' => $speech_text->language,
        ],
    ], 200);
}

public function female_users_list(Request $request)
{
    $user = auth('api')->user();
    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. Please provide a valid token.',
        ], 401);
    }

    $offset = $request->input('offset', 0);
    $limit = $request->input('limit', 10);

    $totalCount = users::where('gender', 'female')->count();

    // Retrieve paginated female users
    $users = users::where('gender', 'female')
        ->skip($offset)
        ->take($limit)
        ->with('avatar') // Only eager load the avatar relationship if necessary
        ->get();

    if ($users->isEmpty()) {
        return response()->json([
            'success' => false,
            'message' => 'No female users found.',
            'total' => $totalCount, // Include total count even if no data found
        ], 200);
    }

    $usersData = [];
    foreach ($users as $user) {
        $avatar = $user->avatar; // Use the avatar relationship to get the avatar
        $gender = $avatar ? $avatar->gender : '';
        $imageUrl = $avatar && $avatar->image ? asset('storage/app/public/avatars/' . $avatar->image) : '';
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

      // Retrieve the authenticated user
      $user = auth('api')->user(); // This checks if the user is authenticated

      if (!$user) {
          return response()->json([
              'success' => false,
              'message' => 'Unauthorized. Please provide a valid token.',
          ], 401); // Return a 401 Unauthorized if no user is authenticated
      }
    // Retrieve user_id, offset, and limit from request
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
            'user_id' => $withdrawal->user_id,
            'amount' => $withdrawal->amount,
            'status' => $withdrawal->status,
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
      // Retrieve the authenticated user
      $user = auth('api')->user(); // This checks if the user is authenticated

      if (!$user) {
          return response()->json([
              'success' => false,
              'message' => 'Unauthorized. Please provide a valid token.',
          ], 401); // Return a 401 Unauthorized if no user is authenticated
      }
    // Retrieve input values
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
        ? asset('storage/app/public/avatars/' . $avatar->image) : '';
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
      // Retrieve the authenticated user
      $user = auth('api')->user(); // This checks if the user is authenticated

      if (!$user) {
          return response()->json([
              'success' => false,
              'message' => 'Unauthorized. Please provide a valid token.',
          ], 401); // Return a 401 Unauthorized if no user is authenticated
      }
    // Retrieve input values
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

    if ($user->coins < 10) {
        return response()->json([
            'success' => false,
            'message' => 'Insufficient coins.',
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
    $receiverImageUrl = ($receiverAvatar && $receiverAvatar->image) ? asset('storage/app/public/avatars/' . $receiverAvatar->image) : '';

       // Fetch avatar image for caller if needed
       $callerAvatar = Avatars::find($caller->avatar_id);
       $callerImageUrl = ($callerAvatar && $callerAvatar->image) ? asset('storage/app/public/avatars/' . $callerAvatar->image) : '';
   
   
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
      // Retrieve the authenticated user
      $user = auth('api')->user(); // This checks if the user is authenticated

      if (!$user) {
          return response()->json([
              'success' => false,
              'message' => 'Unauthorized. Please provide a valid token.',
          ], 401); // Return a 401 Unauthorized if no user is authenticated
      }
    // Retrieve input values
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

    if ($user->coins < 10) {
        return response()->json([
            'success' => false,
            'message' => 'Insufficient coins.',
        ], 200);
    }

    $balance_time = '';
    $coins = $user->coins;
    
    // Calculate balance time in minutes and seconds
    $minutes = floor($coins / 10); // For every 10 coins, 1 minute
    $seconds = 0; // Assume no partial seconds for simplicity
    $balance_time = sprintf('%d:%02d', $minutes, $seconds);

    // Filter female users with status = 1 based on call_type
    $query = users::where('gender', 'female')
        ->where('id', '!=', $user_id); // Exclude the requesting user

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
    $receiverImageUrl = ($receiverAvatar && $receiverAvatar->image) ? asset('storage/app/public/avatars/' . $receiverAvatar->image) : '';

       // Fetch avatar image for caller if needed
       $callerAvatar = Avatars::find($caller->avatar_id);
       $callerImageUrl = ($callerAvatar && $callerAvatar->image) ? asset('storage/app/public/avatars/' . $callerAvatar->image) : '';
   
   
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


public function update_connected_call(Request $request)
{
      // Retrieve the authenticated user
      $user = auth('api')->user(); // This checks if the user is authenticated

      if (!$user) {
          return response()->json([
              'success' => false,
              'message' => 'Unauthorized. Please provide a valid token.',
          ], 401); // Return a 401 Unauthorized if no user is authenticated
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
            'message' => 'Call not found.',
        ], 200);
    }
    
    $user = users::find($user_id);

    // Convert the times to Carbon instances with today's date
    $currentDate = Carbon::now()->format('Y-m-d'); // Current date
    $startTime = Carbon::createFromFormat('Y-m-d H:i:s', "$currentDate $started_time"); // Add the date
    $endTime = Carbon::createFromFormat('Y-m-d H:i:s', "$currentDate $ended_time"); // Add the date

    // Calculate the duration in seconds
    $durationSeconds = $endTime->diffInSeconds($startTime);

    // Calculate duration in minutes (ensure at least 1 minute)
    $durationMinutes = max($endTime->diffInMinutes($startTime), 1);

    // Calculate spend coins and income
    $coinsPerMinute = 10;
    $incomePerMinute = 2;

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



public function calls_list(Request $request)
{
      // Retrieve the authenticated user
      $user = auth('api')->user(); // This checks if the user is authenticated

      if (!$user) {
          return response()->json([
              'success' => false,
              'message' => 'Unauthorized. Please provide a valid token.',
          ], 401); // Return a 401 Unauthorized if no user is authenticated
      }
    $user_id = $request->input('user_id');
    $gender = $request->input('gender'); 

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

    // Query based on gender and filter out calls with empty started_time
    if ($gender === 'male') {
        // Male: Check where user_id matches and started_time is not empty
        $calls = UserCalls::where('user_id', $user_id)
            ->whereNotNull('started_time')
            ->where('started_time', '!=', '')
            ->get();
    } else {
        // Female: Check where call_user_id matches and started_time is not empty
        $calls = UserCalls::where('call_user_id', $user_id)
            ->whereNotNull('started_time')
            ->where('started_time', '!=', '')
            ->get();
    }

    // Check if no calls found
    if ($calls->isEmpty()) {
        return response()->json([
            'success' => false,
            'message' => 'Data not found.',
        ], 200);
    }

    // Prepare the call data
    $callData = [];
    foreach ($calls as $call) {
        // Calculate duration if gender is male
        $duration = '';
        if ($call->started_time && $call->ended_time) {
            $startTime = Carbon::parse($call->started_time);
            $endTime = Carbon::parse($call->ended_time);

            // Calculate difference in seconds
            $durationSeconds = $startTime->diffInSeconds($endTime);
            
            // Convert total seconds to minutes and seconds
            $durationMinutes = floor($durationSeconds / 60); // Minutes
            $durationSeconds = $durationSeconds % 60; // Remaining seconds

            // Format duration as i:s (e.g., 5:45)
            $duration = sprintf('%d:%02d', $durationMinutes, $durationSeconds);
        }

        // For female gender, we return income
        $income = $gender === 'female' ? $call->income : '';

        // Fetch user names for both user_id and call_user_id
        $caller = users::find($call->user_id);
        $receiver = users::find($call->call_user_id);

        $avatar = null;
        $imageUrl = '';
        if ($gender === 'male' && $receiver) {
            $avatar = Avatars::find($receiver->avatar_id);
            $imageUrl = ($avatar && $avatar->image) ? asset('storage/app/public/avatars/' . $avatar->image) : '';
        } elseif ($gender === 'female' && $receiver) {
            $avatar = Avatars::find($receiver->avatar_id);
            $imageUrl = ($avatar && $avatar->image) ? asset('storage/app/public/avatars/' . $avatar->image) : '';
        }

        // Add data to response array based on gender
        if ($gender === 'male') {
            // For male users, include audio and video status
            $callData[] = [
                'id' =>$call->call_user_id,
                'name' => $receiver ? $receiver->name : '',
                'image' => $imageUrl,
                'started_time' => $call->started_time ?? '',
                'duration' => $duration,
                'audio_status' => $receiver->audio_status,
                'video_status' => $receiver->video_status,
            ];
        } elseif ($gender === 'female') {
            // For female users, include income
            $callData[] = [
                'id' =>$call->call_user_id,
                'name' => $receiver ? $receiver->name : '',
                'image' => $imageUrl,
                'started_time' => $call->started_time ?? '',
                'duration' => $duration,
                'income' => $income, 
            ];
        }
    }

    // Return the call data response
    return response()->json([
        'success' => true,
        'message' => 'Calls listed successfully.',
        'data' => $callData,
    ], 200);
}

public function female_call_attend(Request $request)
{
      // Retrieve the authenticated user
    $user = auth('api')->user(); // This checks if the user is authenticated

    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. Please provide a valid token.',
        ], 401); // Return a 401 Unauthorized if no user is authenticated
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

    // Update the started_time
    $userCall->started_time = $started_time;
    $userCall->save();

    // Find the user and fetch balance time
    $user = users::find($user_id);
    $coins = $user ? $user->coins : 0;
    $minutes = floor($coins / 10); // For every 10 coins, 1 minute
    $seconds = 0;
    $balance_time = sprintf('%d:%02d', $minutes, $seconds);

    // Fetch names and avatar images for caller and receiver
    $caller = users::find($userCall->user_id);
    $receiver = users::find($userCall->call_user_id);

    $callerAvatar = Avatars::find($caller->avatar_id);
    $callerImageUrl = ($callerAvatar && $callerAvatar->image) ? asset('storage/app/public/avatars/' . $callerAvatar->image) : '';

    $receiverAvatar = Avatars::find($receiver->avatar_id);
    $receiverImageUrl = ($receiverAvatar && $receiverAvatar->image) ? asset('storage/app/public/avatars/' . $receiverAvatar->image) : '';

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

public function reports(Request $request)
{
    // Retrieve the authenticated user
    $user = auth('api')->user(); // This checks if the user is authenticated

    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. Please provide a valid token.',
        ], 401); // Return a 401 Unauthorized if no user is authenticated
    }

    // Get user_id from request
    $user_id = $request->input('user_id');

    // Validate user_id
    if (empty($user_id)) {
        return response()->json([
            'success' => false,
            'message' => 'user_id is empty.',
        ], 200);
    }

    // Fetch the user based on user_id to check if the user is female
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

    // Fetch the call details for the given user_id
    $call_id = $request->input('call_id');
    $call = UserCalls::where('id', $call_id)->where('call_user_id', $user_id)->first();

    if (!$call) {
        return response()->json([ 
            'success' => false,
            'message' => 'Call not found.',
        ], 200);
    }

    // Get the total calls today for this user
    $today_calls = UserCalls::where('call_user_id', $user_id)
        ->whereDate('datetime', now()->toDateString())  // Assuming created_at stores the call date
        ->count();

    // Get the total earnings today for this user
    $today_earnings = Transactions::where('user_id', $user_id)
        ->whereDate('datetime', now()->toDateString())  // Assuming 'datetime' stores the transaction date
        ->sum('amount');

    // Prepare and return the response with the data
    return response()->json([
        'success' => true,
        'message' => 'Reports listed successfully.',
        'data' => [
            'user_id' => $call->user_id,
            'user_name' => $user->name,
            'today_calls' => $today_calls,
            'today_earnings' => $today_earnings,
        ],
    ], 200);
}

}