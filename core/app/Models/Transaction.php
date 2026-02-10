<?php

namespace App\Models;

use App\Traits\ExportData;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use ExportData;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
