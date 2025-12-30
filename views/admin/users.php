<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/middleware-admin.php';
require_admin();

$db = Database::getInstance()->getConnection();

// Filtres
$search = $_GET['search'] ?? '';
$categorie_filter = $_GET['categorie'] ?? '';
$statut_filter = $_GET['statut'] ?? 'actif';
$date_debut = $_GET['date_debut'] ?? '';
$date_fin = $_GET['date_fin'] ?? '';

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Récupérer la liste des catégories pertinentes
$categories = $db->query("SELECT id, nom FROM categories_services ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC);
$categorie_filter = $_GET['categorie'] ?? '';

// Requête principale
$sql = "SELECT u.*,
    (SELECT COUNT(*) FROM abonnements a WHERE a.utilisateur_id = u.id AND a.statut IN ('actif','essai')) as nb_abonnements_actifs,
    (SELECT COUNT(*) FROM paiements p WHERE p.utilisateur_id = u.id AND p.statut = 'valide') as nb_paiements_valides,
    (SELECT COUNT(*) FROM demandes_activation d WHERE d.utilisateur_id = u.id AND d.statut = 'en_attente') as nb_demandes_en_attente
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

if ($categorie_filter) {
    $sql .= " AND (
        (u.type_utilisateur = 'prestataire' AND EXISTS (
            SELECT 1 FROM prestataire_categories pc
            WHERE pc.prestataire_id = u.id AND pc.categorie_id = ?
        )) OR
        (u.type_utilisateur = 'candidat' AND EXISTS (
            SELECT 1 FROM cv_categories cc
            JOIN cvs cv ON cc.cv_id = cv.id
            WHERE cv.utilisateur_id = u.id AND cc.categorie_id = ?
        )) OR
        (u.type_utilisateur = 'prestataire_candidat' AND (
            EXISTS (
                SELECT 1 FROM prestataire_categories pc
                WHERE pc.prestataire_id = u.id AND pc.categorie_id = ?
            ) OR EXISTS (
                SELECT 1 FROM cv_categories cc
                JOIN cvs cv ON cc.cv_id = cv.id
                WHERE cv.utilisateur_id = u.id AND cc.categorie_id = ?
            )
        ))
    )";
    $params[] = $categorie_filter;
    $params[] = $categorie_filter;
    $params[] = $categorie_filter;
    $params[] = $categorie_filter;
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

$sql .= " ORDER BY u.date_inscription DESC LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;

$stmt = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Compter le total pour la pagination
$total_sql = "SELECT COUNT(*) FROM utilisateurs u WHERE 1=1";
$total_params = [];
if ($search) {
    $total_sql .= " AND (u.prenom LIKE ? OR u.nom LIKE ? OR u.email LIKE ?)";
    $term = "%$search%";
    $total_params[] = $term;
    $total_params[] = $term;
    $total_params[] = $term;
}
if ($categorie_filter) {
    $total_sql .= " AND (
        (u.type_utilisateur = 'prestataire' AND EXISTS (
            SELECT 1 FROM prestataire_categories pc
            WHERE pc.prestataire_id = u.id AND pc.categorie_id = ?
        )) OR
        (u.type_utilisateur = 'candidat' AND EXISTS (
            SELECT 1 FROM cv_categories cc
            JOIN cvs cv ON cc.cv_id = cv.id
            WHERE cv.utilisateur_id = u.id AND cc.categorie_id = ?
        )) OR
        (u.type_utilisateur = 'prestataire_candidat' AND (
            EXISTS (
                SELECT 1 FROM prestataire_categories pc
                WHERE pc.prestataire_id = u.id AND pc.categorie_id = ?
            ) OR EXISTS (
                SELECT 1 FROM cv_categories cc
                JOIN cvs cv ON cc.cv_id = cv.id
                WHERE cv.utilisateur_id = u.id AND cc.categorie_id = ?
            )
        ))
    )";
    $total_params[] = $categorie_filter;
    $total_params[] = $categorie_filter;
    $total_params[] = $categorie_filter;
    $total_params[] = $categorie_filter;
}
if ($statut_filter && $statut_filter !== 'tous') {
    $total_sql .= " AND u.statut = ?";
    $total_params[] = $statut_filter;
}
if ($date_debut) {
    $total_sql .= " AND DATE(u.date_inscription) >= ?";
    $total_params[] = $date_debut;
}
if ($date_fin) {
    $total_sql .= " AND DATE(u.date_inscription) <= ?";
    $total_params[] = $date_fin;
}
$total_stmt = $db->prepare($total_sql);
$total_stmt->execute($total_params);
$total_users = $total_stmt->fetchColumn();
$total_pages = ceil($total_users / $per_page);

// Stats globales
$stats = [
    'total' => $db->query("SELECT COUNT(*) FROM utilisateurs")->fetchColumn(),
    'actifs' => $db->query("SELECT COUNT(*) FROM utilisateurs WHERE statut = 'actif'")->fetchColumn(),
    'suspendus' => $db->query("SELECT COUNT(*) FROM utilisateurs WHERE statut = 'suspendu'")->fetchColumn(),
    'bloques' => $db->query("SELECT COUNT(*) FROM utilisateurs WHERE statut = 'bloque'")->fetchColumn(),
];

$page_title = "Gestion Utilisateurs - Admin LULU-OPEN";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= $page_title ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= url('assets/css/admin-global.css') ?>" rel="stylesheet">
</head>
<body>

<?php include __DIR__ . '/../../includes/sidebar-admin.php'; ?>

<div class="admin-content">
    <nav aria-label="breadcrumb" class="mb-4">
        <div class="breadcrumb-custom">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= url('views/admin/dashboard.php') ?>">Dashboard</a></li>
                <li class="breadcrumb-item active">Utilisateurs</li>
            </ol>
        </div>
    </nav>

    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="mb-2" style="color: var(--primary-dark); font-weight: 700;">
                <i class="bi bi-people-fill me-2"></i>Gestion des Utilisateurs
            </h1>
            <p class="text-muted mb-0">Gérer les comptes, statuts et accès</p>
        </div>
        <div class="col-md-6">
            <div class="row g-2">
                <div class="col-6 col-md-3">
                    <div class="stat-mini-card">
                        <small class="text-muted">Total</small>
                        <h4 class="mb-0 text-primary"><?= $stats['total'] ?></h4>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-mini-card">
                        <small class="text-muted">Actifs</small>
                        <h4 class="mb-0 text-success"><?= $stats['actifs'] ?></h4>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-mini-card">
                        <small class="text-muted">Suspendus</small>
                        <h4 class="mb-0 text-warning"><?= $stats['suspendus'] ?></h4>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-mini-card">
                        <small class="text-muted">Bloqués</small>
                        <h4 class="mb-0 text-danger"><?= $stats['bloques'] ?></h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card-custom mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Rechercher</label>
                    <input type="text" name="search" class="form-control"
                           placeholder="Nom, email..." value="<?= htmlspecialchars($search) ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Catégorie</label>
                    <select name="categorie" class="form-select">
                        <option value="">Toutes</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $categorie_filter == $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['nom']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Statut</label>
                    <select name="statut" class="form-select">
                        <option value="actif" <?= $statut_filter === 'actif' ? 'selected' : '' ?>>Actifs</option>
                        <option value="en_attente" <?= $statut_filter === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                        <option value="suspendu" <?= $statut_filter === 'suspendu' ? 'selected' : '' ?>>Suspendus</option>
                        <option value="bloque" <?= $statut_filter === 'bloque' ? 'selected' : '' ?>>Bloqués</option>
                        <option value="tous" <?= $statut_filter === 'tous' ? 'selected' : '' ?>>Tous</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Date création</label>
                    <div class="d-flex gap-2">
                        <input type="date" name="date_debut" class="form-control" value="<?= $date_debut ?>">
                        <input type="date" name="date_fin" class="form-control" value="<?= $date_fin ?>">
                    </div>
                </div>

                <div class="col-12 d-flex justify-content-between align-items-center">
                    <div>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-search"></i> Filtrer
                        </button>
                        <a href="<?= url('views/admin/users.php') ?>" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-x-circle"></i> Réinitialiser
                        </a>
                    </div>
                    <button type="button" class="btn btn-outline-success btn-sm" onclick="exportUsersCSV()">
                        <i class="bi bi-download"></i> Export CSV
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card-custom">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead style="background: var(--gradient-primary); color: white;">
                    <tr>
                        <th>Utilisateur</th>
                        <th>Rôle</th>
                        <th>Statut</th>
                        <th>Activité</th>
                        <th>Créé le</th>
                        <th>Conn. récente</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="bi bi-inbox" style="font-size: 3rem; color: var(--text-muted);"></i>
                                <p class="text-muted mt-3 mb-0">Aucun utilisateur trouvé</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user):
                            $role = $user['type_utilisateur'];
                            $statut = $user['statut'];
                            $roleColors = [
                                'prestataire' => '#0099FF',
                                'candidat' => '#00ccff',
                                'prestataire_candidat' => '#FFD700',
                                'client' => '#9D4EDD',
                                'admin' => '#dc3545'
                            ];
                            $roleColor = $roleColors[$role] ?? '#6c757d';

                            $statutClass = match($statut) {
                                'actif' => 'badge-status-actif',
                                'en_attente' => 'badge-status-en_attente',
                                'suspendu' => 'badge-status-suspendu',
                                'bloque' => 'badge-status-bloque',
                                default => 'bg-secondary'
                            };

                            $photoUrl = '/lulu/assets/images/default-avatar.png';
                            if ($user['photo_profil']) {
                                if (strpos($user['photo_profil'], 'http') === 0) {
                                    $photoUrl = $user['photo_profil'];
                                } elseif (strpos($user['photo_profil'], 'uploads/') === 0) {
                                    $photoUrl = '/lulu/' . $user['photo_profil'];
                                } elseif (strpos($user['photo_profil'], 'profiles/') === 0) {
                                    $photoUrl = '/lulu/uploads/' . $user['photo_profil'];
                                } else {
                                    $photoUrl = '/lulu/uploads/profiles/' . $user['photo_profil'];
                                }
                            }
                        ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="<?= $photoUrl ?>"
                                             class="rounded-circle me-2"
                                             style="width: 40px; height: 40px; object-fit: cover;"
                                             onerror="this.src='/lulu/assets/images/default-avatar.png'">
                                        <div>
                                            <strong><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></strong>
                                            <br>
                                            <small class="text-muted"><?= htmlspecialchars($user['email']) ?></small>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <span class="badge badge-custom" style="background: <?= $roleColor ?>;">
                                        <?= ucfirst(str_replace('_', '-', $role)) ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="badge <?= $statutClass ?>">
                                        <?= ucfirst(str_replace('_', ' ', $statut)) ?>
                                    </span>
                                </td>

                                <td>
                                    <small class="text-muted d-block">
                                        Abonnements actifs : <strong><?= $user['nb_abonnements_actifs'] ?></strong>
                                    </small>
                                    <small class="text-muted d-block">
                                        Paiements valides : <strong><?= $user['nb_paiements_valides'] ?></strong>
                                    </small>
                                    <?php if ($user['nb_demandes_en_attente'] > 0): ?>
                                        <small class="text-warning d-block">
                                            Demandes en attente : <strong><?= $user['nb_demandes_en_attente'] ?></strong>
                                        </small>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= date('d/m/Y', strtotime($user['date_inscription'])) ?>
                                    <br><small class="text-muted"><?= date('H:i', strtotime($user['date_inscription'])) ?></small>
                                </td>

                                <td>
                                    <?php if (!empty($user['derniere_connexion'])): ?>
                                        <small><?= date('d/m/Y H:i', strtotime($user['derniere_connexion'])) ?></small>
                                    <?php else: ?>
                                        <small class="text-muted">Jamais</small>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary btn-action"
                                                title="Détails"
                                                onclick="showUserDetails(<?= $user['id'] ?>)">
                                            <i class="bi bi-eye"></i>
                                        </button>

                                        <button class="btn btn-outline-info btn-action"
                                                title="Voir abonnements"
                                                onclick="goToUserSubscriptions(<?= $user['id'] ?>)">
                                            <i class="bi bi-award"></i>
                                        </button>

                                        <button class="btn btn-outline-success btn-action"
                                                title="Voir paiements"
                                                onclick="goToUserPayments(<?= $user['id'] ?>)">
                                            <i class="bi bi-credit-card"></i>
                                        </button>

                                        <?php if ($_SESSION['user_id'] != $user['id']): ?>
                                            <?php if ($statut === 'actif'): ?>
                                                <button class="btn btn-outline-warning btn-action"
                                                        title="Suspendre"
                                                        onclick="changeUserStatus(<?= $user['id'] ?>, 'suspendu')">
                                                    <i class="bi bi-pause-circle"></i>
                                                </button>
                                            <?php elseif ($statut === 'suspendu'): ?>
                                                <button class="btn btn-outline-success btn-action"
                                                        title="Réactiver"
                                                        onclick="changeUserStatus(<?= $user['id'] ?>, 'actif')">
                                                    <i class="bi bi-play-circle"></i>
                                                </button>
                                            <?php endif; ?>

                                            <?php if ($statut !== 'bloque'): ?>
                                                <button class="btn btn-outline-danger btn-action"
                                                        title="Bloquer définitivement"
                                                        onclick="changeUserStatus(<?= $user['id'] ?>, 'bloque')">
                                                    <i class="bi bi-slash-circle"></i>
                                                </button>
                                            <?php endif; ?>

                                            <button class="btn btn-outline-secondary btn-action"
                                                    title="Réinitialiser mot de passe"
                                                    onclick="resetUserPassword(<?= $user['id'] ?>)">
                                                <i class="bi bi-key"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Pagination utilisateurs" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                            <i class="bi bi-chevron-left"></i> Précédent
                        </a>
                    </li>
                <?php endif; ?>

                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                if ($start_page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">1</a>
                    </li>
                    <?php if ($start_page > 2): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>

                <?php if ($end_page < $total_pages): ?>
                    <?php if ($end_page < $total_pages - 1): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>"><?= $total_pages ?></a>
                    </li>
                <?php endif; ?>

                <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                            Suivant <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Détails Utilisateur -->
<div class="modal fade" id="modalUserDetails" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: var(--gradient-primary); color: white;">
                <h5 class="modal-title">
                    <i class="bi bi-person-badge-fill me-2"></i>Détails de l'utilisateur
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="userDetailsContent">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Confirmation Changement Statut -->
<div class="modal fade" id="modalUserStatus" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Confirmer le changement de statut
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="statusUserId">
                <input type="hidden" id="statusNewValue">
                <p id="statusConfirmText"></p>
                <div class="mb-3">
                    <label class="form-label">Motif (optionnel, recommandé pour suspendre/bloquer)</label>
                    <textarea id="statusReason" class="form-control" rows="3"></textarea>
                </div>
                <div class="alert alert-warning mb-0">
                    <i class="bi bi-shield-lock"></i>
                    <small>Cette action sera journalisée dans les logs admin.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button class="btn btn-warning" onclick="confirmChangeUserStatus()">
                    <i class="bi bi-check-circle"></i> Confirmer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Confirmation Reset Password -->
<div class="modal fade" id="modalUserReset" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="bi bi-key-fill me-2"></i>Réinitialiser le mot de passe
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="resetUserId">
                <p>
                    Êtes-vous sûr de vouloir <strong>réinitialiser le mot de passe</strong> de cet utilisateur ?
                </p>
                <p class="mb-0">
                    Un mot de passe temporaire sera généré et envoyé par email. L'utilisateur devra le changer à la prochaine connexion.
                </p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button class="btn btn-danger" onclick="confirmResetUserPassword()">
                    <i class="bi bi-key"></i> Confirmer
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.stat-mini-card {
    background: white;
    padding: 0.8rem 1rem;
    border-radius: 10px;
    box-shadow: var(--shadow-sm);
    text-align: center;
}
.table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.btn-action {
    white-space: nowrap;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= url('assets/js/admin-users.js') ?>"></script>
</body>
</html>
