<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'usertype',
        'is_active',
        'password',
        'avatar'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function hasRole($role)
    {
        return $this->usertype === $role;
    }

    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }

    /**
     * Check if the user has any historical operational, transactional, or audit records.
     */
    public function hasHistoricalRecords(): bool
    {
        return \Illuminate\Support\Facades\DB::table('shifts')->where('user_id', $this->id)->exists()
            || \Illuminate\Support\Facades\DB::table('sales_orders')->where('created_by', $this->id)->exists()
            || \Illuminate\Support\Facades\DB::table('sales_orders')->where('approved_by', $this->id)->exists()
            || \Illuminate\Support\Facades\DB::table('payments')->where('created_by', $this->id)->exists()
            || \Illuminate\Support\Facades\DB::table('purchase_orders')->where('created_by', $this->id)->exists()
            || \Illuminate\Support\Facades\DB::table('purchase_orders')->where('approved_by', $this->id)->exists()
            || \Illuminate\Support\Facades\DB::table('stock_adjustments')->where('user_id', $this->id)->exists()
            || \Illuminate\Support\Facades\DB::table('stock_opnames')->where('user_id', $this->id)->exists()
            || \Illuminate\Support\Facades\DB::table('product_price_logs')->where('changed_by', $this->id)->exists()
            || \Illuminate\Support\Facades\DB::table('sales_order_logs')->where('user_id', $this->id)->exists();
    }

    /**
     * Get a breakdown of historical records count for reporting.
     */
    public function getHistoricalCounts(): array
    {
        return [
            'shifts' => \Illuminate\Support\Facades\DB::table('shifts')->where('user_id', $this->id)->count(),
            'sales_orders' => \Illuminate\Support\Facades\DB::table('sales_orders')->where('created_by', $this->id)->count(),
            'payments' => \Illuminate\Support\Facades\DB::table('payments')->where('created_by', $this->id)->count(),
            'purchase_orders' => \Illuminate\Support\Facades\DB::table('purchase_orders')->where('created_by', $this->id)->count(),
            'stock_opnames' => \Illuminate\Support\Facades\DB::table('stock_opnames')->where('user_id', $this->id)->count(),
        ];
    }
}
