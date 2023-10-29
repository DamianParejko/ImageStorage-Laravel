<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Image extends Model
{
    use HasFactory;

    protected $fillable = ['path', 'extension', 'size', 'width', 'height', 'sender_id'];

    public function info()
    {
        return $this->belongsTo(Info::class);
    }

    public function sender()
    {
        return $this->belongsTo(Sender::class);
    }
}
