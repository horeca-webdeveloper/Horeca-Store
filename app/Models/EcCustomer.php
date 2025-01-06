<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class EcCustomer extends Authenticatable
{
    use HasFactory, HasApiTokens;


    protected $table = 'ec_customers'; // Explicitly define the table name if necessary

    protected $fillable = [
        'name', 'email', 'password', 'avatar', 'dob', 'phone', 'is_vendor', 'status', 'email_verify_token', 'vendor_verified_at', 'confirmed_at', 'private_notes'
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];
}



