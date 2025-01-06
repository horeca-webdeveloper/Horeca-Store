<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class JsonArrayRule implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        // Check if the value is an array or a JSON string that decodes to an array
        if (is_array($value)) {
            return true;
        }

        // Check if JSON string can be decoded to an array
        $decodedValue = json_decode($value, true);
        return json_last_error() === JSON_ERROR_NONE && is_array($decodedValue);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute must be a valid JSON array.';
    }
}
