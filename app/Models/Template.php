<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Template extends Model
{
    use HasFactory;
    use SoftDeletes;
    
    public $fillable = [
        'name',
        'user_id',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function accounts() {
        return $this->belongsToMany(Account::class)->withPivot(['amount']);
    }
}
