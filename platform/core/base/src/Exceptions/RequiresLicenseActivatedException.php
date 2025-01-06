<?php

namespace Botble\Base\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class RequiresLicenseActivatedException extends HttpException
{
    public function __construct($message = 'License activation is not required.')
    {
        // You can either:
        // - Call the parent constructor with a non-blocking status code (e.g., 200),
        // - Log a message, or
        // - Leave this method empty to bypass the exception entirely.

        // Option 1: Just log a message
        // \Log::warning($message);

        // Option 2: Or simply do nothing to prevent the exception
        // This bypasses the license activation requirement.
    }
}
