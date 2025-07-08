<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\UserLoginRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\UserRegisterRequest;
use App\Models\User;

class AuthController extends Controller
{
    public function register(UserRegisterRequest $request)
    {

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        event(new Registered($user));

        return response()->json([
            'success' => true,
            'message' => 'User Register Successfully. Please check your email for verification',
            'data' => [
                'user' => [
                    'uuid' => $user->uuid,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at
                ]
            ],
            'token' => $token
        ], 201);
    }


    public function login(UserLoginRequest $request)
    {

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember_me', false);


        if (!Auth::attempt($credentials, $remember)) {

            return response()->json([
                'success' => false,
                'message' => 'Invalid Credentials'
            ], 401);
        }

        $user = Auth::user();

        if (!$user->hasVerifiedEmail()) {
            Auth::logout();
            return response()->json([
                'success' => false,
                'message' => 'Verify Email before login'
            ]);
        }

        $user->update(['last_login_at' => now()]);

        $token = $remember ?  $user->createToken('auth-token', ['*'], now())->addDays(30) : $user->createToken('auth-token');

        return response()->json([
            'success' => true,
            'message' => 'Login Successfully',
            'data' => [
                'user' => [
                    'uuid' => $user->uuid,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'last_login_at' => $user->last_login_at
                ],

                'token' => $token->plainTextToken,

            ]

        ]);
    }


    // public function sendVerificationEmail(Request $request)
    // {

    //     $user = $request->user();

    //     if ($user->hasVerifiedEmail()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Email already verfied'
    //         ], 400);
    //     }

    //     $user->sendEmailVerificationNotification();

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Email Verification sent successfully'
    //     ]);
    // }


    public function logout(Request $request){
        $user = $request->user();
        $user->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout was successful'
        ]);
    }


    public function verifyEmail(Request $request){
        $user = User::findByUuid($request->route('id'));

        if(!$user){
            return response()->json([
                'success' => false,
                'message' => 'user not found'
            ], 404);
        }

        if(!hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()) )){
         return response()->json([
            'success' => false,
            'message' => 'The link is invalid'
         ]);
        }

        if($user->hasVerifiedEmail()){
          return response()->json([
            'success' => false,
            'message' => 'Email already Verified'
          ], 400);
        }

        if($user->markEmailAsVerified()){
            event(new Verified($user));
        }

        return response()->json([
            'success' => true,
            'message' => 'Email has been verIfied'
        ]);
    }


    public function sendPasswordResetLink(ResetPasswordRequest $request){
       
        $status = Password::sendResetLink($request->only('email'));

        if($status === Password::RESET_LINK_SENT){
            return response()->json([
                'success' => true,
                'message' => 'Password Reset Link sent successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Password Reset Link was unable to send'
        ], 500);
    }

    public function resetPassword(ChangePasswordRequest $request){
       
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password){
              $user->forceFill([
                'password' => Hash::make($password)
              ]);
              $user->save();

              $user->tokens()->delete();
            }
        );

        if($status === Password::PASSWORD_RESET){
            return response()->json([
                'success' => true,
                'message' => 'Password Reset Successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Password Reset Unsuccessful'
        ], 400);
    }


    public function profile(Request $request){
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'uuid' => $user->uuid,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'last_login_at' => $user->last_login_at,
                    'created_at' => $user->created_at,
                ]
            ]
        ]);
    }
}
