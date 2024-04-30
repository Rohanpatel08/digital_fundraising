<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Donation extends Model
{
    use HasFactory;

    protected $table = 'donations';

    protected $fillable = [
        'donner_name',
        'donner_email',
        'amount'
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}