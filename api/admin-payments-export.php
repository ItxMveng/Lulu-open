<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/middleware-admin.php';
require_admin();

// Filtres (mêmes que la page principale)
$statut_filter = $_GET['statut'] ?? '';
$methode_filter = $_GET['methode'] ?? '';
$search = $_GET['search'] ?? '';
$date_debut = $_GET['date_debut'] ?? date('Y-m-01');
$date_fin = $_GET['date_fin'] ?? date('Y-m-d');

$db = Database::getInstance()->getConnection();
$sql = "SELECT p.id, p.date_paiement, 
        CONCAT(u.prenom, ' ', u.nom) as nom_utilisateur, 
        u.email,
        pl.nom as plan_nom,
        p.montant, p.methode_paiement, p.statut, p.transaction_id
        FROM paiements p
        JOIN utilisateurs u ON p.utilisateur_id = u.id
        LEFT JOIN abonnements a ON p.abonnement_id = a.id
        LEFT JOIN plans_abonnement pl ON a.plan_id = pl.id
        WHERE 1=1";
$params = [];

if ($statut_filter && $statut_filter !== 'tous') {
    $sql .= " AND p.statut = ?";
    $params[] = $statut_filter;
}
if ($methode_filter) {
    $sql .= " AND p.methode_paiement = ?";
    $params[] = $methode_filter;
}
if ($search) {
    $sql .= " AND (u.prenom LIKE ? OR u.nom LIKE ? OR u.email LIKE ? OR p.transaction_id LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}
if ($date_debut) {
    $sql .= " AND DATE(p.date_paiement) >= ?";
    $params[] = $date_debut;
}
if ($date_fin) {
    $sql .= " AND DATE(p.date_paiement) <= ?";
    $params[] = $date_fin;
}

$sql .= " ORDER BY p.date_paiement DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$paiements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Headers pour téléchargement CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="paiements_' . date('Y-m-d_His') . '.csv"');

// Ouvrir output stream
$output = fopen('php://output', 'w');

// BOM UTF-8 pour Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// En-têtes CSV
fputcsv($output, [
    'ID',
    'Date Paiement',
    'Utilisateur',
    'Email',
    'Plan',
    'Montant',
    'Méthode',
    'Statut',
    'Transaction ID'
], ';');

// Données
foreach ($paiements as $p) {
    fputcsv($output, [
        $p['id'],
        date('d/m/Y H:i', strtotime($p['date_paiement'])),
        $p['nom_utilisateur'],
        $p['email'],
        $p['plan_nom'] ?? 'N/A',
        $p['montant'] . ' €',
        ucfirst($p['methode_paiement']),
        ucfirst($p['statut']),
        $p['transaction_id'] ?? '-'
    ], ';');
}

fclose($output);
exit;
