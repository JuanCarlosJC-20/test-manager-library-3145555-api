<?php

namespace App\Adapters\Inbound\Http;

use App\Application\Exceptions\InvalidCredentials;
use App\Application\Exceptions\InvalidToken;
use App\Application\Exceptions\UsernameTaken;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

final class ApiErrorRenderer
{
    public static function render(Throwable $exception): JsonResponse
    {
        if ($exception instanceof InvalidCredentials) {
            return self::error(401, 'INVALID_CREDENTIALS', 'Las credenciales no son válidas.');
        }

        if ($exception instanceof InvalidToken || $exception instanceof AuthenticationException) {
            return self::error(401, 'UNAUTHENTICATED', 'Se requiere una autenticación válida.');
        }

        if ($exception instanceof AuthorizationException) {
            return self::error(403, 'FORBIDDEN_ROLE', 'No tienes permiso para realizar esta operación.');
        }

        if ($exception instanceof UsernameTaken) {
            return self::error(409, 'USERNAME_TAKEN', 'Ese nombre de usuario ya está registrado.');
        }

        if ($exception instanceof ValidationException) {
            $details = [];

            foreach ($exception->errors() as $field => $messages) {
                foreach ($messages as $_message) {
                    $details[] = ['field' => $field, 'issue' => 'valor inválido'];
                }
            }

            return self::error(422, 'VALIDATION_ERROR', 'Los datos enviados no son válidos.', $details);
        }

        if ($exception instanceof ModelNotFoundException || $exception instanceof NotFoundHttpException) {
            return self::error(404, 'NOT_FOUND', 'No se encontró el recurso solicitado.');
        }

        if ($exception instanceof HttpExceptionInterface) {
            $status = $exception->getStatusCode();
            $mapping = match ($status) {
                401 => ['UNAUTHENTICATED', 'Se requiere una autenticación válida.'],
                403 => ['FORBIDDEN_ROLE', 'No tienes permiso para realizar esta operación.'],
                404 => ['NOT_FOUND', 'No se encontró el recurso solicitado.'],
                409 => ['CONFLICT', 'La operación entra en conflicto con el estado actual.'],
                422 => ['VALIDATION_ERROR', 'Los datos enviados no son válidos.'],
                default => ['HTTP_ERROR', 'No fue posible procesar la solicitud.'],
            };

            return self::error($status, $mapping[0], $mapping[1]);
        }

        return self::error(500, 'INTERNAL_SERVER_ERROR', 'Ocurrió un error interno.');
    }

    private static function error(int $status, string $code, string $message, array $details = []): JsonResponse
    {
        return new JsonResponse([
            'code' => $code,
            'message' => $message,
            'details' => $details,
        ], $status);
    }
}
