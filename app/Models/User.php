<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Enum\SearchModelParams;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable,SoftDeletes;
    public static $searchable = SearchModelParams::User;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'is_active',
        'deleted_at',
        'position_id',
        'warehouse_id',
        'company_godown_id',
        'franchise_id',
        'retailer_id',
        'api_token',
        'referral_code',
        'first_name',
        'middle_name',
        'last_name',
        'user_name',
        'phone',
        'email',
        'password',
        'soft_password',
        'gender',
        'dob',
        'address',
        'city_id',
        'state_id',
        'pincode',
        'image_path',
        'otp',
        'wallet',
        'referred_user_id',
        'referred_user_code',
        'fcm_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = ['name'];

    /**
     * Get user's full name
     */
    public function getNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class)->withTimestamps();
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    public function assignRole($role)
    {
        return $this->roles()->sync([$role]);
    }

    public function assignCompany($company)
    {
        return $this->companies()->sync([$company]);
    }

    public function generateReferralCode($length = 8, $maxAttempts = 10)
    {
        $attempts = 0;

        do {
            // Generate a random code
            $referralCode = Str::upper(Str::random($length));

            // Check if it exists in the database
            $exists = DB::table('users')->where('referral_code', $referralCode)->exists();

            $attempts++;

            // If the code already exists, retry up to the maxAttempts limit
        } while ($exists && $attempts < $maxAttempts);

        // If we exceed max attempts, return a fallback unique ID-based code
        if ($exists) {
            $referralCode = 'REF' . time() . rand(100, 999);
        }

        return $referralCode;
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function referred_user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function companyGodown(): BelongsTo
    {
        return $this->belongsTo(CompanyGodown::class);
    }

    public function franchise(): BelongsTo
    {
        return $this->belongsTo(Franchise::class);
    }

    public function retailer(): BelongsTo
    {
        return $this->belongsTo(Retailer::class);
    }
}
