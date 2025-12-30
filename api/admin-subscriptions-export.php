<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/middleware-admin.php';
require_admin();

// Filtres
$statut_filter = $_GET['statut'] ?? '';
$plan_filter = $_GET['plan'] ?? '';
$search = $_GET['search'] ?? '';

$db = Database::getInstance()->getConnection();
$sql = "SELECT a.id, CONCAT(u.prenom, ' ', u.nom) as nom_utilisateur, u.email, u.type_utilisateur,
        p.nom as plan_nom, a.type_abonnement, a.statut, a.date_debut, a.date_fin, 
        a.prix as montant, a.auto_renouvellement as renouvellement_auto
        FROM abonnements a
        JOIN utilisateurs u ON a.utilisateur_id = u.id
        JOIN plans_abonnement p ON a.plan_id = p.id
        WHERE 1=1";
$params = [];

if ($statut_filter && $statut_filter !== 'tous') {
    $sql .= " AND a.statut = ?";
    $params[] = $statut_filter;
}
if ($plan_filter) {
    $sql .= " AND a.plan_id = ?";
    $params[] = $plan_filter;
}
if ($search) {
    $sql .= " AND (u.prenom LIKE ? OR u.nom LIKE ? OR u.email LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$sql .= " ORDER BY a.date_fin ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$abonnements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Headers pour téléchargement CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="abonnements_' . date('Y-m-d_His') . '.csv"');

// Ouvrir output stream
$output = fopen('php://output', 'w');

// BOM UTF-8 pour Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// En-têtes CSV
fputcsv($output, [
    'ID',
    'Utilisateur',
    'Email',
    'Type Utilisateur',
    'Plan',
    'Type Abonnement',
    'Statut',
    'Date Début',
    'Date Fin',
    'Montant',
    'Renouvellement Auto'
], ';');

// Données
foreach ($abonnements as $abo) {
    fputcsv($output, [
        $abo['id'],
        $abo['nom_utilisateur'],
        $abo['email'],
        $abo['type_utilisateur'],
        $abo['plan_nom'],
        $abo['type_abonnement'],
        $abo['statut'],
        date('d/m/Y', strtotime($abo['date_debut'])),
        date('d/m/Y', strtotime($abo['date_fin'])),
        $abo['montant'] . '€',
        $abo['renouvellement_auto'] ? 'Oui' : 'Non'
    ], ';');
}

fclose($output);
exit;
