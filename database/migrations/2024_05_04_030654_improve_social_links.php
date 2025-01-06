<?php

use Botble\Setting\Facades\Setting;
use Botble\Theme\Facades\ThemeOption;
use Illuminate\Database\Migrations\Migration;

return new class() extends Migration
{
    public function up(): void
    {
        $socialLinks = [];
        for ($i = 1; $i <= 10; $i++) {
            if (
                theme_option('social-name-' . $i)
                && theme_option('social-url-' . $i)
                && theme_option('social-icon-' . $i)
            ) {
                $socialLinks[] = [
                    ['key' => 'name', 'value' => theme_option('social-name-' . $i)],
                    ['key' => 'icon', 'value' => 'fa ' . theme_option('social-icon-' . $i)],
                    ['key' => 'url', 'value' => theme_option('social-url-' . $i)],
                    ['key' => 'icon_image', 'value' => null],
                    ['key' => 'color', 'value' => theme_option('social-color-' . $i)],
                    ['key' => 'background-color', 'value' => null],
                ];
            }
        }

        Setting::delete('social_links');

        Setting::set(ThemeOption::prepareFromArray(['social_links' => $socialLinks]));

        Setting::save();
    }
};
