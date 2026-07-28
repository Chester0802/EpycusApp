<?php

declare(strict_types=1);

namespace App\Shared\Exceptions;

use App\Shared\Domain\Exceptions\ConflictException;
use App\Shared\Domain\Exceptions\DomainException;
use App\Shared\Domain\Exceptions\ForbiddenException;
use App\Shared\Domain\Exceptions\NotFoundException;
use App\Shared\Domain\Exceptions\ValidationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

final class Handler extends ExceptionHandler
{
    protected function renderHttpException(HttpExceptionInterface $e): Response
    {
        if (request()->wantsJson()) {
            return new JsonResponse([
                'error' => $e->getMessage(),
                'code' => $e->getStatusCode(),
            ], $e->getStatusCode());
        }

        if (request()->inertia()) {
            return inertia()->render('Error', [
                'status' => $e->getStatusCode(),
                'message' => $e->getMessage(),
            ])->toResponse(request())->setStatusCode($e->getStatusCode());
        }

        return parent::renderHttpException($e);
    }

    public function render($request, Throwable $e): Response|JsonResponse|RedirectResponse
    {
        if ($e instanceof DomainException) {
            $httpCode = $this->domainExceptionToHttpCode($e);

            $this->logDomainException($e);

            if ($request->wantsJson() || $request->expectsJson()) {
                return new JsonResponse([
                    'message' => $e->getMessage(),
                    'code' => $e->errorCode(),
                ], $httpCode);
            }

            if ($request->inertia()) {
                return redirect()->back()->withErrors([
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return parent::render($request, $e);
    }

    private function domainExceptionToHttpCode(DomainException $e): int
    {
        return match (true) {
            $e instanceof NotFoundException => 404,
            $e instanceof ValidationException => 422,
            $e instanceof ForbiddenException => 403,
            $e instanceof ConflictException => 409,
            default => 500,
        };
    }

    private function logDomainException(DomainException $e): void
    {
        $context = $e->context();
        unset($context['user_id']);

        logger()->warning($e->getMessage(), [
            'code' => $e->errorCode(),
            'context' => $context,
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);
    }
}
