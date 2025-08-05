<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_intern',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function tourGuide()
    {
        return $this->hasOne(TourGuide::class);
    }

    public function hrAssistant()
    {
        return $this->hasOne(HrAssistants::class);
    }

    public function teamLead()
    {
        return $this->hasOne(TeamLeads::class);
    }

    public function operation()
    {
        return $this->hasOne(Operations::class);
    }

    public function supervisor()
    {
        return $this->hasOne(Supervisors::class);
    }

    public function amSupervisor()
    {
        return $this->hasOne(AmSupervisors::class);
    }

    public function accesses()
    {
        return $this->hasMany(UserAccess::class);
    }

    public function hasAccess($accessType)
    {
        // Convert access type to snake_case for legacy compatibility
        $snakeCaseType = Str::snake($accessType);
        
        return $this->accesses()
            ->where(function($query) use ($accessType, $snakeCaseType) {
                $query->where('access_type', $accessType)
                    ->orWhere('access_type', $snakeCaseType);
            })
            ->where('has_access', 1)
            ->exists();
    }

    public function hasAnyAccountingAccess()
    {
        // Get all active income/expense types
        $types = AccountingIncomeExpenseType::where('active', true)
            ->pluck('name')
            ->map(function($name) {
                return Str::snake($name);
            })
            ->toArray();

        // If no types are defined yet, use legacy hardcoded types
        if (empty($types)) {
            $types = [
                'apartment_rent',
                'apartment_deposit',
                'salary',
                'bonus',
                'reimbursements',
                'car_mileage',
                'others'
            ];
        }

        return $this->accesses()
            ->where('has_access', true)
            ->whereIn('access_type', $types)
            ->exists();
    }

    public function supervisorRecord()
    {
        return $this->hasOne(Supervisors::class, 'user_id');
    }

    public function staff()
    {
        return $this->hasOne(StaffUser::class, 'user_id');
    }
}

