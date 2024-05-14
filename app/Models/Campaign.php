<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campaign extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'unique_code',
        'campaign_name',
        'description',
        'banner_image',
        'images'
    ];

    public function account_plan()
    {
        return $this->belongsTo(AccountPlan::class);
    }
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function donation()
    {
        return $this->hasMany(Donation::class);
    }
}
