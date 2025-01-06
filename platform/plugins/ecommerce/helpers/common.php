<?php

use Botble\Ecommerce\Facades\EcommerceHelper;
use Botble\Ecommerce\Models\StoreLocator;
use Botble\Media\Facades\RvMedia;
use Botble\Ecommerce\Models\UnitOfMeasurement;

if (! function_exists('array_equal')) {
    function array_equal(array $first, array $second): bool
    {
        if (count($first) != count($second)) {
            return false;
        }

        return ! array_diff($first, $second) && ! array_diff($second, $first);
    }
}

if (! function_exists('esc_sql')) {
    function esc_sql(int|string|null $string): string
    {
        return app('db')->getPdo()->quote($string);
    }
}

if (! function_exists('rv_get_image_list')) {
    function rv_get_image_list(array $imagesList, array $sizes): array
    {
        $result = [];
        foreach ($sizes as $size) {
            $images = [];

            foreach ($imagesList as $url) {
                $images[] = RvMedia::getImageUrl($url, $size);
            }

            $result[$size] = $images;
        }

        return $result;
    }
}
if (! function_exists('get_ecommerce_setting')) {
    function get_ecommerce_setting(string $key, bool|int|string|null $default = ''): array|int|string|null
    {
        return setting(EcommerceHelper::getSettingPrefix() . $key, $default);
    }
}

if (! function_exists('get_shipment_code')) {
    function get_shipment_code(int|string $shipmentId): string
    {
        return '#' . (config('plugins.ecommerce.order.default_order_start_number') + intval($shipmentId));
    }
}

if (! function_exists('get_primary_store_locator')) {
    function get_primary_store_locator(): StoreLocator
    {
        return StoreLocator::query()->firstOrNew(['is_primary' => 1]);
    }
}

if (! function_exists('ecommerce_convert_weight')) {
    function ecommerce_convert_weight(?float $weight): float
    {
        switch (get_ecommerce_setting('store_weight_unit', 'g')) {
            case 'g':
                break;
            case 'kg':
                $weight = $weight * 1000;

                break;
        }

        return (float) $weight;
    }
}

if (! function_exists('ecommerce_convert_width_height')) {
    function ecommerce_convert_width_height(?float $data): float
    {
        switch (get_ecommerce_setting('store_width_height_unit', 'cm')) {
            case 'cm':
                break;
            case 'm':
                $data = $data * 100;

                break;
        }

        return (float) $data;
    }
}

if (! function_exists('ecommerce_weight_unit')) {
    function ecommerce_weight_unit(bool $full = false): string
    {
        $unit = (string) get_ecommerce_setting('store_weight_unit', 'g');

        if (! $full) {
            return $unit;
        }

        switch ($unit) {
            case 'g':
                $unit = __('grams');

                break;
            case 'kg':
                $unit = __('kilograms');

                break;
        }

        return $unit;
    }
}

// if (! function_exists('ecommerce_width_height_unit')) {
//     function ecommerce_width_height_unit(bool $full = false): string
//     {
//         $unit = (string) get_ecommerce_setting('store_width_height_unit', 'cm');

//         if (! $full) {
//             return $unit;
//         }

//         switch ($unit) {
//             case 'cm':
//                 $unit = __('centimeters');

//                 break;
//             case 'm':
//                 $unit = __('meters');

//                 break;
//         }

//         return $unit;
//     }
// }

if (!function_exists('ecommerce_width_height_unit')) {
    // function ecommerce_width_height_unit(bool $full = false): string
    // {
    //     // Define available units
    //     $availableUnits = [
    //         'cm' => __('cm'),
    //         'm' => __('m'),
    //         'in' => __('in'),
    //         'ft' => __('ft'),
    //     ];

    //     // Get the currently selected unit from settings
    //     $unit = (string) get_ecommerce_setting('store_width_height_unit', 'cm');

    //     if (!array_key_exists($unit, $availableUnits)) {
    //         $unit = 'cm'; // Default to cm if the unit is not found
    //     }

    //     if (!$full) {
    //         return $unit;
    //     }

    //     switch ($unit) {
    //         case 'cm':
    //             return __('centimeters');
    //         case 'm':
    //             return __('meters');
    //         case 'in':
    //             return __('inches');
    //         case 'ft':
    //             return __('feet');
    //         default:
    //             return __('centimeters'); // Default case
    //     }
    // }

    // function ecommerce_unit_dropdown(bool $full = false): string
    // {
    //     // Get the currently selected unit from settings
    //     $unit = (string) get_ecommerce_setting('store_width_height_unit', 'cm');

    //     // Fetch available units from the database
    //     $availableUnits = DB::table('units')->pluck('full_name', 'symbol')->toArray();

    //     if (!array_key_exists($unit, $availableUnits)) {
    //         $unit = 'cm'; // Default to cm if the unit is not found
    //     }

    //     // Generate the dropdown HTML
    //     $dropdown = '<select name="width_height_unit" class="form-control">';
    //     foreach ($availableUnits as $key => $value) {
    //         $selected = $key === $unit ? 'selected' : '';
    //         $dropdown .= "<option value=\"{$key}\" {$selected}>{$value} ({$key})</option>";
    //     }
    //     $dropdown .= '</select>';

    //     return $full ? $dropdown : $unit; // Return dropdown or unit symbol based on the parameter
    // }
    function ecommerce_unit_dropdown(string $name, $selectedUnitId = null): string
    {
        // Fetch available units from the database (id => unit name)
        $availableUnits = DB::table('units')->pluck('symbol', 'id')->toArray();

        // Generate the dropdown HTML, where the `name` attribute is dynamic
        $dropdown = '<select name="' . $name . '" class="form-control">';
        foreach ($availableUnits as $id => $unitName) {
            $selected = $id == $selectedUnitId ? 'selected' : '';
            $dropdown .= "<option value=\"{$id}\" {$selected}>{$unitName}</option>";
        }
        $dropdown .= '</select>';

        return $dropdown;
    }
    function measurement_unit_dropdown(string $name, $selectedUnitId = null): string
    {
        // Fetch available units from the database (id => unit name)
        $availableUnits = UnitOfMeasurement::pluck('name', 'id')->toArray();

        // Generate the dropdown HTML, where the `name` attribute is dynamic
        $dropdown = '<select name="' . $name . '" class="form-control" id="unit-of-measurement">';
        foreach ($availableUnits as $id => $unitName) {
            $selected = $id == $selectedUnitId ? 'selected' : '';
            $dropdown .= "<option value=\"{$id}\" {$selected}>{$unitName}</option>";
        }
        $dropdown .= '</select>';

        return $dropdown;
    }
    function ecommerce_weight_unit_dropdown(string $name, $selectedUnitId = null): string
    {
        // Fetch available units from the database (id => unit name)
        $availableUnits = DB::table('weight_units')->pluck('symbol', 'id')->toArray();

        // Generate the dropdown HTML, where the `name` attribute is dynamic
        $dropdown = '<select name="' . $name . '" class="form-control">';
        foreach ($availableUnits as $id => $unitName) {
            $selected = $id == $selectedUnitId ? 'selected' : '';
            $dropdown .= "<option value=\"{$id}\" {$selected}>{$unitName}</option>";
        }
        $dropdown .= '</select>';

        return $dropdown;
    }

function ecommerce_width_height_unit(bool $full = false): string
{
    // Get the currently selected unit from settings
    $unit = (string) get_ecommerce_setting('store_width_height_unit', 'cm');

    // Fetch available units from the database
    $availableUnits = DB::table('units')->pluck('full_name', 'symbol')->toArray();

    if (!array_key_exists($unit, $availableUnits)) {
        $unit = 'cm'; // Default to cm if the unit is not found
    }

    // Generate the dropdown HTML
    $dropdown = '<select name="width_height_unit" class="form-control">';
    foreach ($availableUnits as $key => $value) {
        $selected = $key === $unit ? 'selected' : '';
        $dropdown .= "<option value=\"{$key}\" {$selected}>{$value} ({$key})</option>";
    }
    $dropdown .= '</select>';

    return $full ? $dropdown : $unit; // Return dropdown or unit symbol based on the parameter
}





function ecommerce_shipping_unit_dropdown(string $name, $selectedUnitId = null): string
{
    // Fetch available units from the database (id => unit name)
    $availableUnits = DB::table('units')->pluck('symbol', 'id')->toArray();

    // Generate the dropdown HTML, where the `name` attribute is dynamic
    $dropdown = '<select name="' . $name . '" class="form-control">';
    foreach ($availableUnits as $id => $unitName) {
        $selected = $id == $selectedUnitId ? 'selected' : '';
        $dropdown .= "<option value=\"{$id}\" {$selected}>{$unitName}</option>";
    }
    $dropdown .= '</select>';

    return $dropdown;
}


// function ecommerce_width_height_unit(string $settingKey, bool $full = false): string
// {
//     $availableUnits = DB::table('units')->pluck('symbol', 'id')->toArray();

//     // Generate the dropdown HTML
//     $dropdown = '<select name="' . $name . '" class="form-control">';
//     foreach ($availableUnits as $id => $name) {
//         $selected = ($selectedId == $id) ? 'selected' : '';
//         $dropdown .= "<option value=\"{$id}\" {$selected}>{$name}</option>";
//     }
//     $dropdown .= '</select>';

//     return $dropdown; // Return dropdown
// }

}

if (! function_exists('mapped_implode')) {
    function mapped_implode(string $glue, array $array, string $symbol = '='): string
    {
        return implode(
            $glue,
            array_map(
                function ($k, $v) use ($symbol) {
                    return $k . $symbol . $v;
                },
                array_keys($array),
                array_values($array)
            )
        );
    }
}
