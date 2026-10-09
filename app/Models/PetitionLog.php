<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PetitionLog extends Model
{
    protected $fillable = [
        'openai_model',
        'request_payload',
        'response_payload',
    ];

    protected function casts(): array
    {
        return [
            'request_payload' => 'array',
            'response_payload' => 'array',
        ];
    }
}
