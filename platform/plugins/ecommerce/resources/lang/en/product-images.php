<?php

return [
    'name' => 'Product Images',
    'storehouse_management' => 'Storehouse Management',

    'import' => [
        'name' => 'Update Product Images',
        'description' => 'Update product Images in bulk by uploading a CSV/Excel file.',
        'done_message' => 'Updated :count product(s) successfully.',
        'rules' => [
            'id' => 'The ID field is mandatory and must be exists in products table.',
            'name' => 'The name field is mandatory and must be a string.',
            'sku' => 'The SKU field must be a string.',
            'images' => 'The with storehouse management field must be "Yes" or "No".',
            
        ],
    ],

    'export' => [
        'description' => 'Export product inventory to a CSV/Excel file.',
    ],
];
