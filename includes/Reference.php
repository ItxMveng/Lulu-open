<?php
declare(strict_types=1);

/** Données de référence pour les listes déroulantes (langues, compétences). */
final class Reference
{
    public static function languages(): array
    {
        return ['Français', 'Anglais', 'Espagnol', 'Allemand', 'Italien', 'Portugais', 'Arabe', 'Néerlandais', 'Chinois', 'Russe'];
    }

    /** Compétences transversales, tous secteurs (le catalogue reste ouvert via un champ libre). */
    public static function commonSkills(): array
    {
        return [
            // Transversal
            'Communication', 'Travail en équipe', 'Gestion de projet', 'Organisation', 'Relation client',
            'Autonomie', 'Vente', 'Négociation', 'Management', 'Résolution de problèmes',
            // Commerce / Marketing
            'Marketing digital', 'Community management', 'SEO', 'Publicité', 'Merchandising',
            // Bureautique / Admin / Gestion
            'Bureautique (Word, Excel)', 'Comptabilité', 'Gestion administrative', 'Facturation', 'Ressources humaines',
            // Métiers / Artisanat / BTP
            'Cuisine', 'Service en salle', 'Pâtisserie', 'Menuiserie', 'Électricité', 'Plomberie', 'Maçonnerie', 'Peinture en bâtiment', 'Mécanique',
            // Santé / Social / Éducation
            'Aide à la personne', 'Soins infirmiers', 'Petite enfance', 'Enseignement', 'Formation',
            // Créatif / Design
            'Design graphique', 'Photographie', 'Montage vidéo', 'Rédaction', 'Traduction',
            // Beauté / Bien-être
            'Coiffure', 'Esthétique', 'Massage',
            // Logistique
            'Conduite / Livraison', 'Manutention', 'Gestion de stock',
            // Tech
            'Développement web', 'Bureautique avancée', 'Analyse de données', 'Support informatique',
        ];
    }

    public static function contractTypes(): array
    {
        return ['CDI', 'CDD', 'Freelance', 'Alternance', 'Stage', 'Intérim'];
    }

    /** Pays (marché africain prioritaire + international). */
    public static function countries(): array
    {
        return [
            'Bénin', 'Burkina Faso', 'Cameroun', 'Côte d\'Ivoire', 'Gabon', 'Ghana', 'Guinée', 'Kenya', 'Mali',
            'Maroc', 'Niger', 'Nigéria', 'République démocratique du Congo', 'Congo-Brazzaville', 'Sénégal',
            'Tchad', 'Togo', 'Tunisie', 'Afrique du Sud', 'Algérie', 'Rwanda', 'Tanzanie',
            'France', 'Belgique', 'Canada', 'Suisse', 'Autre',
        ];
    }
}
