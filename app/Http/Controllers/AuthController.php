<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\Users;
use App\Models\Upis;
use App\Models\Avatars;
use App\Models\Whatsapplink;
use App\Models\Coins;
use App\Models\SpeechText;  
use App\Models\random_female_connecteds;
use App\Models\fcm_tokens;
use App\Models\refer_bonus;
use App\Models\Orders;
use App\Models\Appsettings; 
use App\Models\Ratings; 
use App\Models\ScreenNotifications;
use App\Services\FirebaseService;
use App\Models\Gifts;
use App\Models\Transactions;
use App\Models\DeletedUsers; 
use App\Models\Withdrawals;  
use App\Models\UserCalls;
use App\Models\explaination_video;
use App\Models\PersonalNotifications;
use App\Models\explaination_video_links;
use Carbon\Carbon;
use App\Models\News; 
use Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;
use Berkayk\OneSignal\OneSignalFacade as OneSignal;


class AuthController extends Controller
{
    protected $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;

        // Set middleware
        $this->middleware('auth:api', ['except' => ['login','register','send_otp','avatar_list','speech_text','settings_list','appsettings_list','add_coins','cron_jobs','cron_updates','explaination_video_list','gifts_list','createUpigateway','whatsapplink_list','try_coins','check_refer_code']]);
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
           $referredBy = $request->input('referred_by');
    
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
            $name = $this->generateRandomFemaleName($language);
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
        $users->refer_code = $this->generateReferCode(); // Generate self refer code
        $users->datetime = Carbon::now();

        $validReferral = false;

        if ($referredBy) {
            $referrer = Users::where('refer_code', $referredBy)->first();
        
            $referralSettings = News::latest()->first();
            $coinsPerReferral = $referralSettings->coins_per_referral ?? 0;
            $moneyPerReferral = $referralSettings->money_per_referral ?? 0;
        
            if ($referrer) {
                $newUserGender = $users->gender;
                $referrerGender = $referrer->gender;
        
                if ($referrerGender === 'male' && $newUserGender === 'male') {
                    // ✅ Male → Male
                    $referrer->coins += $coinsPerReferral;
                    $referrer->total_coins += $coinsPerReferral;
                    $referrer->total_referrals += 1;
                    $referrer->save();
        
                    Transactions::create([
                        'user_id' => $referrer->id,
                        'type' => 'refer_bonus',
                        'coins' => $coinsPerReferral,
                        'datetime' => now(),
                    ]);
        
                    $validReferral = true;
        
                } elseif ($referrerGender === 'female' && $newUserGender === 'female') {
                    if (!empty($referrer->pancard_name) && !empty($referrer->pancard_number)) {
                        $validReferral = true;      
                    }
                }
            }
        }
        
        // Only set referred_by if it's a valid referral
        if ($validReferral) {
            $users->referred_by = $referredBy;
        }
        
        $users->save();
    
        // Prepare the user details to return
        $avatar = Avatars::find($users->avatar_id);
        $imageUrl = ($avatar && $avatar->image) ? asset('storage/app/public/' . $avatar->image) : '';
        $voicePath = $users && $users->voice ? asset('storage/app/public/voices/' . $users->voice) : '';
    
        // Find user manually
        $user = Users::where('mobile', $mobile)->first();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        // Generate JWT token manually
        config(['jwt.ttl' => 60 * 24 * 90]); // 90 days in minutes
        $token = auth('api')->login($user);

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
            'interests' => $users->interests ?? '',
            'describe_yourself' =>  $users->describe_yourself ?? '',
            'voice' =>  $voicePath ?? '',
            'status' => 0,
            'balance' => (int) $users->balance ?? '',
            'coins' => (int) $users->coins ?? '',
            'total_coins' => (int) $users->total_coins ?? '',
               'refer_code' => $users->refer_code ?? '',
              'referred_by' => $users->referred_by ?? '',
               'total_referrals' => $users->total_referrals ?? '',
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

    private function generateRandomFemaleName($language){
        // Fetch a random name from female_users table based on language
        $randomFemaleName = DB::table('female_users')->where('language', $language)->inRandomOrder()->value('name');
        if (!$randomFemaleName) {
            $randomFemaleName = 'user'; // Default name if table is empty
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
        private function generateReferCode()
        {
            $letters = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 4));
            $numbers = substr(str_shuffle('0123456789'), 0, 4);
            return $letters . $numbers;
        }
        
         public function check_refer_code(Request $request)
{
    $validator = Validator::make($request->all(), [
        'mobile' => 'required|digits:10',
        'referred_by' => 'nullable|string',
    ], [
        'mobile.required' => 'Mobile number is required.',
        'mobile.digits' => 'Mobile number must be 10 digits.',
    ]);

    // If validation fails
    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
        ], 200);
    }

    $mobile = $request->input('mobile');
    $referredBy = $request->input('referred_by');

    // Check if the mobile already exists
    $existingUser = Users::where('mobile', $mobile)->first();
    if ($existingUser) {
        return response()->json([
            'success' => false,
            'message' => 'Referral is only available for New Users',
        ], 200);
    }

    // If mobile is new, but referral not yet provided
    if (empty($referredBy)) {
        return response()->json([
            'success' => true,
            'message' => 'Please enter referral code'
        ], 200);
    }

    // If referral code is provided, validate it
    $referrer = Users::where('refer_code', $referredBy)->first();
    if (! $referrer) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid refer code',
        ], 200);
    }

    return response()->json([
        'success' => true,
        'message' => 'Valid for registration',
    ], 200);
}



        
    public function createUpigateway(Request $request)
    {
      
        $user_id = $request->input('user_id');
        $client_txn_id = $request->input('client_txn_id');
        $amount = $request->input('amount');

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

        if (empty($client_txn_id)) {
            return response()->json([
                'success' => false,
                'message' => 'client_txn_id is empty.',
            ], 200);
        }

        if (empty($amount)) {
            return response()->json([
                'success' => false,
                'message' => 'amount is empty.',
            ], 200);
        }

        // Set API URL
        $apiUrl = "https://api.ekqr.in/api/create_order";


        // Prepare request payload with default values
        $payload = [
            "key" => "698eca21-ee54-42ff-b226-1a969ab4c344",
            "client_txn_id" =>$client_txn_id.'-HM',
            "amount" => $amount,
            "p_info" => "Hima",
            "customer_name" => $user->name,
            "customer_email" => 'himaapp123@gmail.com',
            "customer_mobile" => $user->mobile,
            "redirect_url" => "https://himaapp.in/success.php",
            "udf1" => "user defined field 1 (max 25 char)",
            "udf2" => "user defined field 2 (max 25 char)",
            "udf3" => "user defined field 3 (max 25 char)"
        ];

        // Make POST request to the external API
            $response = Http::post($apiUrl, $payload);

            // Return only the response data
            return $response->json();

        
    }

     public function login(Request $request) 
    {
        $validator = Validator::make($request->all(), [
            'mobile' => 'required|digits:10',
        ]);
    
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
    
        $mobile = $request->mobile;
        $users = Users::where('mobile', $mobile)->first();
    
        // If user not found, return failure response
        if (!$users) {
            return response()->json([
                'success' => true,
                'registered' => false,
                'message' => 'Mobile number not registered.'
            ], 200);
        }
    
        config(['jwt.ttl' => 60 * 24 * 90]); // Token valid for 90 days
    
        // **Manually log in user without password**
        $token = auth('api')->login($users);

        if (!$token) {
            return response()->json(['error' => 'Could not generate token'], 401);
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
                'pancard_name' => $users->pancard_name ?? '',
                'pancard_number' => $users->pancard_number ?? '',
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
            'interests' => $user->interests ?? '',
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
    
       $totalCoinsGained = null;
    $totalAmountGained = null;

    if (strtolower($user->gender) === 'male') {
        $totalCoinsGained = Transactions::where('user_id', $user->id)
            ->where('type', 'refer_bonus')
            ->sum('coins');
    } elseif (strtolower($user->gender) === 'female') {
        $totalAmountGained = Transactions::where('user_id', $user->id)
            ->where('type', 'refer_bonus')
            ->sum('amount');
    }


      $responseData = [
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
            'upi_id' => $user->upi_id ?? '',
            'refer_code' => $user->refer_code ?? '',
            'referred_by' => $user->referred_by ?? '',
            'total_referrals' => $user->total_referrals ?? '',
             'pancard_name' => $user->pancard_name ?? '',
            'pancard_number' => $user->pancard_number ?? '',
            'datetime' => Carbon::parse($user->datetime)->format('Y-m-d H:i:s'),
            'updated_at' => Carbon::parse($user->updated_at)->format('Y-m-d H:i:s'),
            'created_at' => Carbon::parse($user->created_at)->format('Y-m-d H:i:s'),
        ];
    
         // Conditionally add the bonus amount based on gender
            if (strtolower($user->gender) === 'male') {
                $responseData['referral_coins_gained'] = $totalCoinsGained;
            } elseif (strtolower($user->gender) === 'female') {
                $responseData['referral_amount_gained'] = $totalAmountGained;
            }
        
          // Get referral bonus values from the news table
            $referralSettings = News::latest()->first(); // or use News::find(1), etc.
    
            $coinsPerReferral = $referralSettings->coins_per_referral;
            $moneyPerReferral = $referralSettings->money_per_referral;
    
            // Conditionally add the bonus amount based on gender
            if (strtolower($user->gender) === 'female') {
                $responseData['money_per_referral'] = $moneyPerReferral;
            } elseif (strtolower($user->gender) === 'male') {
                $responseData['coins_per_referral'] = $coinsPerReferral;
            }
            
    
          // Conditionally add the bonus amount based on gender
            if (strtolower($user->gender) === 'female') {
                 $responseData['disclaimer'] = "Disclaimer: You will get money only when the referred user completes their KYC.";
            } 
    
        return response()->json([
            'success' => true,
            'message' => 'User details retrieved successfully.',
            'data' => $responseData,
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
        $user = Users::find($user_id);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'user not found.',
            ], 200);
        }
        $offset = $request->input('offset', 0);  // Default offset to 0 if not provided
        $limit = $request->input('limit', 10);  // Default limit to 10 if not provided
    
        // Determine the query based on user's coins
        if ($user->coins > 0) {
            $coinsQuery = Coins::where('price', '>', 9)
                          ->orderBy('price', 'asc');
        } else {
            $coinsQuery = Coins::orderBy('price', 'asc');
        }
        
        $totalCoins = $coinsQuery->count();
        $coins = $coinsQuery->skip($offset)
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
            'total' => $totalCoins,
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

    $user_id = $request->input('user_id');
    $offset = $request->input('offset', 0);  // Default offset to 0 if not provided
    $limit = $request->input('limit', 10);   // Default limit to 10 if not provided

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

    $transactionsCount = 0;

    // **Only check transactions if user has 1 or more coins**
    if ($user->coins > 0) {
        $transactionsCount = Transactions::where('type', 'add_coins')
            ->where('datetime', '>=', Carbon::now()->subDays(3))
            ->count();

            $transactionsCount += 150;
    }

        $coins = Coins::where('id', 5)
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

    // **Map Coins Data with Transaction Count (Only if user has 1+ coins)**
    $coinsData = $coins->map(function ($coin) use ($transactionsCount, $user) {
        $data = [
            'id' => $coin->id,
            'price' => $coin->price,
            'coins' => $coin->coins,
            'save' => $coin->save,
            'popular' => $coin->popular,
            'total_count' => $coin->count ?? 0,
            'best_offer' => $coin->best_offer,
            'updated_at' => Carbon::parse($coin->updated_at)->format('Y-m-d H:i:s'),
            'created_at' => Carbon::parse($coin->created_at)->format('Y-m-d H:i:s'),
        ];

        // **Only include transactions count if user has coins > 0**
        // **If user has 0 coins, set total_count to 100**
        if ($user->coins == 0) {
            $data['total_count'] = 100;
        } else {
            $data['total_count'] = $transactionsCount;
        }

        return $data;
    });


    return response()->json([
        'success' => true,
        'message' => 'Best Offers listed successfully.',
        'total' => $coins->count(),
        'data' => $coinsData,
    ], 200);
}


public function transaction_list(Request $request) {
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

    // Add offset and limit
    $offset = (int) $request->input('offset', 0);
    $limit = (int) $request->input('limit', 10);

    // Base query with filters applied
    $baseQuery = Transactions::where('transactions.user_id', $user_id)
        ->leftJoin('user_calls', function ($join) {
            $join->on('transactions.user_id', '=', 'user_calls.user_id')
                 ->on('transactions.datetime', '=', 'user_calls.update_current_endedtime');
        })
        ->leftJoin('users as call_users', 'user_calls.call_user_id', '=', 'call_users.id')
        ->select(
            'transactions.*',
            'user_calls.call_user_id',
            'call_users.name as call_user_name',
            'user_calls.started_time',
            'user_calls.ended_time',
            'user_calls.coins_spend',
            'user_calls.type as call_type'
        )
        ->where(function ($query) {
            $query->where('transactions.type', '<>', 'coins_deduction')
                  ->orWhere(function ($q) {
                      $q->where('transactions.type', '=', 'coins_deduction')
                        ->where('transactions.coins', '>=', 10);
                  });
        })
        ->orderBy('transactions.datetime', 'desc');

    // Clone for pagination
    $total = $baseQuery->count(); // Total before pagination

    $transactions = (clone $baseQuery)->skip($offset)->take($limit)->get();

    if ($transactions->isEmpty()) {
        return response()->json([
            'success' => false,
            'message' => 'No transactions found for this user.',
        ], 200);
    }

    // Prepare the response data
    $transactionsData = [];

    foreach ($transactions as $transaction) {
        $data = [
            'id' => $transaction->id,
            'user_id' => $transaction->user_id,
            'type' => $transaction->type,
            'amount' => $transaction->amount ?? '',
            'payment_type' => $transaction->payment_type ?? '',
            'datetime' => $transaction->datetime,
            'date' => Carbon::parse($transaction->datetime)->format('M d'),
            'coins' => $transaction->coins ?? 0,
        ];

        // Include call details only for `coins_deduction`
        if ($transaction->type === 'coins_deduction') {
            $call_user_id = $transaction->call_user_id ?? '';
            $call_user_name = $transaction->call_user_name ?? '';
            $started_time = $transaction->started_time ?? '';
            $ended_time = $transaction->ended_time ?? '';
            $coins_spend = $transaction->coins_spend ?? 0;
            $call_type = $transaction->call_type ?? '';

            // Calculate duration if both times are present
            $duration = '';
            if (!empty($transaction->started_time) && !empty($transaction->ended_time)) {
                $start = Carbon::parse($transaction->started_time);
                $end = Carbon::parse($transaction->ended_time);
                $diffInSeconds = $start->diffInSeconds($end);

                $hours = floor($diffInSeconds / 3600);
                $minutes = floor(($diffInSeconds % 3600) / 60);
                $seconds = $diffInSeconds % 60;

                $duration = trim(
                    ($hours > 0 ? "{$hours} hour" . ($hours > 1 ? 's ' : ' ') : '') .
                    ($minutes > 0 ? "{$minutes} min " : '') .
                    ($seconds > 0 ? "{$seconds} sec" : '')
                );
            }

            // Add call details to the transaction data
            $data = array_merge($data, [
                'call_user_id' => $call_user_id,
                'call_user_name' => $call_user_name,
                'started_time' => $started_time,
                'ended_time' => $ended_time,
                'duration' => $duration,
                'coins' => $coins_spend,
                'call_type' => $call_type
            ]);
        }

        $transactionsData[] = $data;
    }

    return response()->json([
        'success' => true,
        'message' => 'User transaction list retrieved successfully.',
        'total' => $total,
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
            ->inRandomOrder()
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
    $authKey = 'dc0b07c812ca4934'; // Your authkey here
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
            'payment_gateway_type' => $item->payment_gateway_type,
            'auto_disable_info ' => $item->auto_disable_info,
             'terms_conditions' => $item->terms_conditions,
            'refund_cancellation' => $item->refund_cancellation,
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
            'bank' => $item->bank,
            'upi' => $item->upi,
            'minimum_required_version' => $item->minimum_required_version,
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
    
      return response()->json([
        'success' => false,
        'message' => 'please mail your mobile number and describe your issue',
    ], 200);
    
    // $user_id = $request->input('user_id');
    // $delete_reason = $request->input('delete_reason');

    // if (empty($user_id)) {
    //     return response()->json([
    //         'success' => false,
    //         'message' => 'user_id is empty.',
    //     ], 200);
    // }

    // if (empty($delete_reason)) {
    //     return response()->json([
    //         'success' => false,
    //         'message' => 'delete_reason is empty.',
    //     ], 200);
    // }

    // // Find the user to delete
    // $user = users::find($user_id);
    // if (!$user) {
    //     return response()->json([
    //         'success' => false,
    //         'message' => 'user not found.',
    //     ], 200);
    // }

    // // Log user deletion in the DeletedUsers model
    // $deleteduser = new DeletedUsers();
    // $deleteduser->user_id = $user->id;
    // $deleteduser->name = $user->name;
    // $deleteduser->mobile = $user->mobile;
    // $deleteduser->language = $user->language;
    // $deleteduser->avatar_id = $user->avatar_id;
    // $deleteduser->coins = $user->coins;
    // $deleteduser->total_coins = $user->total_coins;
    // $deleteduser->datetime = Carbon::now();
    // $deleteduser->delete_reason = $delete_reason;
    // $deleteduser->save();

    // // Delete the user
    // $user->delete();

    // return response()->json([
    //     'success' => true,
    //     'message' => 'user deleted successfully.',
    // ], 200);
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

    $user_id = $request->input('user_id');

    // Determine the language to use
    if (!empty($user_id)) {
        // Find the user
        $user = Users::find($user_id);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 200);
        }
        $callerLanguage = $user->language;
    } else {
        $callerLanguage = 'Tamil';
    }

    // Retrieve total count of female users with the same language
    $totalCount = Users::where('gender', 'female')
        ->where('status', 2)
        ->where('language', $callerLanguage) // Match language
        ->where(function($query) {
            $query->where('audio_status', 1)
                  ->orWhere('video_status', 1);
        })
        ->count();

    // Retrieve all female users matching language, ordered by avg_call_percentage
    $Users = Users::where('gender', 'female')
        ->where('status', 2)
        ->where('language', $callerLanguage) // Match language
        ->where(function($query) {
            $query->where('audio_status', 1)
                  ->orWhere('video_status', 1);
        })
        ->inRandomOrder() // Order the results randomly
        ->with('avatar') // Only eager load the avatar relationship if necessary
        ->get();

    if ($Users->isEmpty()) {
        return response()->json([
            'success' => false,
            'message' => 'No female users found.',
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
            'age' => (int) $user->age ?? '',
            'mobile' => $user->mobile ?? '',
            'interests' => $user->interests  ?? '',
            'describe_yourself' => $user->describe_yourself ?? '',
            'voice' => $voicePath ?? '',
            'status' => $user->status ?? '',
            'balance' => (int) $user->balance ?? '',
            'audio_status' => (int) $user->audio_status ?? '',
            'video_status' => (int) $user->video_status ?? '',
            'avg_call_percentage' => (float) $user->avg_call_percentage ?? 100,
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
             'reason' => $withdrawal->reason ?? '',
            
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

      $currentTime = now();

    if ($call_type === 'audio') {
        $user->audio_status = $status;
        $user->last_audio_time_updated = $currentTime;
    } else {
        $user->video_status = $status;
        $user->last_video_time_updated = $currentTime;
    }
    
    $user->datetime = $currentTime;
    $user->save();
    
      if ($user->gender == 'female') {
        // Step 1: Get male users who had long calls with this female
        $callCounts = UserCalls::select(
                'user_id', 
                'call_user_id',
                DB::raw('SUM(TIMESTAMPDIFF(MINUTE, started_time, ended_time)) as total_minutes')
            )
            ->where('call_user_id', $user_id)
            ->whereNotNull('started_time')
            ->whereNotNull('ended_time')
            ->groupBy('user_id', 'call_user_id')
            ->having('total_minutes', '>=', 5)
            ->orderByDesc('total_minutes')
            ->get();
    
        // Step 2: Get most active users (flat collection)
        $mostActiveUsers = $callCounts->groupBy('user_id')
            ->map(fn($group) => $group->sortByDesc('total_minutes')->first())
            ->values();
    
        // Step 3: Loop through and check if female is in male's top 3
        $mostActiveUsers->each(function ($item) use ($currentTime, $user_id) {
            $maleUser = Users::find($item->user_id);
            $femaleUser = Users::find($item->call_user_id);
    
            if (!$maleUser || !$femaleUser) {
                return;
            }
    
            // Get male's top 3 most talked-to female users
            $topFemales = UserCalls::select(
                    'call_user_id',
                    DB::raw('SUM(TIMESTAMPDIFF(MINUTE, started_time, ended_time)) as total_minutes')
                )
                ->where('user_id', $maleUser->id)
                ->whereHas('callusers', fn($q) => $q->where('gender', 'female'))
                ->whereNotNull('started_time')
                ->whereNotNull('ended_time')
                ->groupBy('call_user_id')
                ->orderByDesc('total_minutes')
                ->limit(3)
                ->get();
    
            $isTop3 = $topFemales->pluck('call_user_id')->contains($femaleUser->id);
    
            if (!$isTop3) {
                return;
            }
    
            if ($femaleUser->audio_status == 1 || $femaleUser->video_status == 1) {
                $lastNotification = PersonalNotifications::where('user_id', $maleUser->id)
                    ->orderByDesc('datetime')
                    ->first();
    
                $shouldSend = !$lastNotification || now()->diffInMinutes(Carbon::parse($lastNotification->datetime)) >= 30;
    
                if ($shouldSend) {
                    PersonalNotifications::create([
                        'user_id' => $maleUser->id,
                        'title' => "{$femaleUser->name} is now online",
                        'description' => "She is waiting for your call",
                        'datetime' => now(),
                    ]);

                    OneSignal::sendNotificationCustom([
                        "app_id" => "2c7d72ae-8f09-48ea-a3c8-68d9c913c592",
                        "include_external_user_ids" => [(string) $maleUser->id],
                        "headings" => ["en" => "{$femaleUser->name} is now online."],
                        "contents" => ["en" => "She is waiting for your call"],
                        "small_icon" => "notification_icon",
                        "large_icon" => "https://himaapp.in/storage/uploads/logo/notification_icon.webp"
                    ]);
                }
            }
        });
    }
    
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
            'name' => $user->name ?? '',
            'user_gender' => $user->gender ?? '',
            'avatar_id' => (int) $user->avatar_id,
            'image' => $imageUrl ?? '',
            'gender' => $gender ?? '',
            'language' => $user->language ?? '',
            'age' => (int) $user->age ?? '',
            'mobile' => $user->mobile ?? '',
            'interests' => $user->interests ?? '',
            'describe_yourself' => $user->describe_yourself ?? '',
            'voice' => $voicePath ?? '',
            'status' => $user->status ?? '',
            'balance' => (int) $user->balance ?? '',
            'audio_status' => (int) $user->audio_status ?? '',
            'video_status' => (int) $user->video_status ?? '',
            'last_audio_time_updated' => $user->last_audio_time_updated 
                ? Carbon::parse($user->last_audio_time_updated)->format('Y-m-d H:i:s') : '',
            'last_video_time_updated' => $user->last_video_time_updated 
                ? Carbon::parse($user->last_video_time_updated)->format('Y-m-d H:i:s') : '',
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

    // Check if user is blocked
    if ($user->blocked == 1) {
        return response()->json([
            'success' => false,
            'message' => 'Your account has been suspended for 48 hours due to a violation of our policy.',
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

    if ($call_type == 'audio') {
        // For audio calls: 10 coins = 1 minute
        $minutes = floor($coins / 10);
    } elseif ($call_type == 'video') {
        // For video calls: 60 coins = 1 minute
        $minutes = floor($coins / 60);
    }
    
    $seconds = 0;
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

    // Increment missed_calls for the call_user_id user
    $receiver->missed_calls += 1;
    $receiver->save();

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
            'audio_status' => $receiver ? $receiver->audio_status : '',
            'video_status' => $receiver ? $receiver->video_status : '',
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
    $user = Users::find($user_id);
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

    // Check if the user has enough coins
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

    if ($call_type == 'audio') {
        // For audio calls: 10 coins = 1 minute
        $minutes = floor($coins / 10);
    } elseif ($call_type == 'video') {
        // For video calls: 60 coins = 1 minute
        $minutes = floor($coins / 60);
    }

    $seconds = 0;
    $balance_time = sprintf('%d:%02d', $minutes, $seconds);


  $cooldownMinutes = 5;
    $cooldownThreshold = Carbon::now()->subMinutes($cooldownMinutes);

    $callerLanguage = $user->language;

    $activeCallUserIds = UserCalls::whereDate('datetime', Carbon::today())
        ->whereNotNull('started_time')
        ->whereNull('ended_time')
        ->pluck('call_user_id')
        ->toArray();
    
    // Step 3: Get eligible female users (no priority, no last_seen)
    $priorityOneUsers = Users::where('gender', 'female')
        ->where('language', $callerLanguage)
        ->where('id', '!=', $user->id)
        ->whereNotIn('id', $activeCallUserIds)
        ->when($call_type === 'video', fn($q) => $q->where('video_status', 1), fn($q) => $q->where('audio_status', 1))
        ->pluck('id')
        ->toArray();
    
    // Merge: priority 1 users come first
    $eligibleFemaleUsers = array_merge($priorityOneUsers);
    
    // Step 4: Filter out users in cooldown
    $cooldownUserIds = UserCalls::where('user_id', $user->id)
        ->whereNotNull('ended_time')
        ->where('ended_time', '>=', $cooldownThreshold)
        ->pluck('call_user_id')
        ->toArray();
    
    $filteredUserIds = array_diff($eligibleFemaleUsers, $cooldownUserIds);
    
    // Step 5: First pass — exclude already connected (for fresh candidates)
    $alreadyConnectedIds = UserCalls::where('user_id', $user->id)
        ->where('type', $call_type)
        ->pluck('call_user_id')
        ->unique()
        ->toArray();
    
    $firstPassUserIds = array_values(array_diff($filteredUserIds, $alreadyConnectedIds));
    
    // Step 6: Get random_female_connecteds for this user
    $randomConnectedIds = random_female_connecteds::where('user_id', $user->id)
        ->pluck('female_user_id')
        ->toArray();
    
    // Step 7: Get all female users' call counts and prioritize those with fewer calls
    $usersWithCallCounts = UserCalls::select('call_user_id', DB::raw('count(*) as call_count'))
        ->whereIn('call_user_id', $firstPassUserIds)
          ->whereDate('datetime', Carbon::today())
        ->groupBy('call_user_id')
        ->get()
        ->pluck('call_count', 'call_user_id')
        ->toArray();
    
    // Step 7.1: Loop through the firstPassUserIds, and prioritize those with fewer calls than the current user
    $nextUserId = null;
    $currentUserCallCount = UserCalls::where('user_id', $user->id)->count();
    
    // Loop through and check for users with fewer calls
    foreach ($firstPassUserIds as $candidateId) {
        // If this candidate hasn't been randomly connected already
        if (!in_array($candidateId, $randomConnectedIds)) {
            $callCount = $usersWithCallCounts[$candidateId] ?? 0; // Default to 0 if not found
            
            // Check if this candidate has fewer calls than the current user
            if ($callCount < $currentUserCallCount) {
                $nextUserId = $candidateId;
                break; // Stop as soon as we find a valid candidate
            }
        }
    }
    
    // Step 8: If no suitable candidate is found in step 7, fall back to the rotation logic
    if (!$nextUserId) {
        // Fallback to checking users in a rotating manner
        $filteredUsers = Users::whereIn('id', $filteredUserIds)->get();
        $sortedFilteredUserIds = $filteredUsers->pluck('id')->toArray();
        
        // Get the last connected user and start from there
        $lastConnectedUserId = UserCalls::where('user_id', $user->id)
            ->where('type', $call_type)
            ->orderByDesc('id')
            ->value('call_user_id');
        
        $startIndex = array_search($lastConnectedUserId, $sortedFilteredUserIds);
        $startIndex = ($startIndex === false) ? 0 : ($startIndex + 1);
        
        // Loop through all filtered users and select the next one
        for ($i = 0; $i < count($sortedFilteredUserIds); $i++) {
            $index = ($startIndex + $i) % count($sortedFilteredUserIds);
            $candidateId = $sortedFilteredUserIds[$index];
    
            if (!in_array($candidateId, $randomConnectedIds)) {
                $nextUserId = $candidateId;
                break;
            }
        }
    }
    
    // Step 9: Final check and response
    $femaleUser = $nextUserId ? Users::find($nextUserId) : null;
    
    if (!$femaleUser) {
        return response()->json([
            'success' => false,
            'message' => 'Users are busy right now.',
        ], 200);
    }

    
    // Insert call data into users_call table
    $usersCalls = UserCalls::create([
        'user_id' => $user->id,
        'call_user_id' => $femaleUser->id,
        'type' => $call_type,
        'datetime' => now(),
    ]);
    
    $random_female_connecteds = random_female_connecteds::create([
        'user_id' => $user->id,
        'female_user_id' => $femaleUser->id,
        'connected_time' => now(),
    ]);


    // Fetch inserted call data
    $insertedCallData = UserCalls::find($usersCalls->id);

    // Fetch names and avatars of users
    $caller = Users::find($insertedCallData->user_id);
    $receiver = Users::find($insertedCallData->call_user_id);

    // Fetch avatar image for receiver
    $receiverAvatar = Avatars::find($receiver->avatar_id);
    $receiverImageUrl = ($receiverAvatar && $receiverAvatar->image) ? asset('storage/app/public/' . $receiverAvatar->image) : '';

    // Fetch avatar image for caller
    $callerAvatar = Avatars::find($caller->avatar_id);
    $callerImageUrl = ($callerAvatar && $callerAvatar->image) ? asset('storage/app/public/' . $callerAvatar->image) : '';

    // Update call status for the receiver
    $receiver->missed_calls += 1;
    $receiver->save();

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
    $user = Users::find($user_id);
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

    // $existingCall = UserCalls::where('user_id', $user_id)
    // ->where('call_user_id', $call->call_user_id)
    // ->where('started_time', $started_time)
    // ->first();

    //     if ($existingCall) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Call already exists with the same details.',
    //         ], 200);
    //     }


    if (!empty($call->ended_time)) {
        return response()->json([
            'success' => false, 
            'message' => 'Call has already been updated.'
        ], 200);
    }

    // Convert the times to Carbon instances with today's date
    $currentDate = Carbon::now()->format('Y-m-d'); 
    $startTime = Carbon::createFromFormat('Y-m-d H:i:s', "$currentDate $started_time");
    $endTime = Carbon::createFromFormat('Y-m-d H:i:s', "$currentDate $ended_time");

    // Handle cases where the end time is past midnight
    if ($endTime->lessThan($startTime)) {
        $endTime->addDay();
    }

    // Calculate the duration in seconds
    $durationSeconds = $endTime->diffInSeconds($startTime);

    $callType = $call->type; // Assuming 'type' field in 'UserCalls' table is either 'audio' or 'video'

    // Ignore the first 10 seconds before counting minutes
    $effectiveDurationSeconds = max($durationSeconds - 9, 0);

    // Ensure at least 1 minute is counted (ceil rounds up)
    $durationMinutes = max(ceil($effectiveDurationSeconds / 60), 1);

    $callUser = Users::find($call->call_user_id);
    // Update audio_status or video_status based on call type
    
        $currentTime = now();
    if ($callType == 'audio') {
        $callUser->audio_status = 1;
        $callUser->last_audio_time_updated = $currentTime; // Update only audio timestamp
    } elseif ($callType == 'video') {
        $callUser->video_status = 1;
        $callUser->last_video_time_updated = $currentTime; // Update only audio timestamp
    }
     $callUser->save();
      $startHour = $startTime->hour;
    $endHour = $endTime->hour;
    $startMinute = $startTime->minute;
    $endMinute = $endTime->minute;
    $startSecond = $startTime->second;
    $endSecond = $endTime->second;
    $currentCoinsBeforeDeduction = $user->coins; // Store coins before deduction

    $maxMinutesAffordable = 0;
    $actualCoinsSpend = 0;
    $actualIncome = 0;
    
    if ($callType == 'audio') {
        $coinsPerMinute = 10;
    } elseif ($callType == 'video') {
        $coinsPerMinute = 60;
    }
    
    // Determine maximum minutes user can afford
    $maxMinutesAffordable = floor($currentCoinsBeforeDeduction / $coinsPerMinute);
    
    // Ensure at least 1 minute is counted, but don't exceed what they can afford
    $effectiveMinutes = min($maxMinutesAffordable, $durationMinutes);
    
    $durationSeconds = $endTime->diffInSeconds($startTime);

// If duration is less than 10 seconds, do not charge
if ($durationSeconds < 10) {
    $income = 0;
    $coins_spend = 0;
    $roundedMinutes = 0;
} else {
    $roundedMinutes = ceil($durationSeconds / 60); // **Round up seconds to full minute**

    $maxMinutesAffordable = floor($currentCoinsBeforeDeduction / $coinsPerMinute);

    // Use the minimum of rounded minutes and what the user can afford
    $effectiveMinutes = min($maxMinutesAffordable, $roundedMinutes);

    $actualCoinsSpend = $effectiveMinutes * $coinsPerMinute;
    $actualIncome = 0;

    for ($i = 0; $i < $effectiveMinutes; $i++) {
        $currentHour = $startHour;
        $currentMinute = $startMinute + $i;

        if ($currentMinute >= 60) {
            $currentMinute -= 60;
            $currentHour++;
        }
        if ($currentHour >= 24) {
            $currentHour -= 24;
        }

        // Determine income per minute based on time slot
        if ($callType == 'audio') {
            $incomePerMinute = ($currentHour >= 16 || $currentHour < 2) ? 1 : 1;
        } else { // Video
            $incomePerMinute = ($currentHour >= 16 || $currentHour < 2) ? 6 : 6;
        }

        $actualIncome += $incomePerMinute;
    }
}

// Deduct coins from the user, ensuring it doesn't go negative
    $user->coins = max(0, $user->coins - $actualCoinsSpend);
    $user->save();

    
    $currentCoinsAfterDeduction = $user->coins;
    
     $deductionTransaction = new Transactions();
    $deductionTransaction->user_id = $user->id;
    $deductionTransaction->coins = $actualCoinsSpend;
    $deductionTransaction->type = 'coins_deduction';
    $deductionTransaction->amount = 0;  
    $deductionTransaction->datetime = now();
    $deductionTransaction->save();
  
    // Update call recipient's balance
    if ($callUser) {
        $callUser->balance += $actualIncome;
        $callUser->total_income += $actualIncome;
        $callUser->last_seen = now();
        $callUser->save();
    
        // Record transaction
        $transaction = new Transactions();
        $transaction->user_id = $callUser->id;
        $transaction->coins = 0;
        $transaction->type = 'call_income';
        $transaction->amount = $actualIncome;
        $transaction->datetime = now();
        $transaction->save();
    }

    // Update call details
    $call->started_time = $startTime->format('H:i:s');
    $call->ended_time = $endTime->format('H:i:s'); 
    $call->coins_spend = $actualCoinsSpend;
    $call->income = $actualIncome;
    $call->update_current_endedtime = now();
    $call->save();

    $callUser = Users::find($call->call_user_id);
    if ($callUser) {
        $callUser->attended_calls += 1;
        if ($callUser->missed_calls > 0) {
            $callUser->missed_calls = 0;
        }
        $callUser->save();
    }

    $receiver = Users::find($call->call_user_id);
    $currentCoinsAfterDeduction = $user->coins;

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
            'update_current_endedtime' => Carbon::parse($call->update_current_endedtime)->format('Y-m-d H:i:s'),
             'available_coins_before_deduction' => $currentCoinsBeforeDeduction, // Show coins before deduction
            'available_coins_after_deduction' => $currentCoinsAfterDeduction, // Show coins after deduction
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
    $user = Users::find($user_id);
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

    // $existingCall = UserCalls::where('user_id', $user_id)
    //     ->where('call_user_id', $call->call_user_id)
    //     ->where('started_time', $started_time)
    //     ->first();

    //     if ($existingCall) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Call already exists with the same details.',
    //         ], 200);
    //     }

    if (!empty($call->ended_time)) {
        return response()->json([
            'success' => false, 
            'message' => 'Call has already been updated.'
        ], 200);
    }

    $user = users::find($user_id);

    // Convert the times to Carbon instances with today's date
    $currentDate = Carbon::now()->format('Y-m-d'); // Current date
    $startTime = Carbon::createFromFormat('Y-m-d H:i:s', "$currentDate $started_time"); // Add the date
    $endTime = Carbon::createFromFormat('Y-m-d H:i:s', "$currentDate $ended_time"); // Add the date

        // Handle cases where the end time is past midnight
        if ($endTime->lessThan($startTime)) {
            $endTime->addDay();
        }

    // Calculate the duration in seconds
    $durationSeconds = $endTime->diffInSeconds($startTime);

    // // Handle calls with less than 10 seconds duration
    // if ($durationSeconds < 10) {
    //     DB::table('not_repeat_call_users')->insert([
    //         'user_id' => $user_id,
    //         'call_user_id' => $call->call_user_id,
    //         'reason' => 'Duration less than 10 seconds',
    //         'datetime' => Carbon::now(),
    //         'created_at' => Carbon::now(),
    //         'updated_at' => Carbon::now(),
    //     ]);
    // }

    $callType = $call->type; // Assuming 'type' field in 'UserCalls' table is either 'audio' or 'video'

   // Calculate the duration in seconds
$durationSeconds = $endTime->diffInSeconds($startTime);

// Ignore the first 10 seconds before counting minutes
$effectiveDurationSeconds = max($durationSeconds - 9, 0);

// Ensure at least 1 minute is counted (ceil rounds up)
$durationMinutes = max(ceil($effectiveDurationSeconds / 60), 1);

$callUser = Users::find($call->call_user_id);
    // Update audio_status or video_status based on call type
       $currentTime = now();
    if ($callType == 'audio') {
        $callUser->audio_status = 1;
        $callUser->last_audio_time_updated = $currentTime; // Update only audio timestamp
    } elseif ($callType == 'video') {
        $callUser->video_status = 1;
        $callUser->last_video_time_updated = $currentTime; // Update only audio timestamp
    }
    $callUser->save();
     $startHour = $startTime->hour;
    $endHour = $endTime->hour;
    $startMinute = $startTime->minute;
    $endMinute = $endTime->minute;
    $startSecond = $startTime->second;
    $endSecond = $endTime->second;
    $currentCoinsBeforeDeduction = $user->coins; // Store coins before deduction

    $maxMinutesAffordable = 0;
    $actualCoinsSpend = 0;
    $actualIncome = 0;
    
    if ($callType == 'audio') {
        $coinsPerMinute = 10;
    } elseif ($callType == 'video') {
        $coinsPerMinute = 60;
    }
    
    // Determine maximum minutes user can afford
    $maxMinutesAffordable = floor($currentCoinsBeforeDeduction / $coinsPerMinute);
    
    // Ensure at least 1 minute is counted, but don't exceed what they can afford
    $effectiveMinutes = min($maxMinutesAffordable, $durationMinutes);
    
    $durationSeconds = $endTime->diffInSeconds($startTime);

// If duration is less than 10 seconds, do not charge
if ($durationSeconds < 10) {
    $income = 0;
    $coins_spend = 0;
    $roundedMinutes = 0;
} else {
    $roundedMinutes = ceil($durationSeconds / 60); // **Round up seconds to full minute**

    $maxMinutesAffordable = floor($currentCoinsBeforeDeduction / $coinsPerMinute);

    // Use the minimum of rounded minutes and what the user can afford
    $effectiveMinutes = min($maxMinutesAffordable, $roundedMinutes);

    $actualCoinsSpend = $effectiveMinutes * $coinsPerMinute;
    $actualIncome = 0;

    for ($i = 0; $i < $effectiveMinutes; $i++) {
        $currentHour = $startHour;
        $currentMinute = $startMinute + $i;

        if ($currentMinute >= 60) {
            $currentMinute -= 60;
            $currentHour++;
        }
        if ($currentHour >= 24) {
            $currentHour -= 24;
        }

        // Determine income per minute based on time slot
        if ($callType == 'audio') {
            $incomePerMinute = ($currentHour >= 16 || $currentHour < 2) ? 1 : 1;
        } else { // Video
            $incomePerMinute = ($currentHour >= 16 || $currentHour < 2) ? 6 : 6;
        }

        $actualIncome += $incomePerMinute;
    }
}

// Deduct coins from the user, ensuring it doesn't go negative
    $user->coins = max(0, $user->coins - $actualCoinsSpend);
    $user->save();

    
    $currentCoinsAfterDeduction = $user->coins;
    
     $deductionTransaction = new Transactions();
    $deductionTransaction->user_id = $user->id;
    $deductionTransaction->coins = $actualCoinsSpend;
    $deductionTransaction->type = 'coins_deduction';
    $deductionTransaction->amount = 0;  
    $deductionTransaction->datetime = now();
    $deductionTransaction->save();
  
    // Update call recipient's balance
    if ($callUser) {
        $callUser->balance += $actualIncome;
        $callUser->total_income += $actualIncome;
        $callUser->last_seen = now();
        $callUser->save();
    
        // Record transaction
        $transaction = new Transactions();
        $transaction->user_id = $callUser->id;
        $transaction->coins = 0;
        $transaction->type = 'call_income';
        $transaction->amount = $actualIncome;
        $transaction->datetime = now();
        $transaction->save();
    }

    // Update call details
    $call->started_time = $startTime->format('H:i:s');
    $call->ended_time = $endTime->format('H:i:s'); 
    $call->coins_spend = $actualCoinsSpend;
    $call->income = $actualIncome;
    $call->update_current_endedtime = now();
    $call->save();


    $callUser = Users::find($call->call_user_id);
    if ($callUser) {
        $callUser->attended_calls += 1;
        if ($callUser->missed_calls > 0) {
            $callUser->missed_calls = 0;
        }
        $callUser->save();
    
    }

    $receiver = Users::find($call->call_user_id);
    
    $currentCoinsAfterDeduction = $user->coins;

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
            'update_current_endedtime' => Carbon::parse($call->update_current_endedtime)->format('Y-m-d H:i:s'),
             'available_coins_before_deduction' => $currentCoinsBeforeDeduction, // Show coins before deduction
            'available_coins_after_deduction' => $currentCoinsAfterDeduction, // Show coins after deduction
        ],
    ], 200);
}


public function every_min_update_connected_call(Request $request)
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
    $user = Users::find($user_id);
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

    // $existingCall = UserCalls::where('user_id', $user_id)
    // ->where('call_user_id', $call->call_user_id)
    // ->where('started_time', $started_time)
    // ->where('ended_time', $ended_time)
    // ->first();

    //     if ($existingCall) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Call already exists with the same details.',
    //         ], 200);
    //     }


    // if (!empty($call->ended_time)) {
    //     return response()->json([
    //         'success' => false, 
    //         'message' => 'Call has already been updated.'
    //     ], 200);
    // }

   // Get today's date
        $currentDate = Carbon::now()->format('Y-m-d'); 

        // Fetch the ended time from the database
        $endedTime = $call->ended_time;

        // Ensure endedTime has a valid format before parsing
        if (strlen($endedTime) == 8) { // If only time is given (H:i:s)
            $endedTime = "$currentDate $endedTime";
        }

        // Convert ended time correctly
        $endTime = Carbon::createFromFormat('Y-m-d H:i:s', $endedTime);

        // Validate and format new end time input
        if (strlen($ended_time) == 8) {
            $ended_time = "$currentDate $ended_time";
        }
        $newEndTime = Carbon::createFromFormat('Y-m-d H:i:s', $ended_time);

        // Validate and format start time input
        if (strlen($started_time) == 8) {
            $started_time = "$currentDate $started_time";
        }
        $startTime = Carbon::createFromFormat('Y-m-d H:i:s', $started_time);

        // Calculate the difference in minutes
        $remainingEndTime = $endTime->diffInMinutes($newEndTime);

        // Calculate the correct new end time
        $calculateEndTime = $startTime->copy()->addMinutes($remainingEndTime);

        // Handle cases where the calculated end time is past midnight
        if ($calculateEndTime->lessThan($startTime)) {
            $calculateEndTime->addDay();
        }

// Calculate the duration in seconds
   $durationSeconds = $calculateEndTime->diffInSeconds($startTime);

    $callType = $call->type; // Assuming 'type' field in 'UserCalls' table is either 'audio' or 'video'

    // Ignore the first 10 seconds before counting minutes
    $effectiveDurationSeconds = max($durationSeconds - 9, 0);

    // Ensure at least 1 minute is counted (ceil rounds up)
    $durationMinutes = max(ceil($effectiveDurationSeconds / 60), 1);

    $callUser = Users::find($call->call_user_id);
    // Update audio_status or video_status based on call type
    
    $currentTime = now();
  if ($callType == 'audio') {
    $callUser->audio_status = 1;
    $callUser->last_audio_time_updated = $currentTime; // Update only audio timestamp
    } elseif ($callType == 'video') {
    $callUser->video_status = 1;
    $callUser->last_video_time_updated = $currentTime; // Update only audio timestamp
    }
    $callUser->save();

    // Determine coin deduction rates
    if ($callType == 'audio') {
        $coinsPerMinute = 10; // Per minute deduction
        $incomePerMinute = 2; // Income per minute
    } elseif ($callType == 'video') {
        $coinsPerMinute = 60;
        $incomePerMinute = 10;
    }

    
    // Calculate total coins spent and earned
    $coins_spend = $durationMinutes * $coinsPerMinute;
    $income = $durationMinutes * $incomePerMinute;

    // Deduct coins only if duration is 10 seconds or more
    if ($durationSeconds >= 10) {
        $user->coins -= $coins_spend;
        $user->save();
    } else {
        $coins_spend = 0;
        $income = 0;
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
    $call->ended_time = $newEndTime->format('H:i:s'); 
    $call->coins_spend = $coins_spend;
    $call->income = $income;
    $call->update_current_endedtime = now();
    $call->save();

    $callUser = Users::find($call->call_user_id);
    if ($callUser) {
        $callUser->attended_calls += 1;
        if ($callUser->missed_calls > 0) {
            $callUser->missed_calls -= 1;
        }
        $callUser->save();
    }

    $receiver = Users::find($call->call_user_id);

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
            'update_current_endedtime' => Carbon::parse($call->update_current_endedtime)->format('Y-m-d H:i:s'),
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

    // Offset and limit for pagination
    $offset = (int) $request->input('offset', 0); // <-- Added
    $limit = (int) $request->input('limit', 10);  // <-- Added

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
    $totalCalls = 0; // <-- Initialize total count

    if ($gender === 'male') {
        $baseQuery = UserCalls::where('user_id', $user_id)
            ->whereNotNull('started_time')
            ->where('started_time', '!=', '')
            ->orderBy('datetime', 'desc');

        // Count total matching calls (even those not in this page)
        $totalCalls = $baseQuery->count(); // <-- Total count before pagination

        // Apply pagination
        $calls = (clone $baseQuery)->skip($offset)->take($limit)->get(); // <-- Use clone to prevent affecting count()

        foreach ($calls as $call) {
            $receiver = Users::find($call->call_user_id);
            if ($receiver) {
                $validCalls[] = $call;
            }
        }
    } else {
        $baseQuery = UserCalls::where('call_user_id', $user_id)
            ->whereNotNull('started_time')
            ->where('started_time', '!=', '')
            ->orderBy('datetime', 'desc');

        $totalCalls = $baseQuery->count(); // <-- Total count before pagination

        $calls = (clone $baseQuery)->skip($offset)->take($limit)->get();

        foreach ($calls as $call) {
            $caller = Users::find($call->user_id);
            if ($caller) {
                $validCalls[] = $call;
            }
        }
    }

    // Prepare the call data
    $callData = [];
    foreach ($validCalls as $call) {
        // Calculate duration
        $duration = '';
        if (!empty($call->started_time) && !empty($call->ended_time)) {
            $startTime = Carbon::parse($call->started_time);
            $endTime = Carbon::parse($call->ended_time);

            // Handle calls crossing midnight
            if ($endTime->lessThan($startTime)) {
                $endTime->addDay();
            }

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
        } else {
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
        } else {
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

    // Return the response with all valid calls
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

    $user = Users::find($user_id);
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

       // Ensure that the call type is checked from the UserCall model
       $call_type = $userCall->type;

       // Validate coins based on call type
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

    // Update the started_time
    $userCall->started_time = $started_time;
    $userCall->save();

    // Find the user and fetch balance time
    $coins = $user ? $user->coins : 0;

    // Calculate remaining time based on call type
    if ($userCall->type === 'video') {
        $minutes = floor($coins / 60); // 60 coins = 1 minute for video
    } else {
        $minutes = floor($coins / 10); // 10 coins = 1 minute for audio
    }

    $seconds = 0;
    $balance_time = sprintf('%d:%02d', $minutes, $seconds);

    // Fetch names and avatar images for caller and receiver
    $caller = Users::find($userCall->user_id);
    $callerAvatar = $caller ? Avatars::find($caller->avatar_id) : '';
    $receiver = Users::find($userCall->call_user_id);

    // Update audio_status or video_status for receiver only
    if ($receiver) {
        if ($userCall->type === 'audio') {
            $receiver->audio_status = 0;
        } else {
            $receiver->video_status = 0;
        }
        $receiver->save();
    }

    $receiverAvatar = $receiver ? Avatars::find($receiver->avatar_id) : '';
    $callerImageUrl = ($callerAvatar && $callerAvatar->image) ? asset('storage/app/public/' . $callerAvatar->image) : '';
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
    // Authenticate user
    $authenticatedUser = auth('api')->user();
    if (!$authenticatedUser) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. Please provide a valid token.',
        ], 401);
    }

    $user_id = $request->input('user_id');
    $call_type = $request->input('call_type');

    // Validate inputs
    if (empty($user_id)) {
        return response()->json([
            'success' => false,
            'message' => 'user_id is empty.',
        ], 200);
    }

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

    // Fetch the latest user data including coins
    $user = Users::find($user_id);
    
    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'User not found for the provided user_id.',
        ], 200);
    }

    // Get the call details from the user_calls table
    $call = DB::table('user_calls')
        ->where('user_id', $user_id)
        ->where('type', $call_type)
        ->whereNull('ended_time')  // Ongoing call
        ->latest()
        ->first();

    $elapsed_minutes = 0;
    $elapsed_seconds = 0;

    if ($call && $call->started_time) {
        $started_time = Carbon::parse($call->started_time);
        $current_time = Carbon::now();
        $elapsed_seconds = $current_time->diffInSeconds($started_time);

        $elapsed_minutes = floor($elapsed_seconds / 60);
        $elapsed_seconds %= 60;
    }

    // Determine the coin-to-time conversion rate
    $conversion_rate = ($call_type === 'video') ? 60 : 10;

    // Get the latest coin balance (after recharge)
    $user->refresh();
    $coins = $user->coins;

    // Calculate remaining time accurately
    $total_seconds = ($coins / $conversion_rate) * 60;  // Convert remaining coins to seconds

    // Subtract elapsed time from the total time
    $remaining_seconds = max(0, $total_seconds - ($elapsed_minutes * 60 + $elapsed_seconds));

    // Calculate remaining minutes and seconds
    $remaining_minutes = floor($remaining_seconds / 60);
    $remaining_seconds %= 60;

    // Format remaining time with minutes and seconds
    $balance_time = sprintf('%d:%02d', $remaining_minutes, $remaining_seconds);

    // Return the response
    return response()->json([
        'success' => true,
        'message' => 'Remaining Time Listed successfully.',
        'data' => [
            'remaining_time' => $balance_time,       // Shows minutes and seconds
            'elapsed_time' => sprintf('%d:%02d', $elapsed_minutes, $elapsed_seconds),
            'latest_coins' => $coins,
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

    $user->last_seen = now();
    $user->save();

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
    // if (!preg_match("/^[A-Z]{4}0[A-Z0-9]{6}$/", $ifsc)) {
    //     return response()->json([
    //         'success' => false,
    //         'message' => 'Invalid IFSC code. It should be 11 characters long with the 5th character as 0.',
    //     ], 200);
    // }

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
            'interests' => $user->interests ?? '',
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
            'upi_id' => $user->upi_id ?? '',
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

    // Update UPI ID in users table
    $user->upi_id = $upi_id;
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
            'upi_id' => $user->upi_id,
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
    
    if ($amount < 10) {
        return response()->json([
            'success' => false,
            'message' => 'Minimum withdrawal amount is 10 Rs.',
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
    // Check if UPI or bank transfer is enabled in appsettings
        $appSettings = Appsettings::first();
        if ($type === 'upi_transfer' && $appSettings->upi == 0) {
            return response()->json([
                'success' => false,
                'message' => 'UPI transfer is disabled.',
            ], 200);
        }

        if ($type === 'bank_transfer' && $appSettings->bank == 0) {
            return response()->json([
                'success' => false,
                'message' => 'Bank transfer is disabled.',
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
        if (empty($user->upi_id)) {
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
                'ratings' => number_format($rating->ratings, 1) ?? '',// Format ratings to one decimal place
                'title' => $rating->title ?? '',
                'description' => $rating->description ?? '',
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
    // ✅ Extract and validate request data
    $user_id = $request->input('user_id');
    $coins_id = $request->input('coins_id');
    $order_id = $request->input('order_id');
    $status = $request->input('status');
    $message = $request->input('message');

    if (empty($user_id)) {
        return response()->json(['success' => false, 'message' => 'user_id is empty.'], 400);
    }

    if (empty($coins_id)) {
        return response()->json(['success' => false, 'message' => 'coins_id is empty.'], 400);
    }

    if (empty($order_id)) {
        return response()->json(['success' => false, 'message' => 'coins_id is empty.'], 400);
    }

    if (empty($status)) {
        return response()->json(['success' => false, 'message' => 'status is empty.'], 400);
    }

    $user = Users::find($user_id);
    if (!$user) {
        return response()->json(['success' => false, 'message' => 'User not found.'], 404);
    }

    $coins_entry = Coins::find($coins_id);
    if (!$coins_entry) {
        return response()->json(['success' => false, 'message' => 'Coins entry not found.'], 404);
    }

    $existing_order = Orders::where('user_id', $user_id)
        ->where('coins_id', $coins_id)
        ->where('order_id', $order_id)
        ->where('status', 0)
        ->latest('datetime')
        ->first();

    if ($existing_order) {
        // Update existing order status and message
        $existing_order->status = $status;
        $existing_order->message = $message;
        $existing_order->datetime = now();

        if (!$existing_order->save()) {
            return response()->json(['success' => false, 'message' => 'Failed to update existing order.'], 500);
        }

        if ($status == 1) {
            // ✅ Add coins only when status is 1
            $coins = $coins_entry->coins;
            $price = $coins_entry->price;

            // Update user balance
            $user->coins += $coins;
            $user->total_coins += $coins;

            if (!$user->save()) {
                return response()->json(['success' => false, 'message' => 'Failed to update user coins.'], 500);
            }

            // Save transaction
            $transaction = new Transactions();
            $transaction->user_id = $user_id;
            $transaction->coins = $coins;
            $transaction->type = 'add_coins';
            $transaction->amount = $price;
            $transaction->payment_type = 'Credit';
            $transaction->datetime = now();

            if (!$transaction->save()) {
                return response()->json(['success' => false, 'message' => 'Failed to save transaction.'], 500);
            }
        }

        // ✅ Return successful response
        $user = Users::select('name', 'coins', 'total_coins')->find($user_id);

        return response()->json([
            'success' => true,
            'message' => $status == 1 ? 'Coins added successfully.' : 'Order status updated, no coins added.',
            'data' => [
                'name' => $user->name,
                'coins' => (string) $user->coins,
                'total_coins' => (string) $user->total_coins,
            ],
        ], 200);
    } else {
        return response()->json(['success' => false, 'message' => 'No existing order found.'], 404);
    }
}




public function try_coins(Request $request)
{
    // Extract request data
    $user_id = $request->input('user_id'); 
    $coins_id = $request->input('coins_id');
    $order_id = $request->input('order_id');
    $status = $request->input('status');
    $message = $request->input('message');

    // Validate user_id
    if (empty($user_id)) {
        return response()->json([
            'success' => false,
            'message' => 'user_id is empty.',
        ], 400);
    }

    // Validate coins_id
    if (empty($coins_id)) {
        return response()->json([
            'success' => false,
            'message' => 'coins_id is empty.',
        ], 400);
    }

    if (empty($order_id)) {
        return response()->json([
            'success' => false,
            'message' => 'order_id is empty.',
        ], 400);
    }

    if (!isset($status)) {
        return response()->json([
            'success' => false,
            'message' => 'status is empty.',
        ], 400);
    }
    

    // Check if user exists
    $user = Users::find($user_id);
    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'User not found.',
        ], 404);
    }

    // Check if coins entry exists
    $coins_entry = Coins::find($coins_id);
    if (!$coins_entry) {
        return response()->json([
            'success' => false,
            'message' => 'Coins entry not found.',
        ], 404);
    }

    // Get coin details
    $coins = $coins_entry->coins;
    $price = $coins_entry->price;

    $order = new Orders();
    $order->user_id = $user_id;
    $order->coins_id = $coins_id;
    $order->order_id = $order_id;
    $order->status = $status;                  // Status set to 1
    $order->price = $price;   
    $order->message = $message;           // Use coins price
    $order->datetime = now();            // Current timestamp

    if (!$order->save()) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to insert order.',
        ], 500);
    }

    $user = Users::select('name', 'coins', 'total_coins')->find($user_id);

    return response()->json([
        'success' => true,
        'message' => 'Orders Created Successfully.',
        'data' => [
            'name' => $user->name,
            'coins' => (string) $user->coins,
            'total_coins' => (string) $user->total_coins,
        ],
    ], 200);
}

public function cron_jobs(Request $request)
{

    $currentTime = Carbon::now('Asia/Kolkata');
    $currentDay = $currentTime->format('l'); // Get current day (e.g., Monday, Tuesday)
    $currentHourMinute = $currentTime->format('H:i'); // Get current time (HH:MM)

     $expiredConnections = DB::table('random_female_connecteds')->get();
    
        foreach ($expiredConnections as $row) {
            if (isset($row->connected_time)) {
                $connectedTime = Carbon::parse($row->connected_time, 'Asia/Kolkata');
                $diffInSeconds = $currentTime->diffInSeconds($connectedTime);
    
                if ($diffInSeconds > 60) { // More than 1 minute
                    DB::table('random_female_connecteds')
                        ->where('user_id', $row->user_id)
                        ->where('female_user_id', $row->female_user_id)
                        ->delete();
                }
            }
        }
        
           DB::table('users')
        ->where('gender', 'female')
        ->where('missed_calls', '>=', 5)
        ->update([
            'audio_status' => 0,
            'video_status' => 0,
            'missed_calls' => 0,
        ]);

   // Find all notifications scheduled for the current day and time or "all"
        $notifications = ScreenNotifications::where(function ($query) use ($currentDay) {
            $query->where('day', $currentDay)
                ->orWhere('day', 'all'); // Include notifications for "all" days
        })
        ->where('time', $currentHourMinute)
        ->get();

        if ($notifications->isNotEmpty()) {
            $notifications->each(function ($notification) {
                // Set default values if gender or language is missing
                $gender = $notification->gender ?? 'all';
                $language = $notification->language ?? 'all';

                // Define filters based on gender and language
                $filters = [];

                if ($gender !== 'all' && $language !== 'all') {
                    $filters[] = ["field" => "tag", "key" => "gender_language", "relation" => "=", "value" => "{$gender}_{$language}"];
                } elseif ($gender !== 'all') {
                    $filters[] = ["field" => "tag", "key" => "gender", "relation" => "=", "value" => "{$gender}"];
                } elseif ($language !== 'all') {
                    $filters[] = ["field" => "tag", "key" => "language", "relation" => "=", "value" => "{$language}"];
                }

                // If both gender and language are 'all', send to everyone
                if ($gender === 'all' && $language === 'all') {
                    $filters = []; // No filters means send to all users
                }

                // Prepare notification payload
                $payload = [
                    "app_id" => "2c7d72ae-8f09-48ea-a3c8-68d9c913c592",
                    "filters" => $filters,
                    "headings" => ["en" => $notification->title],
                    "contents" => ["en" => $notification->description],
                    "small_icon" => "notification_icon",
                    "large_icon" => $notification['logo'] ? "https://himaapp.in/storage/app/public/{$notification['logo']}" : "https://himaapp.in/storage/uploads/logo/notification_icon.webp",
                    "big_picture" => $notification['image'] ? "https://himaapp.in/storage/app/public/{$notification['image']}" : "",
                ];

                // Send notification via OneSignal
                OneSignal::sendNotificationCustom($payload);
            });
        }

}


// public function cron_updates(Request $request)
// {
//     // Reset missed_calls, attended_calls, and avg_call_percentage for all users
//     Users::query()->update([
//         'missed_calls' => 0,
//         'attended_calls' => 0,
//         'audio_status' => 0,
//         'video_status' => 0,
//         'avg_call_percentage' => 100,
//     ]);

//     // Insert datetime into cron_jobs_update table
//     DB::table('cron_jobs_update')->insert([
//         'datetime' => Carbon::now(),
//     ]);
// }

    
public function explaination_video_list(Request $request)
{

    $language = $request->input('language');
    
    if (empty($language)) {
        return response()->json([
            'success' => false,
            'message' => 'language is empty.',
        ], 200);
    }

    $explainationVideos = explaination_video_links::where('language', $language)
                 ->get();

    if ($explainationVideos->isEmpty()) {
        return response()->json([
            'success' => false,
            'message' => 'No Explaination Video found for this user.',
        ], 200);
    }

    $languageData = [];
    foreach ($explainationVideos as $video) {
    foreach ($explainationVideos as $language) {
        $languageData[] = [
            'id' => $language->id,
            'language' => $language->language,
            'video_link' => $language->video_link,
            'updated_at' => $language->updated_at->format('Y-m-d H:i:s'),
            'created_at' => $language->created_at->format('Y-m-d H:i:s'),
        ];
    }

    return response()->json([
        'success' => true,
        'message' => 'Explaination Video Link list retrieved successfully.',
        'data' => $languageData,
    ], 200);
}

}

public function gifts_list(Request $request)
{
    // Retrieve all gifts
    $gifts = Gifts::all();

    if ($gifts->isEmpty()) {
        return response()->json([
            'success' => false,
            'message' => 'No gifts found.',
        ], 200);
    }

    // Prepare the data to be returned
    $giftsData = [];
    foreach ($gifts as $item) {
        $GiftUrl = ($item->gift_icon) ? asset('storage/app/public/' . $item->gift_icon) : '';

        $giftsData[] = [
            'id' => $item->id,
            'gift_icon' => $GiftUrl,
            'coins' => $item->coins,
            'updated_at' => $item->updated_at->format('Y-m-d H:i:s'),
            'created_at' => $item->created_at->format('Y-m-d H:i:s'),
        ];
    }

    return response()->json([
        'success' => true,
        'message' => 'Gifts listed successfully.',
        'data' => $giftsData,
    ], 200);
}

public function send_gifts(Request $request)
{
    $authenticatedUser = auth('api')->user();
    if (!$authenticatedUser) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. Please provide a valid token.',
        ], 401);
    }
    // Retrieve input values
    $user_id = $request->input('user_id');
    $receiver_id = $request->input('receiver_id');
    $gift_id = $request->input('gift_id');

    // Validate individual inputs with separate error messages
    if (empty($user_id)) {
        return response()->json([
            'success' => false,
            'message' => 'user_id is required.',
        ], 200);
    }

    if (empty($receiver_id)) {
        return response()->json([
            'success' => false,
            'message' => 'receiver_id is required.',
        ], 200);
    }

    if (empty($gift_id)) {
        return response()->json([
            'success' => false,
            'message' => 'gift_id is required.',
        ], 200);
    }

    // Fetch sender and receiver
    $sender = Users::find($user_id);
    $receiver = Users::find($receiver_id);

    if (!$sender) {
        return response()->json([
            'success' => false,
            'message' => 'Sender not found.',
        ], 200);
    }

    if (!$receiver) {
        return response()->json([
            'success' => false,
            'message' => 'Receiver not found.',
        ], 200);
    }
    
   if (strtolower($receiver->gender) !== 'female') {
       return response()->json([
        'success' => false,
        'message' => 'Gifts can only be sent to female users.',
        ], 200);
    }

    // Fetch the gift
    $gift = Gifts::find($gift_id);
    if (!$gift) {
        return response()->json([
            'success' => false,
            'message' => 'Gift not found.',
        ], 200);
    }

       // Get gift cost in coins
       $gift_coins = $gift->coins; // Example: 20 coins

       // Convert coins to rupees (20 coins = 2 rupees, so 1 coin = 0.1 rupee)
       $amount_in_rupees = $gift_coins * 0.1;
   
       // Check if sender has enough coins
       if ($sender->coins < $gift_coins) {
           return response()->json([
               'success' => false,
               'message' => 'Insufficient coins to send this gift.',
           ], 400);
       }
   
       // Deduct coins from sender
       $sender->coins -= $gift_coins;
       $sender->save();
   
       // Credit equivalent rupees to receiver
       $receiver->balance += $amount_in_rupees;
       $receiver->save();
   
       // Record transaction for sender (debit)
       Transactions::create([
           'user_id' => $user_id,
           'coins' => -$gift_coins,
           'type' => 'send_gift',
           'amount' => 0,
           'datetime' => now(),
       ]);

    // Record transaction for receiver (credit only)
    Transactions::create([
        'user_id' => $receiver_id,
        'coins' => 0,
        'type' => 'receive_gift',
        'amount' => $amount_in_rupees,
        'datetime' => now(),
    ]);

    // Get gift icon URL
    $giftIconUrl = ($gift->gift_icon) ? asset('storage/app/public/' . $gift->gift_icon) : '';

    // Return success response
    return response()->json([
        'success' => true,
        'message' => 'Gift sent successfully!',
        'data' => [
            'sender_name' => $sender->name,
            'receiver_name' => $receiver->name,
            'gift_id' => $gift->id,
            'gift_icon' => $giftIconUrl,
            'gift_coins' => $gift_coins,
        ],
    ], 200);
}
    
public function whatsapplink_list(Request $request)
{
    $language = $request->input('language');
    
    if (empty($language)) {
        return response()->json([
            'success' => false,
            'message' => 'language is empty.',
        ], 200);
    }

    $whatsapplink = Whatsapplink::where('language', $language)->get();

    if ($whatsapplink->isEmpty()) {
        return response()->json([
            'success' => false,
            'message' => 'No Whatsapp Link found for this language.',
        ], 200);
    }

    $whatsapplinkData = [];

    // ✅ Correct iteration over the collection
    foreach ($whatsapplink as $link) {
        $whatsapplinkData[] = [
            'id' => $link->id,                              // Use $link instead of $whatsapplink
            'language' => $link->language,
            'link' => $link->link,
            'updated_at' => $link->updated_at->format('Y-m-d H:i:s'),
            'created_at' => $link->created_at->format('Y-m-d H:i:s'),
        ];
    }

    return response()->json([
        'success' => true,
        'message' => 'Whatsapp Link list retrieved successfully.',
        'data' => $whatsapplinkData,
    ], 200);

}

public function send_fcm_token(Request $request)
{
    $authenticatedUser = auth('api')->user();
    if (!$authenticatedUser) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. Please provide a valid token.',
        ], 401);
    }

    // Retrieve input values
    $user_id = $request->input('user_id');
    $token = $request->input('token');

    // Validate individual inputs with separate error messages
    if (empty($user_id)) {
        return response()->json([
            'success' => false,
            'message' => 'user_id is required.',
        ], 400);
    }

       if ($token === null) {
        return response()->json([
            'success' => false,
            'message' => 'token is required.',
        ], 400);
    }

    // Check if user exists
    $user = Users::find($user_id);
    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'User not found.',
        ], 404);
    }

    // Insert or update FCM token
    $fcmtoken = fcm_tokens::updateOrCreate(
        ['user_id' => $user_id],
        ['token' => $token]
    );

    return response()->json([
        'success' => true,
        'message' => 'Token saved successfully!',
        'data' => $fcmtoken,
    ], 200);
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

public function user_avatar_image(Request $request)
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


        return response()->json([
            'success' => true,
            'message' => 'User Avatar retrieved successfully.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'user_gender' => $user->gender,
                'avatar_id' => (int) $user->avatar_id,
                'image' => $imageUrl ?? '',
            ],
        ], 200);
    }

public function update_pancard(Request $request)
{
    $authenticatedUser = auth('api')->user();
    if (!$authenticatedUser) {
        return response()->json([
            'success' => false,
            'message' => 'Unauthorized. Please provide a valid token.',
        ], 401);
    }

    $user_id = $request->input('user_id');
    $pancard_name = $request->input('pancard_name');
    $pancard_number = $request->input('pancard_number');

    // Check required fields
    if (empty($user_id)) {
        return response()->json([
            'success' => false,
            'message' => 'user_id is empty.',
        ], 200);
    }

      // Get user
      $user = Users::find($user_id);
      if (!$user) {
          return response()->json([
              'success' => false,
              'message' => 'User not found.',
          ], 200);
      }
  
      // Check if user is female
      if (strtolower($user->gender) !== 'female') {
          return response()->json([
              'success' => false,
              'message' => 'Only female users can update PAN card information.',
          ], 200);
      }
  

    if (empty($pancard_name)) {
        return response()->json([
            'success' => false,
            'message' => 'pancard_name is empty.',
        ], 200);
    }

    if (empty($pancard_number)) {
    return response()->json([
        'success' => false,
        'message' => 'pancard_number is empty.',
    ], 200);
    }

    // PAN card format validation
    if (!preg_match("/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/", strtoupper($pancard_number))) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid PAN card number format.',
        ], 200);
    }

    $existingPan = Users::where('pancard_number', $pancard_number)
                        ->where('id', '!=', $user_id)
                        ->first();
    if ($existingPan) {
        return response()->json([
            'success' => false,
            'message' => 'This PAN card number already exists.',
        ], 200);
    }
 
     if (!empty($user->pancard_name) && !empty($user->pancard_number)) {
        return response()->json([
            'success' => false,
            'message' => 'PAN card details have already been submitted and cannot be updated.',
        ], 200);
    }

    // Save PAN card info
    $user->pancard_name = $pancard_name;
    $user->pancard_number = $pancard_number;
    $user->save();

    $message = 'Pancard Details Saved Successfully.';


    if (!empty($user->referred_by)) {
        $referrer = Users::where('refer_code', $user->referred_by)->first();
    
        if ($referrer && strtolower($referrer->gender) === 'female') {
            if (!empty($referrer->pancard_name) && !empty($referrer->pancard_number)) {
    
                // Check if referral bonus already given
                $alreadyRewarded = DB::table('refer_bonus')
                    ->where('user_id', $referrer->id)
                    ->where('referred_user_id', $user->id)
                    ->exists();
    
                if (!$alreadyRewarded) {
                    $referralSettings = News::latest()->first();
                    $coinsPerReferral = $referralSettings->coins_per_referral ?? 0;
                    $moneyPerReferral = $referralSettings->money_per_referral ?? 0;
    
                    $referrer->balance += $moneyPerReferral;
                    $referrer->total_referrals += 1;
                    $referrer->save();
    
                    // Add transaction
                    Transactions::create([
                        'user_id' => $referrer->id,
                        'type' => 'refer_bonus',
                        'amount' => $moneyPerReferral,
                        'datetime' => now(),
                    ]);
    
                    // Record in refer_bonus table
                    DB::table('refer_bonus')->insert([
                        'user_id' => $referrer->id,
                        'referred_user_id' => $user->id,
                        'datetime' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
    
    // Prepare user data for response
    $avatar = Avatars::find($user->avatar_id);
    $gender = $avatar ? $avatar->gender : '';

    $imageUrl = ($avatar && $avatar->image) ? asset('storage/app/public/' . $avatar->image) : '';
    $voicePath = $user && $user->voice ? asset('storage/app/public/voices/' . $user->voice) : '';

    return response()->json([
        'success' => true,
        'message' => $message,
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
            'upi_id' => $user->upi_id,
            'pancard_name' => $user->pancard_name ?? '',
            'pancard_number' => $user->pancard_number ?? '',
            'datetime' => Carbon::parse($user->datetime)->format('Y-m-d H:i:s'),
            'updated_at' => Carbon::parse($user->updated_at)->format('Y-m-d H:i:s'),
            'created_at' => Carbon::parse($user->created_at)->format('Y-m-d H:i:s'),
        ],
    ], 200);
}
}