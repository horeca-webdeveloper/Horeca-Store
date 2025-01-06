<?php

use Botble\Page\Models\Page;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach (Page::get() as $page) {
            $page->content = str_replace('[site-features][/site-features]', '[site-features icon1="' . theme_option('feature_1_icon') . '" title1="' . theme_option('feature_1_title') . '" subtitle1="' . theme_option('feature_1_subtitle') . '" icon2="' . theme_option('feature_2_icon') . '" title2="' . theme_option('feature_2_title') . '" subtitle2="' . theme_option('feature_2_subtitle') . '" icon3="' . theme_option('feature_3_icon') . '" title3="' . theme_option('feature_3_title') . '" subtitle3="' . theme_option('feature_3_subtitle') . '" icon4="' . theme_option('feature_4_icon') . '" title4="' . theme_option('feature_4_title') . '" subtitle4="' . theme_option('feature_4_subtitle') . '" icon5="' . theme_option('feature_5_icon') . '" title5="' . theme_option('feature_5_title') . '" subtitle5="' . theme_option('feature_5_subtitle') . '"][/site-features]', $page->content);
            $page->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
