<?php

namespace App\Models;

use Database\Factories\AppointmentFormStepFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppointmentFormStep extends Model
{
    /** @use HasFactory<AppointmentFormStepFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'description',
        'icon',
        'position',
    ];

    /**
     * @return HasMany<AppointmentFormQuestion, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(AppointmentFormQuestion::class)->orderBy('position');
    }
}
