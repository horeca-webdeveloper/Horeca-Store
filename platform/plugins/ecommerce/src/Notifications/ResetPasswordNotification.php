<?php


namespace Botble\Ecommerce\Notifications;

use Botble\Base\Facades\EmailHandler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $token)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        // Manually construct the reset link with localhost:300
        $resetLink = 'https://thehorecastore.co/password/reset/' . $this->token . '?email=' . urlencode($notifiable->email);

        // Configure the email handler with the reset link
        $emailHandler = EmailHandler::setModule(ECOMMERCE_MODULE_SCREEN_NAME)
            ->setType('plugins')
            ->setTemplate('password-reminder')
            ->addTemplateSettings(ECOMMERCE_MODULE_SCREEN_NAME, config('plugins.ecommerce.email', []))
            ->setVariableValue('reset_link', $resetLink);

        // Return the email message
        return (new MailMessage())
            ->view(['html' => new HtmlString($emailHandler->getContent())])
            ->subject($emailHandler->getSubject());
    }
}

// namespace Botble\Ecommerce\Notifications;

// use Botble\Base\Facades\EmailHandler;
// use Illuminate\Bus\Queueable;
// use Illuminate\Contracts\Queue\ShouldQueue;
// use Illuminate\Notifications\Messages\MailMessage;
// use Illuminate\Notifications\Notification;
// use Illuminate\Support\HtmlString;

// class ResetPasswordNotification extends Notification implements ShouldQueue
// {
//     use Queueable;

//     public function __construct(public string $token)
//     {
//     }

//     public function via($notifiable): array
//     {
//         return ['mail'];
//     }

//     public function toMail($notifiable): MailMessage
//     {
//         $emailHandler = EmailHandler::setModule(ECOMMERCE_MODULE_SCREEN_NAME)
//             ->setType('plugins')
//             ->setTemplate('password-reminder')
//             ->addTemplateSettings(ECOMMERCE_MODULE_SCREEN_NAME, config('plugins.ecommerce.email', []))
//             ->setVariableValue('reset_link', route('customer.password.reset.update', ['token' => $this->token, 'email' => request()->input('email')]));

//         return (new MailMessage())
//             ->view(['html' => new HtmlString($emailHandler->getContent())])
//             ->subject($emailHandler->getSubject());
//     }
// }
