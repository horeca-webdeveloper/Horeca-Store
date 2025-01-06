<?php


namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;


class ForgotPasswordApiController extends Controller
{
    /**
     * Handle the forgot password request.
     *
     * @return JsonResponse
     * @throws ValidationException
     */
    public function sendResetLinkEmail(): JsonResponse
    {
        $data = request()->validate([
            'email' => 'required|email|exists:ec_customers,email',
        ]);

        // Attempt to send the password reset link
        $status = Password::broker('customers')->sendResetLink($data);

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => trans($status),
            ], 200);
        }

        throw ValidationException::withMessages([
            'email' => trans($status),
        ]);
    }
}
