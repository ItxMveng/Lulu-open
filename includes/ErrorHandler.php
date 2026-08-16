<?php
declare(strict_types=1);

final class ErrorHandler
{
    public static function register(): void
    {
        set_exception_handler([self::class, 'handleException']);
        set_error_handler([self::class, 'handleError']);
    }

    public static function handleException(Throwable $throwable): void
    {
        self::log($throwable);

        if (APP_ENV === 'production' && !APP_DEBUG) {
            self::renderHttpError(500, 'Une erreur interne est survenue.');
            return;
        }

        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');

        echo '<h1>Erreur applicative</h1>';
        echo '<pre>' . e($throwable::class . ': ' . $throwable->getMessage()) . '</pre>';
        echo '<pre>' . e($throwable->getTraceAsString()) . '</pre>';
    }

    public static function handleError(int $severity, string $message, string $file, int $line): bool
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        throw new ErrorException($message, 0, $severity, $file, $line);
    }

    public static function renderHttpError(int $status, string $message = ''): never
    {
        $layout = request_path() !== '/' && str_starts_with(request_path(), '/admin') ? 'admin' : 'main';
        $view = $status === 404 ? 'pages/404' : 'pages/error';
        $title = $status === 404 ? 'Page introuvable' : 'Erreur';

        if (class_exists('View') && is_file(base_path('views/' . $view . '.php'))) {
            View::render($view, ['title' => $title, 'message' => $message], $layout, $status);
            exit;
        }

        echo e($message !== '' ? $message : 'Une erreur est survenue.');
        exit;
    }

    private static function log(Throwable $throwable): void
    {
        $line = sprintf(
            "[%s] %s in %s:%d\n%s\n\n",
            date('Y-m-d H:i:s'),
            $throwable->getMessage(),
            $throwable->getFile(),
            $throwable->getLine(),
            $throwable->getTraceAsString()
        );

        file_put_contents(LOG_PATH . DIRECTORY_SEPARATOR . 'errors.log', $line, FILE_APPEND);
    }
}