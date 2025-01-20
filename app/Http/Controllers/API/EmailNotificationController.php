<?php

namespace App\Http\Controllers\Api;

use Botble\Ecommerce\Notifications\ConfirmEmailNotification;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Notification;

class EmailNotificationController extends Controller
{
    /**
     * Send the email confirmation notification.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function sendConfirmationEmail(Request $request)
    {
        // Validate user input
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        // Find the user by ID
        $user = User::find($request->user_id);

        // Send the notification
        $user->notify(new ConfirmEmailNotification());

        return response()->json([
            'message' => 'Confirmation email sent successfully.',
        ]);
    }
}
