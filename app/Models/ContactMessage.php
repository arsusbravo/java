<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    public const STATUSES = ['new' => 'New', 'replied' => 'Replied', 'spam' => 'Spam'];

    protected $fillable = ['name', 'email', 'subject', 'message', 'status'];
}
