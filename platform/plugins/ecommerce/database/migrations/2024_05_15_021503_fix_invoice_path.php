<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\File;

return new class () extends Migration {
    public function up(): void
    {
        $file = storage_path('app/templates/invoice.tpl');

        if (File::exists($file)) {
            File::ensureDirectoryExists(storage_path('app/templates/ecommerce'));

            File::move($file, storage_path('app/templates/ecommerce/invoice.tpl'));
        }
    }
};
