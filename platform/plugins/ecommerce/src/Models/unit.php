<?php

namespace Botble\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends BaseModel
{
    use HasFactory;

    // Specify the table name if it's not the plural form of the model name
    protected $table = 'units'; // optional if the table is named 'units'

    // Specify the fillable fields
    protected $fillable = [
        'symbol',
    ];
}
