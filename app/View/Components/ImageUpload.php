<?php

namespace App\View\Components;

use App\Services\Media\ImageService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\Component;

/**
 * Upload field for one image collection on a HasImages model.
 *
 * Everything except the owner and collection is read from config/media.php, so
 * <x-image-upload :owner="$model" collection="banner" /> is the whole API. The
 * field lets an admin choose files (click or drag), preview them full size,
 * download a saved image and remove one.
 */
class ImageUpload extends Component
{
    /** @var array<string, mixed> */
    public array $config;

    public bool $multiple;

    public string $inputName;

    public string $inputId;

    /** @var Collection<int, \App\Models\Image> */
    public Collection $images;

    public string $heading;

    public int $maxKb;

    public string $accept;

    public function __construct(
        public Model $owner,
        public string $collection = 'default',
        ?string $label = null,
        public ?string $help = null,
    ) {
        $this->config = app(ImageService::class)->config($owner, $collection);

        $this->multiple = (bool) ($this->config['multiple'] ?? false);
        $this->inputName = $collection.($this->multiple ? '_files[]' : '_file');
        // Unique so several fields can sit in one form and still pair label to input.
        $this->inputId = 'image-upload-'.Str::slug($collection).'-'.Str::random(6);
        $this->images = $owner->exists ? $owner->imagesIn($collection) : collect();
        $this->heading = $label ?? ($this->config['label'] ?? Str::headline($collection));
        $this->maxKb = (int) ($this->config['max_kb'] ?? 4096);
        $this->accept = $this->config['accept'] ?? 'image/*';
    }

    public function maxLabel(): string
    {
        return $this->maxKb >= 1024
            ? round($this->maxKb / 1024).' MB'
            : $this->maxKb.' KB';
    }

    public function hint(): string
    {
        return $this->help ?? ($this->multiple
            ? 'Add one or more images.'
            : 'Uploading replaces the current image.');
    }

    public function render(): View
    {
        return view('components.image-upload');
    }
}
