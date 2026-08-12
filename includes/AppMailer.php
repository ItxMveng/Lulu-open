<?php
declare(strict_types=1);

/** Emails transactionnels de la plateforme (candidatures). */
final class AppMailer
{
    /** Confirmation d'envoi de candidature au candidat. */
    public static function applicationSent(string $to, string $candidateName, string $offerTitle, string $companyName): void
    {
        $subject = 'Votre candidature a bien été envoyée';
        $body = '<p>Bonjour ' . e($candidateName) . ',</p>'
            . '<p>Votre candidature pour le poste <strong>' . e($offerTitle) . '</strong>'
            . ($companyName !== '' ? ' chez <strong>' . e($companyName) . '</strong>' : '')
            . ' a bien été transmise au recruteur.</p>'
            . '<p>Vous serez informé(e) par email à chaque évolution de votre candidature. Vous pouvez également suivre son statut depuis votre espace.</p>'
            . self::button(url('/client/candidatures'), 'Voir mes candidatures');
        self::send($to, $subject, $body);
    }

    /** Email personnalisé selon le nouveau statut. Retourne false si le statut n'implique pas d'email. */
    public static function statusChanged(string $to, string $candidateName, string $offerTitle, string $status, ?array $interview = null): bool
    {
        $map = [
            'vue' => [
                'Votre candidature a été consultée',
                '<p>Bonne nouvelle : le recruteur a consulté votre candidature pour <strong>' . e($offerTitle) . '</strong>. Nous vous tiendrons informé(e) de la suite.</p>',
            ],
            'entretien' => [
                'Vous êtes convié(e) à un entretien',
                '<p>Félicitations ! Le recruteur souhaite vous rencontrer pour le poste <strong>' . e($offerTitle) . '</strong>.</p>' . self::interviewBlock($interview),
            ],
            'acceptee' => [
                'Votre candidature a été acceptée 🎉',
                '<p>Excellente nouvelle ! Votre candidature pour <strong>' . e($offerTitle) . '</strong> a été <strong>acceptée</strong>.</p>' . self::interviewBlock($interview) . '<p>Le recruteur reviendra vers vous pour la suite.</p>',
            ],
            'rejetee' => [
                'Réponse à votre candidature',
                '<p>Nous vous remercions pour l\'intérêt porté au poste <strong>' . e($offerTitle) . '</strong>. Après étude, votre candidature n\'a pas été retenue cette fois-ci.</p><p>Nous vous souhaitons plein succès dans vos recherches et vous invitons à postuler à d\'autres offres.</p>',
            ],
        ];

        if (!isset($map[$status])) {
            return false;
        }
        [$subject, $inner] = $map[$status];
        $body = '<p>Bonjour ' . e($candidateName) . ',</p>' . $inner . self::button(url('/client/candidatures'), 'Voir ma candidature');
        self::send($to, $subject, $body);
        return true;
    }

    private static function interviewBlock(?array $interview): string
    {
        if (!$interview || empty($interview['at'])) {
            return '';
        }
        $when = date('d/m/Y à H\hi', strtotime((string) $interview['at']));
        $html = '<div style="background:#F0F7FC;border:1px solid #CDE7F6;border-radius:12px;padding:16px;margin:16px 0;">'
            . '<p style="margin:0 0 6px;font-weight:600;color:#0C4A6E;">📅 Entretien programmé</p>'
            . '<p style="margin:0;">Date : <strong>' . e($when) . '</strong></p>';
        if (!empty($interview['location'])) {
            $html .= '<p style="margin:4px 0 0;">Lieu / lien : ' . e((string) $interview['location']) . '</p>';
        }
        if (!empty($interview['note'])) {
            $html .= '<p style="margin:8px 0 0;color:#475569;">' . nl2br(e((string) $interview['note'])) . '</p>';
        }
        return $html . '</div>';
    }

    private static function button(string $href, string $label): string
    {
        return '<p style="margin:22px 0;"><a href="' . e($href) . '" style="background:#0369A1;color:#fff;text-decoration:none;padding:11px 22px;border-radius:8px;font-weight:600;display:inline-block;">' . e($label) . '</a></p>';
    }

    private static function send(string $to, string $subject, string $bodyInner): void
    {
        $html = '<div style="font-family:Segoe UI,Arial,sans-serif;max-width:560px;margin:auto;color:#1E293B;">'
            . '<div style="padding:20px 0;border-bottom:2px solid #E2E8F0;"><span style="font-weight:800;font-size:20px;color:#0C4A6E;">LULU-OPEN</span></div>'
            . '<div style="padding:24px 0;line-height:1.6;">' . $bodyInner . '</div>'
            . '<div style="padding:16px 0;border-top:1px solid #E2E8F0;color:#94A3B8;font-size:12px;">Cet email vous est envoyé par LULU-OPEN, la marketplace des talents et des entreprises.</div>'
            . '</div>';
        MailHelper::send($to, $subject, $html, strip_tags(str_replace(['</p>', '<br>'], "\n", $bodyInner)));
    }
}
