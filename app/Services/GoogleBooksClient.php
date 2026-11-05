<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class GoogleBooksClient
{
    private const BASE_URL = 'https://www.googleapis.com/books/v1/volumes';

    /**
     * ISBNでGoogle Books APIを検索し、書籍情報を取得する。
     *
     * @return array<string, mixed>|null 該当書籍が見つからない場合はnull
     *
     * @throws RequestException 外部APIがエラーレスポンスを返した場合
     * @throws ConnectionException 通信自体が失敗した場合
     */
    public function findByIsbn(string $isbn): ?array
    {
        $response = Http::timeout(5)->get(self::BASE_URL, [
            'q' => "isbn:{$isbn}",
        ]);

        $response->throw();

        $items = $response->json('items', []);

        if (empty($items)) {
            return null;
        }

        $volumeInfo = $items[0]['volumeInfo'] ?? [];

        return [
            'title' => $volumeInfo['title'] ?? null,
            'author' => isset($volumeInfo['authors']) ? implode('、', $volumeInfo['authors']) : null,
            'description' => $volumeInfo['description'] ?? null,
            'image_url' => $volumeInfo['imageLinks']['thumbnail'] ?? null,
            'published_date' => $volumeInfo['publishedDate'] ?? null,
        ];
    }
}
