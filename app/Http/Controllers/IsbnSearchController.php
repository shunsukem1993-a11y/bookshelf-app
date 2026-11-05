<?php

namespace App\Http\Controllers;

use App\Services\GoogleBooksClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;

class IsbnSearchController extends Controller
{
    public function __construct(private readonly GoogleBooksClient $client) {}

    /**
     * ISBNから書籍情報を検索する（認証必須）。
     *
     * 13桁のISBNでGoogle Books APIを検索し、書籍登録・編集フォーム自動入力用の
     * 書籍情報をJSONで返す。
     */
    public function show(string $isbn): JsonResponse
    {
        if (! preg_match('/^\d{13}$/', $isbn)) {
            return response()->json(['error' => 'ISBNは13桁の数字で指定してください。'], 422);
        }

        try {
            $book = $this->client->findByIsbn($isbn);
        } catch (RequestException|ConnectionException $e) {
            return response()->json(['error' => '書籍情報の取得に失敗しました。時間をおいて再度お試しください。'], 502);
        }

        if ($book === null) {
            return response()->json(['error' => '該当する書籍情報が見つかりませんでした。'], 404);
        }

        return response()->json($book);
    }
}
