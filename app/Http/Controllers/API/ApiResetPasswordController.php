<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;


class ApiResetPasswordController extends Controller
{
    /**
     * Handle password reset request via API.
     */
    public function reset(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'email' => 'required|email|exists:ec_customers,email',
            'password' => 'required|min:8|confirmed',
            'token' => 'required',
        ]);

        // Attempt to reset the password
        $status = Password::broker('customers')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($customer, $password) {
                $customer->forceFill([
                    'password' => Hash::make($password),
                ])->save();
            }
        );

        // Handle the response
        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Password reset successfully.'], 200);
        }

        // Handle errors
        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }
}
