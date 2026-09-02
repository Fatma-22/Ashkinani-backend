<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlayerPhotoResource extends JsonResource
{
    /**
     * The "data" wrapper that should be applied.
     *
     * @var string|null
     */
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url ? (filter_var($this->url, FILTER_VALIDATE_URL) ? $this->url : asset('storage/' . $this->url)) : null,
            'caption' => $this->caption,
            'is_main' => $this->is_main,
        ];
    }
}
