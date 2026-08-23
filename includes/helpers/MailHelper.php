<?php
declare(strict_types=1);

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mime\Email;

final class MailHelper
{
    public static function send(string $to, string $subject, string $html, string $text = ''): bool
    {
        if (APP_ENV !== 'production') {
            self::storePreview($to, $subject, $html, $text);
            return true;
        }

        if (!class_exists(Mailer::class) || !class_exists(Email::class)) {
            self::storePreview($to, $subject, $html, $text);
            return false;
        }

        try {
            // TLS : sur le port 465 => TLS implicite (true) ; sur 587 => STARTTLS,
            // qu'on laisse Symfony négocier en passant null (forcer true casserait
            // la connexion Brevo/Gmail en 587). 'ssl' force le TLS implicite.
            $port = (int) env('MAIL_PORT', 587);
            $encryption = strtolower((string) env('MAIL_ENCRYPTION', ''));
            $implicitTls = ($encryption === 'ssl' || $port === 465) ? true : null;

            $transport = new EsmtpTransport(
                (string) env('MAIL_HOST', '127.0.0.1'),
                $port,
                $implicitTls
            );

            $username = (string) env('MAIL_USER', '');
            $password = (string) env('MAIL_PASS', '');

            if ($username !== '') {
                $transport->setUsername($username);
            }

            if ($password !== '') {
                $transport->setPassword($password);
            }

            $mailer = new Mailer($transport);

            $email = (new Email())
                ->from(sprintf('%s <%s>', env('MAIL_FROM_NAME', APP_NAME), env('MAIL_FROM_ADDRESS', 'no-reply@localhost')))
                ->to($to)
                ->subject($subject)
                ->html($html);

            if ($text !== '') {
                $email->text($text);
            }

            $mailer->send($email);
            return true;
        } catch (Throwable $throwable) {
            file_put_contents(
                LOG_PATH . DIRECTORY_SEPARATOR . 'mail_errors.log',
                sprintf("[%s] %s\n", date('Y-m-d H:i:s'), $throwable->getMessage()),
                FILE_APPEND
            );

            self::storePreview($to, $subject, $html, $text);
            return false;
        }
    }

    private static function storePreview(string $to, string $subject, string $html, string $text): void
    {
        $previewDirectory = LOG_PATH . DIRECTORY_SEPARATOR . 'mail_previews';
        if (!is_dir($previewDirectory)) {
            mkdir($previewDirectory, 0775, true);
        }

        $fileName = sprintf('%s-%s.html', date('Ymd-His'), bin2hex(random_bytes(4)));
        $content = sprintf(
            '<h1>%s</h1><p><strong>Destinataire :</strong> %s</p><div>%s</div><hr><pre>%s</pre>',
            e($subject),
            e($to),
            $html,
            e($text)
        );

        file_put_contents($previewDirectory . DIRECTORY_SEPARATOR . $fileName, $content);
    }
}