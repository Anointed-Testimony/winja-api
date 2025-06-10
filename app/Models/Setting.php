<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key', 'value', 'type', 'description',
    ];

    // Cast value to correct type
    public function getCastedValueAttribute()
    {
        switch ($this->type) {
            case 'int':
                return (int) $this->value;
            case 'bool':
                return (bool) $this->value;
            case 'json':
                return json_decode($this->value, true);
            default:
                return $this->value;
        }
    }

    // Set value with type
    public function setValueAttribute($val)
    {
        if ($this->type === 'json') {
            $this->attributes['value'] = json_encode($val);
        } else {
            $this->attributes['value'] = $val;
        }
    }
} 