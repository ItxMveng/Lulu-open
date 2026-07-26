<?php
declare(strict_types=1);

final class View
{
    public static function render(string $template, array $data = [], string $layout = 'main', int $status = 200): void
    {
        $templatePath = base_path('views/' . ltrim($template, '/')) . '.php';

        if (!is_file($templatePath)) {
            throw new RuntimeException(sprintf('Template introuvable: %s', $template));
        }

        http_response_code($status);
        extract($data, EXTR_SKIP);
        $pageTitle = $data['title'] ?? APP_NAME;

        ob_start();
        require $templatePath;
        $content = (string) ob_get_clean();

        if ($layout === '') {
            echo $content;
            return;
        }

        $layoutPath = base_path('views/layouts/' . $layout . '.php');
        if (!is_file($layoutPath)) {
            throw new RuntimeException(sprintf('Layout introuvable: %s', $layout));
        }

        require $layoutPath;
    }

    public static function partial(string $partial, array $data = []): void
    {
        $partialPath = base_path('views/' . ltrim($partial, '/')) . '.php';

        if (!is_file($partialPath)) {
            throw new RuntimeException(sprintf('Partiel introuvable: %s', $partial));
        }

        extract($data, EXTR_SKIP);
        require $partialPath;
    }
}