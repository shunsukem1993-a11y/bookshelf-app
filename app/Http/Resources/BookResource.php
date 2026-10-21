<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    /**
     * 書籍一覧APIのレスポンス用リソース（要約表示）。
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'author' => $this->author,
            'image_url' => $this->image_url,
            'genres' => GenreResource::collection($this->whenLoaded('genres')),
            'review_count' => $this->reviews_count,
            'average_rating' => is_null($this->reviews_avg_rating)
                ? null
                : round($this->reviews_avg_rating, 1),
        ];
    }
}
