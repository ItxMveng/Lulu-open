<?php
declare(strict_types=1);

abstract class Controller
{
    protected function render(string $template, array $data = [], string $layout = 'main'): void
    {
        View::render($template, $data, $layout);
    }

    protected function redirect(string $path): never
    {
        redirect($path);
    }

    protected function json(array $payload, int $status = 200): never
    {
        json_response($payload, $status);
    }
}