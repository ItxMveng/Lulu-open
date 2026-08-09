<?php
declare(strict_types=1);

/** Données de référence pour les listes déroulantes (langues, compétences). */
final class Reference
{
    public static function languages(): array
    {
        return ['Français', 'Anglais', 'Espagnol', 'Allemand', 'Italien', 'Portugais', 'Arabe', 'Néerlandais', 'Chinois', 'Russe'];
    }

    public static function commonSkills(): array
    {
        return [
            'PHP', 'JavaScript', 'TypeScript', 'Python', 'Java', 'Dart', 'Flutter', 'React', 'Vue.js', 'Node.js',
            'Laravel', 'Symfony', 'SQL', 'MySQL', 'PostgreSQL', 'MongoDB', 'Docker', 'Git', 'AWS', 'DevOps',
            'UI/UX', 'Figma', 'Photoshop', 'Illustrator', 'SEO', 'Google Ads', 'Marketing digital', 'Community management',
            'Rédaction', 'Traduction', 'Comptabilité', 'Gestion de projet', 'Data analyse', 'Machine learning', 'Cybersécurité', 'Support client',
        ];
    }

    public static function contractTypes(): array
    {
        return ['CDI', 'CDD', 'Freelance', 'Alternance', 'Stage', 'Intérim'];
    }
}
