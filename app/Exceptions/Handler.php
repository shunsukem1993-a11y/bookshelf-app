<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // 公開APIでモデルが見つからない場合は、対象に応じた日本語メッセージで404を返す
        //
        // ModelNotFoundExceptionはHandler::prepareException()でrenderable()呼び出し前に
        // NotFoundHttpExceptionへ変換されるため、こちらで変換後の例外を捕捉する。
        $this->renderable(function (NotFoundHttpException $e, Request $request): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            $previous = $e->getPrevious();

            if (! $previous instanceof ModelNotFoundException) {
                return null;
            }

            $message = match (class_basename($previous->getModel())) {
                'Book' => '書籍情報が見つかりませんでした。',
                'Genre' => '指定されたジャンルが見つかりませんでした。',
                default => '指定された情報が見つかりませんでした。',
            };

            return response()->json(['error' => $message], 404);
        });

        // ★ 公開APIでPolicyによる認可エラー（他ユーザーの書籍操作等）が発生した場合は、
        // 日本語メッセージで403を返す。
        //
        // AuthorizationExceptionはHandler::prepareException()でrenderable()呼び出し前に
        // AccessDeniedHttpExceptionへ変換されるため、こちらで変換後の例外を捕捉する。
        $this->renderable(function (AccessDeniedHttpException $e, Request $request): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            if (! $e->getPrevious() instanceof AuthorizationException) {
                return null;
            }

            return response()->json(['error' => 'この操作を実行する権限がありません。'], 403);
        });
    }
}
