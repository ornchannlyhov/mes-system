<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Mail\OtpMail;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class ForgotPasswordController extends Controller
{
    public function sendOtp(ForgotPasswordRequest $request)
    {
        // Validation is handled by ForgotPasswordRequest

        $email = $request->email;
        $otp = rand(100000, 999999);

        // Invalidate old codes
        DB::table('verification_codes')
            ->where('email', $email)
            ->where('type', 'password_reset')
            ->delete();

        DB::table('verification_codes')->insert([
            'email' => $email,
            'code' => $otp,
            'type' => 'password_reset',
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Mail::to($email)->send(new OtpMail((string) $otp, 'Your password reset code is:'));

        return response()->json(['message' => 'Reset code sent to your email.']);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        // Validation is handled by ResetPasswordRequest

        $record = DB::table('verification_codes')
            ->where('email', $request->email)
            ->where('code', $request->code)
            ->where('type', 'password_reset')
            ->where('expires_at', '>', now())
            ->first();

        if (!$record) {
            return response()->json(['message' => 'Invalid or expired reset code.'], 400);
        }

        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        // Clean up
        DB::table('verification_codes')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Password has been reset successfully.']);
    }
}
