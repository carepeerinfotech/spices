<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['group', 'key', 'value', 'type', 'is_encrypted'])]
class Setting extends Model
{
    protected function casts(): array
    {
        return [
            'is_encrypted' => 'boolean',
        ];
    }
}
