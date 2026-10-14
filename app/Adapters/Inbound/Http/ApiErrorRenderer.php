<?php

namespace App\Adapters\Inbound\Http;

use App\Application\Exceptions\BookHasOpenLoans;
use App\Application\Exceptions\BookNotFound;
use App\Application\Exceptions\CoverNotFound;
use App\Application\Exceptions\InvalidCredentials;
use App\Application\Exceptions\InvalidToken;
use App\Application\Exceptions\IsbnTaken;
use App\Application\Exceptions\DocumentTaken;
use App\Application\Exceptions\MemberNotFound;
use App\Application\Exceptions\LoanNotFound;
use App\Application\Exceptions\InvalidDateRange;
use App\Application\Exceptions\InvalidCategory;
use App\Application\Exceptions\MemberHasOverdue;
use App\Application\Exceptions\MemberLimitExceeded;
use App\Application\Exceptions\UsernameTaken;
use App\Domain\Books\CopyLimitExceeded;
use App\Domain\Books\BookNotAvailable;
use App\Domain\Loans\DuplicateBookInLoan;
use App\Domain\Loans\ItemAlreadyReturned;
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

        if ($exception instanceof IsbnTaken) {
            return self::error(409, 'ISBN_TAKEN', 'El ISBN ya está asociado a un libro activo.');
        }

        if ($exception instanceof InvalidCategory) {
            return self::error(422, 'VALIDATION_ERROR', 'Los datos enviados no son válidos.', [
                ['field' => 'category_id', 'issue' => 'categoría inexistente'],
            ]);
        }

        if ($exception instanceof DocumentTaken) {
            return self::error(409, 'DOCUMENT_TAKEN', 'El documento ya está registrado.');
        }

        if ($exception instanceof MemberNotFound) {
            return self::error(404, 'NOT_FOUND', 'No se encontró el miembro solicitado.');
        }

        if ($exception instanceof LoanNotFound) {
            return self::error(404, 'NOT_FOUND', 'No se encontró el préstamo solicitado.');
        }

        if ($exception instanceof InvalidDateRange) {
            return self::error(422, 'INVALID_DATE_RANGE', 'La fecha final debe ser igual o posterior a la fecha inicial.', [
                ['field' => 'to', 'issue' => 'debe ser igual o posterior a from'],
            ]);
        }

        if ($exception instanceof BookNotAvailable) {
            return self::error(409, 'BOOK_NOT_AVAILABLE', 'No hay ejemplares disponibles de uno de los libros solicitados.');
        }

        if ($exception instanceof MemberHasOverdue) {
            return self::error(409, 'MEMBER_HAS_OVERDUE', 'El miembro tiene préstamos vencidos pendientes de devolución.');
        }

        if ($exception instanceof ItemAlreadyReturned) {
            return self::error(409, 'ITEM_ALREADY_RETURNED', 'El ejemplar ya fue devuelto.');
        }

        if ($exception instanceof DuplicateBookInLoan) {
            return self::error(422, 'DUPLICATE_BOOK_IN_LOAN', 'No se puede repetir un libro dentro del mismo préstamo.', [
                ['field' => 'items', 'issue' => 'no se permiten libros duplicados'],
            ]);
        }

        if ($exception instanceof MemberLimitExceeded) {
            return self::error(422, 'MEMBER_LIMIT_EXCEEDED', 'El miembro superaría el límite de libros sin devolver.', [
                ['field' => 'items', 'issue' => 'libros sin devolver actualmente: '.$exception->currentCount],
            ]);
        }

        if ($exception instanceof BookHasOpenLoans) {
            return self::error(409, 'BOOK_HAS_OPEN_LOANS', 'El libro tiene préstamos sin devolver.');
        }

        if ($exception instanceof BookNotFound || $exception instanceof CoverNotFound) {
            return self::error(404, 'NOT_FOUND', 'No se encontró el libro solicitado.');
        }

        if ($exception instanceof CopyLimitExceeded) {
            return self::error(422, 'VALIDATION_ERROR', 'Los datos enviados no son válidos.', [
                ['field' => 'total_copies', 'issue' => 'no puede ser menor que los ejemplares prestados'],
            ]);
        }

        if ($exception instanceof ValidationException) {
            $details = [];

            foreach ($exception->errors() as $field => $messages) {
                $field = preg_replace('/\Aitems\.(\d+)\./', 'items[$1].', $field) ?? $field;
                foreach ($messages as $_message) {
                    $details[] = ['field' => $field, 'issue' => 'valor inválido'];
                }
            }

            return self::error(422, 'VALIDATION_ERROR', 'Los datos enviados no son válidos.', $details);
        }

        if ($exception instanceof \InvalidArgumentException) {
            return self::error(422, 'VALIDATION_ERROR', 'Los datos enviados no son válidos.');
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
