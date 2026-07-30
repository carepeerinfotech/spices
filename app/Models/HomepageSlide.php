<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use App\Support\MediaUrl;

#[Fillable([
    'title', 'link_url', 'image', 'mobile_image', 'sort_order', 'is_active',
])]
class HomepageSlide extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function imageUrl(): ?string
    {
        return MediaUrl::public($this->image);
    }

    public function mobileImageUrl(): ?string
    {
        return MediaUrl::public($this->mobile_image) ?: $this->imageUrl();
    }
}
