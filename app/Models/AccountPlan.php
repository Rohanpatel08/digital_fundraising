<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountPlan extends Model
{
    use HasFactory, SoftDeletes;

    public $table = 'account_plans';
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
