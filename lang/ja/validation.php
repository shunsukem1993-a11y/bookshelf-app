<?php

/**
 * Laravel標準バリデーションルールの日本語メッセージ（共通・汎用メッセージ）。
 *
 * 項目（フィールド）固有の文言は、各FormRequestのmessages()で個別に管理する。
 * ここで定義するのは、各FormRequestが個別にメッセージを定義していない場合に
 * 適用される汎用メッセージであり、カスタムメッセージが存在する項目では
 * 実際には使用されない（英語メッセージへのフォールバックを防ぐための保険）。
 */
return [

    'required' => ':attributeは必須です。',
    'string' => ':attributeは文字列で入力してください。',
    'array' => ':attributeは配列形式で指定してください。',
    'integer' => ':attributeは整数で指定してください。',
    'digits' => ':attributeは:digits桁で入力してください。',
    'url' => ':attributeはURL形式で入力してください。',
    'email' => ':attributeはメール形式で入力してください。',
    'date_format' => ':attributeはYYYY-MM-DD形式で入力してください。',
    'confirmed' => ':attributeが確認用の入力と一致しません。',
    'exists' => '指定された:attributeは存在しません。',
    'unique' => 'その:attributeはすでに登録されています。',
    'between' => [
        'numeric' => ':attributeは:min〜:maxの範囲で指定してください。',
        'string' => ':attributeは:min文字〜:max文字の範囲で入力してください。',
    ],
    'min' => [
        'numeric' => ':attributeは:min以上の値を指定してください。',
        'string' => ':attributeは:min文字以上で入力してください。',
        'array' => ':attributeは:min個以上選択してください。',
    ],
    'max' => [
        'numeric' => ':attributeは:max以下の値を指定してください。',
        'string' => ':attributeは:max文字以内で入力してください。',
        'array' => ':attributeは:max個以内で選択してください。',
    ],

    /**
     * フィールド名（項目名）の日本語表記。
     *
     * 複数の画面で同じキーが異なる意味を持つ項目（例: 'name'はUser登録では
     * 「名前」、Genreでは「ジャンル名」を指す）は、意図しない誤表示を避けるため
     * ここには含めず、各FormRequest/Actionのmessages()で個別に定義する。
     */
    'attributes' => [
        'title' => 'タイトル',
        'author' => '著者名',
        'isbn' => 'ISBN',
        'published_date' => '出版日',
        'description' => '説明',
        'image_url' => '画像URL',
        'genres' => 'ジャンル',
        'genre_id' => 'ジャンルID',
        'rating' => '評価',
        'comment' => 'コメント',
        'book_id' => '書籍',
        'target_date' => '期日',
        'email' => 'メールアドレス',
        'password' => 'パスワード',
        'keyword' => 'キーワード',
        'page' => 'ページ番号',
        'per_page' => '1ページあたりの件数',
    ],

];
