<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/middleware-admin.php';
require_admin();

$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? '';
$statut_filter = $_GET['statut'] ?? '';
$date_debut = $_GET['date_debut'] ?? '';
$date_fin = $_GET['date_fin'] ?? '';

$db = Database::getInstance()->getConnection();
$sql = "SELECT u.id, u.prenom, u.nom, u.email, u.telephone, u.type_utilisateur, u.statut, u.date_inscription, u.derniere_connexion
        FROM utilisateurs u
        WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (u.prenom LIKE ? OR u.nom LIKE ? OR u.email LIKE ?)";
    $term = "%$search%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if ($role_filter) {
    $sql .= " AND u.type_utilisateur = ?";
    $params[] = $role_filter;
}

if ($statut_filter && $statut_filter !== 'tous') {
    $sql .= " AND u.statut = ?";
    $params[] = $statut_filter;
}

if ($date_debut) {
    $sql .= " AND DATE(u.date_inscription) >= ?";
    $params[] = $date_debut;
}

if ($date_fin) {
    $sql .= " AND DATE(u.date_inscription) <= ?";
    $params[] = $date_fin;
}

$sql .= " ORDER BY u.date_inscription DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="utilisateurs_' . date('Y-m-d_His') . '.csv"');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

fputcsv($output, [
    'ID',
    'Prénom',
    'Nom',
    'Email',
    'Téléphone',
    'Rôle',
    'Statut',
    'Date Inscription',
    'Dernière Connexion'
], ';');

foreach ($users as $u) {
    fputcsv($output, [
        $u['id'],
        $u['prenom'],
        $u['nom'],
        $u['email'],
        $u['telephone'] ?? '-',
        ucfirst($u['type_utilisateur']),
        ucfirst($u['statut']),
        date('d/m/Y H:i', strtotime($u['date_inscription'])),
        $u['derniere_connexion'] ? date('d/m/Y H:i', strtotime($u['derniere_connexion'])) : 'Jamais'
    ], ';');
}

fclose($output);
exit;
