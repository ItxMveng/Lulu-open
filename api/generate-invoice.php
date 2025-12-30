<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/middleware-admin.php';
require_admin();

$paiementId = $_GET['id'] ?? null;

if (!$paiementId) {
    die('ID paiement manquant');
}

$db = Database::getInstance()->getConnection();

// Récupérer les détails du paiement
$stmt = $db->prepare("
    SELECT p.*, 
           CONCAT(u.prenom, ' ', u.nom) as nom_utilisateur,
           u.email, u.telephone,
           pl.nom as plan_nom,
           a.date_debut as abo_date_debut,
           a.date_fin as abo_date_fin
    FROM paiements p
    JOIN utilisateurs u ON p.utilisateur_id = u.id
    LEFT JOIN abonnements a ON p.abonnement_id = a.id
    LEFT JOIN plans_abonnement pl ON a.plan_id = pl.id
    WHERE p.id = ?
");
$stmt->execute([$paiementId]);
$paiement = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$paiement) {
    die('Paiement introuvable');
}

// Générer le PDF (version simple HTML pour l'instant)
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture #<?= $paiement['id'] ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .header { text-align: center; margin-bottom: 40px; }
        .header h1 { color: #0099FF; margin: 0; }
        .info-section { margin: 30px 0; }
        .info-section h3 { color: #333; border-bottom: 2px solid #0099FF; padding-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        table th { background: #f8f9fa; font-weight: bold; }
        .total { font-size: 20px; font-weight: bold; color: #0099FF; text-align: right; margin-top: 20px; }
        .footer { margin-top: 50px; text-align: center; color: #666; font-size: 12px; }
        @media print {
            body { margin: 20px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #0099FF; color: white; border: none; cursor: pointer; border-radius: 5px;">
            Imprimer / Télécharger PDF
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; background: #6c757d; color: white; border: none; cursor: pointer; border-radius: 5px; margin-left: 10px;">
            Fermer
        </button>
    </div>

    <div class="header">
        <h1>LULU-OPEN</h1>
        <p>Facture de paiement</p>
    </div>

    <div class="info-section">
        <h3>Informations client</h3>
        <p><strong>Nom :</strong> <?= htmlspecialchars($paiement['nom_utilisateur']) ?></p>
        <p><strong>Email :</strong> <?= htmlspecialchars($paiement['email']) ?></p>
        <?php if ($paiement['telephone']): ?>
        <p><strong>Téléphone :</strong> <?= htmlspecialchars($paiement['telephone']) ?></p>
        <?php endif; ?>
    </div>

    <div class="info-section">
        <h3>Détails de la facture</h3>
        <p><strong>Numéro de facture :</strong> #<?= $paiement['id'] ?></p>
        <p><strong>Date :</strong> <?= date('d/m/Y H:i', strtotime($paiement['date_paiement'])) ?></p>
        <p><strong>Statut :</strong> <?= strtoupper($paiement['statut']) ?></p>
        <?php if ($paiement['transaction_id']): ?>
        <p><strong>Transaction ID :</strong> <?= htmlspecialchars($paiement['transaction_id']) ?></p>
        <?php endif; ?>
    </div>

    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th>Période</th>
                <th>Montant</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <?php if ($paiement['plan_nom']): ?>
                        Abonnement <?= htmlspecialchars($paiement['plan_nom']) ?>
                    <?php else: ?>
                        Paiement
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($paiement['abo_date_debut'] && $paiement['abo_date_fin']): ?>
                        Du <?= date('d/m/Y', strtotime($paiement['abo_date_debut'])) ?> 
                        au <?= date('d/m/Y', strtotime($paiement['abo_date_fin'])) ?>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
                <td><?= number_format($paiement['montant'], 2, ',', ' ') ?> €</td>
            </tr>
        </tbody>
    </table>

    <div class="total">
        Total : <?= number_format($paiement['montant'], 2, ',', ' ') ?> €
    </div>

    <div class="footer">
        <p>LULU-OPEN - Plateforme de mise en relation</p>
        <p>Merci pour votre confiance</p>
    </div>

    <script>
        // Auto-print si demandé
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('print') === '1') {
            window.onload = function() {
                window.print();
            };
        }
    </script>
</body>
</html>
