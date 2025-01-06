<?php
namespace Botble\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Botble\Base\Models\BaseModel;

class Country extends BaseModel
{
    use HasFactory;

    protected $table = 'country';

    protected $fillable = [
        'iso',
        'name',
        'nicename',
        'iso3',
        'numcode',
        'phonecode',
    ];
}
