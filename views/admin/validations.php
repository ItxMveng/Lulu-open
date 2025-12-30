<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/middleware-admin.php';
require_admin();

// Récupérer demandes d'activation
$db = Database::getInstance()->getConnection();

// Filtres
$statut_filter = $_GET['statut'] ?? 'tous';
$type_filter = $_GET['type'] ?? '';

$sql = "SELECT da.*, CONCAT(u.prenom, ' ', u.nom) as nom_complet, u.email, u.photo_profil,
        COALESCE(p.role, 'Plan Standard') as plan_nom
        FROM demandes_activation da
        JOIN utilisateurs u ON da.utilisateur_id = u.id
        LEFT JOIN pricings p ON da.plan_demande_id = p.id
        WHERE 1=1";
$params = [];

if ($statut_filter && $statut_filter !== 'tous') {
    $sql .= " AND da.statut = ?";
    $params[] = $statut_filter;
}
if ($type_filter) {
    $sql .= " AND da.type_utilisateur = ?";
    $params[] = $type_filter;
}

$sql .= " ORDER BY da.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$demandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pagination pour les abonnements
$page = isset($_GET['page_abonnements']) ? (int)$_GET['page_abonnements'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Compter le total
$stmt_count = $db->query("SELECT COUNT(*) FROM subscription_requests WHERE status = 'En Attente'");
$total_abonnements = $stmt_count->fetchColumn();
$total_pages = ceil($total_abonnements / $per_page);

// Récupérer demandes d'abonnement avec pagination
$sql_abonnements = "SELECT sr.*, CONCAT(u.prenom, ' ', u.nom) as nom_complet, u.email, u.photo_profil,
                           p.duration_months, p.price, p.currency,
                           CONCAT(UPPER(LEFT(p.role, 1)), LOWER(SUBSTRING(p.role, 2)), ' - ', p.duration_months, ' mois') as plan_nom
                    FROM subscription_requests sr
                    JOIN utilisateurs u ON sr.user_id = u.id
                    LEFT JOIN pricings p ON sr.pricing_id = p.id
                    WHERE sr.status = 'En Attente'
                    ORDER BY sr.submitted_at DESC
                    LIMIT ? OFFSET ?";

$stmt_abonnements = $db->prepare($sql_abonnements);
$stmt_abonnements->bindValue(1, $per_page, PDO::PARAM_INT);
$stmt_abonnements->bindValue(2, $offset, PDO::PARAM_INT);
$stmt_abonnements->execute();
$demandes_abonnements = $stmt_abonnements->fetchAll(PDO::FETCH_ASSOC);

// Statistiques
$stmt = $db->query("SELECT COUNT(*) FROM demandes_activation WHERE statut = 'en_attente'");
$total_en_attente = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM demandes_activation WHERE statut = 'approuve' AND DATE(date_verification) = CURDATE()");
$approuves_aujourdhui = $stmt->fetchColumn();

// Statistiques abonnements
$stmt = $db->query("SELECT COUNT(*) FROM subscription_requests WHERE status = 'En Attente'");
$total_abonnements_en_attente = $stmt->fetchColumn();

$page_title = "Validation Comptes - Admin LULU-OPEN";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= url('assets/css/admin-global.css') ?>" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
    .admin-content {
        margin-left: 260px;
        padding: 2rem;
        min-height: 100vh;
        background: #f8f9fa;
    }
    
    @media (max-width: 991.98px) {
        .admin-content {
            margin-left: 0;
            padding-top: calc(60px + 2rem);
        }
    }
    
    .validation-card {
        transition: all 0.3s ease;
        border-left: 4px solid var(--primary-blue);
    }

    .validation-card:hover {
        transform: translateX(5px);
        box-shadow: var(--shadow-lg);
    }

    .stat-mini {
        background: white;
        padding: 0.75rem 1rem;
        border-radius: 10px;
        box-shadow: var(--shadow-sm);
        min-width: 120px;
    }

    .stat-mini h4 {
        font-weight: 700;
    }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../includes/sidebar-admin.php'; ?>
    
    <div class="admin-content">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <div class="breadcrumb-custom">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="<?= url('views/admin/dashboard.php') ?>">Dashboard</a></li>
                    <li class="breadcrumb-item active">Validations</li>
                </ol>
            </div>
        </nav>
        
        <!-- En-tête -->
        <div class="row mb-4" data-aos="fade-down">
            <div class="col-md-8">
                <h1 class="mb-2" style="color: var(--primary-dark); font-weight: 700;">
                    <i class="bi bi-check-circle-fill me-2"></i>Validation des Comptes
                </h1>
                <p class="text-muted">Approuver ou refuser les demandes d'activation</p>
            </div>
            <div class="col-md-4 text-end">
                <div class="d-flex gap-2 justify-content-end">
                    <div class="stat-mini">
                        <small class="text-muted">Activations en attente</small>
                        <h4 class="mb-0 text-warning"><?= $total_en_attente ?></h4>
                    </div>
                    <div class="stat-mini">
                        <small class="text-muted">Abonnements en attente</small>
                        <h4 class="mb-0 text-info"><?= $total_abonnements_en_attente ?></h4>
                    </div>
                    <div class="stat-mini">
                        <small class="text-muted">Approuvés aujourd'hui</small>
                        <h4 class="mb-0 text-success"><?= $approuves_aujourdhui ?></h4>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filtres -->
        <div class="card-custom mb-4" data-aos="fade-up">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Statut</label>
                        <select name="statut" class="form-select" onchange="this.form.submit()">
                            <option value="tous" <?= $statut_filter === 'tous' ? 'selected' : '' ?>>Tous</option>
                            <option value="en_attente" <?= $statut_filter === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                            <option value="en_cours" <?= $statut_filter === 'en_cours' ? 'selected' : '' ?>>En cours</option>
                            <option value="approuve" <?= $statut_filter === 'approuve' ? 'selected' : '' ?>>Approuvé</option>
                            <option value="refuse" <?= $statut_filter === 'refuse' ? 'selected' : '' ?>>Refusé</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Type utilisateur</label>
                        <select name="type" class="form-select" onchange="this.form.submit()">
                            <option value="">Tous</option>
                            <option value="prestataire" <?= $type_filter === 'prestataire' ? 'selected' : '' ?>>Prestataire</option>
                            <option value="candidat" <?= $type_filter === 'candidat' ? 'selected' : '' ?>>Candidat</option>
                            <option value="prestataire_candidat" <?= $type_filter === 'prestataire_candidat' ? 'selected' : '' ?>>Prestataire-Candidat</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <a href="<?= url('views/admin/validations.php') ?>" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-x-circle"></i> Réinitialiser
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Section Abonnements -->
        <div class="mt-5" data-aos="fade-up">
            <h2 class="mb-4">
                <i class="bi bi-credit-card-fill me-2 text-info"></i>Demandes d'Abonnement
            </h2>

            <?php if (empty($demandes_abonnements)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-credit-card" style="font-size: 5rem; color: var(--text-muted);"></i>
                    <h3 class="mt-3">Aucune demande d'abonnement</h3>
                    <p class="text-muted">Aucune demande d'abonnement en attente</p>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($demandes_abonnements as $demande): ?>
                    <div class="col-12">
                        <div class="card-custom validation-card" style="border-left-color: #17a2b8;">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <!-- Info utilisateur -->
                                    <div class="col-lg-3">
                                        <div class="d-flex align-items-center">
                                            <?php
                                            $photo = $demande['photo_profil'] ?? null;
                                            // Debug: afficher la valeur pour comprendre le format
                                            error_log("Photo profil: " . $photo);
                                            if ($photo) {
                                                // Si c'est déjà une URL complète
                                                if (strpos($photo, 'http') === 0) {
                                                    $photo_url = $photo;
                                                }
                                                // Si c'est un chemin relatif commençant par uploads/
                                                elseif (strpos($photo, 'uploads/') === 0) {
                                                    $photo_url = url($photo);
                                                }
                                                // Si c'est juste le nom du fichier
                                                else {
                                                    $photo_url = url('uploads/' . $photo);
                                                }
                                            } else {
                                                $photo_url = url('assets/images/default-avatar.png');
                                            }
                                            // Debug: afficher l'URL finale
                                            error_log("Photo URL: " . $photo_url);
                                            ?>
                                            <img src="<?= $photo_url ?>" class="rounded-circle me-3"
                                                 style="width: 50px; height: 50px; object-fit: cover;">
                                            <div>
                                                <h5 class="mb-1 fw-bold"><?= htmlspecialchars($demande['nom_complet']) ?></h5>
                                                <p class="mb-1 text-muted small">
                                                    <i class="bi bi-envelope"></i> <?= htmlspecialchars($demande['email']) ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Détails abonnement -->
                                    <div class="col-lg-4">
                                        <div class="mb-2">
                                            <small class="text-muted">Plan demandé :</small>
                                            <strong class="d-block">
                                                <?= $demande['plan_nom'] ?? 'Plan ' . $demande['duration_months'] . ' mois' ?>
                                            </strong>
                                        </div>
                                        <div class="mb-2">
                                            <small class="text-muted">Montant :</small>
                                            <strong class="d-block text-success">
                                                <?= number_format($demande['amount_paid'] ?? $demande['price'] ?? 0, 2) ?> <?= $demande['currency'] ?? 'EUR' ?>
                                            </strong>
                                        </div>
                                        <div class="mb-2">
                                            <small class="text-muted">Date demande :</small>
                                            <strong class="d-block">
                                                <?= date('d/m/Y H:i', strtotime($demande['submitted_at'])) ?>
                                            </strong>
                                        </div>
                                    </div>

                                    <!-- Moyen de paiement -->
                                    <div class="col-lg-3">
                                        <div class="mb-2">
                                            <small class="text-muted">Paiement :</small>
                                            <strong class="d-block">
                                                <i class="bi bi-<?= $demande['payment_method'] === 'PayPal' ? 'paypal' : 'bank' ?>"></i>
                                                <?= $demande['payment_method'] ?>
                                            </strong>
                                        </div>
                                        <?php if ($demande['proof_document_path']): ?>
                                            <a href="<?= url($demande['proof_document_path']) ?>"
                                               target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-file-earmark"></i> Voir justificatif
                                            </a>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Actions -->
                                    <div class="col-lg-2 text-end">
                                        <div class="d-flex flex-column gap-2">
                                            <button class="btn btn-success w-100"
                                                    onclick="validerAbonnement(<?= $demande['id'] ?>, 'approuver')">
                                                <i class="bi bi-check-circle-fill"></i> Activer
                                            </button>
                                            <button class="btn btn-danger w-100"
                                                    onclick="openRefuseAbonnementModal(<?= $demande['id'] ?>)">
                                                <i class="bi bi-x-circle-fill"></i> Refuser
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination pour les abonnements -->
                <?php if ($total_pages > 1): ?>
                <div class="d-flex justify-content-center mt-4">
                    <nav aria-label="Pagination abonnements">
                        <ul class="pagination">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page_abonnements' => $page - 1])) ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page_abonnements' => $i])) ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page_abonnements' => $page + 1])) ?>">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Section Activations -->
        <div class="mt-5" data-aos="fade-up">
            <h2 class="mb-4">
                <i class="bi bi-person-check-fill me-2 text-primary"></i>Demandes d'Activation
            </h2>

        <!-- Liste demandes -->
        <?php if (empty($demandes)): ?>
            <div class="text-center py-5" data-aos="fade-up">
                <i class="bi bi-inbox" style="font-size: 5rem; color: var(--text-muted);"></i>
                <h3 class="mt-3">Aucune demande</h3>
                <p class="text-muted">Aucune demande d'activation pour ces filtres</p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($demandes as $demande): ?>
                <div class="col-12" data-aos="fade-up">
                    <div class="card-custom validation-card">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <!-- Info utilisateur -->
                                <div class="col-lg-4">
                                    <div class="d-flex align-items-center">
                                        <?php
                                        $photo = $demande['photo_profil'] ?? null;
                                        if ($photo && strpos($photo, 'http') !== 0) {
                                            $photo = url($photo);
                                        } elseif (!$photo) {
                                            $photo = url('assets/images/default-avatar.png');
                                        }
                                        ?>

                                        <div>
                                            <h5 class="mb-1 fw-bold"><?= htmlspecialchars($demande['nom_complet']) ?></h5>
                                            <p class="mb-1 text-muted small">
                                                <i class="bi bi-envelope"></i> <?= htmlspecialchars($demande['email']) ?>
                                            </p>
                                            <span class="badge" style="background: <?= getRoleColor($demande['type_utilisateur']) ?>;">
                                                <?= ucfirst(str_replace('_', '-', $demande['type_utilisateur'])) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Détails demande -->
                                <div class="col-lg-4">
                                    <div class="mb-2">
                                        <small class="text-muted">Plan demandé :</small>
                                        <strong class="d-block"><?= $demande['plan_nom'] ?? 'Non spécifié' ?></strong>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted">Date demande :</small>
                                        <strong class="d-block"><?= date('d/m/Y H:i', strtotime($demande['created_at'])) ?></strong>
                                    </div>
                                    <?php if ($demande['documents_fournis']): ?>
                                        <div>
                                            <small class="text-muted">Documents :</small>
                                            <?php
                                            $docs = json_decode($demande['documents_fournis'], true);
                                            if ($docs):
                                                foreach ($docs as $doc):
                                            ?>
                                                <a href="<?= url('uploads/documents/' . $doc) ?>" target="_blank" class="badge bg-info me-1">
                                                    <i class="bi bi-file-earmark"></i> Voir
                                                </a>
                                            <?php
                                                endforeach;
                                            endif;
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Actions -->
                                <div class="col-lg-4 text-end">
                                    <?php if ($demande['statut'] === 'en_attente' || $demande['statut'] === 'en_cours'): ?>
                                        <div class="d-flex flex-column gap-2">
                                            <button class="btn btn-success w-100"
                                                    onclick="validerDemande(<?= $demande['id'] ?>, 'approuver')">
                                                <i class="bi bi-check-circle-fill"></i> Approuver
                                            </button>
                                            <button class="btn btn-danger w-100"
                                                    onclick="openRefuseModal(<?= $demande['id'] ?>)">
                                                <i class="bi bi-x-circle-fill"></i> Refuser
                                            </button>
                                            <?php if ($demande['statut'] === 'en_attente'): ?>
                                            <button class="btn btn-outline-secondary w-100"
                                                    onclick="marquerEnCours(<?= $demande['id'] ?>)">
                                                <i class="bi bi-hourglass"></i> Marquer en cours
                                            </button>
                                            <?php else: ?>
                                            <span class="badge bg-info w-100 py-2" style="font-size: 0.9rem;">
                                                <i class="bi bi-hourglass-split"></i> En cours de traitement
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center">
                                            <?php
                                            $statusClass = match($demande['statut']) {
                                                'approuve' => 'success',
                                                'refuse' => 'danger',
                                                default => 'secondary'
                                            };
                                            ?>
                                            <span class="badge badge-custom bg-<?= $statusClass ?> mb-2" style="font-size: 1rem;">
                                                <?= ucfirst($demande['statut']) ?>
                                            </span>
                                            <?php if ($demande['date_verification']): ?>
                                                <p class="text-muted small mb-0">
                                                    Le <?= date('d/m/Y à H:i', strtotime($demande['date_verification'])) ?>
                                                </p>
                                            <?php endif; ?>
                                            <?php if ($demande['statut'] === 'refuse' && $demande['motif_refus']): ?>
                                                <p class="text-danger small mt-2">
                                                    <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($demande['motif_refus']) ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        </div>
    </div>

    <!-- Modal Refus -->
    <div class="modal fade" id="modalRefus" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background: var(--gradient-primary); color: white;">
                    <h5 class="modal-title">
                        <i class="bi bi-x-circle-fill me-2"></i>Refuser la demande
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="formRefus">
                    <div class="modal-body">
                        <input type="hidden" id="demandeIdRefus">
                        <div class="mb-3">
                            <label class="form-label">Motif du refus <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="motifRefus" rows="4" required
                                      placeholder="Expliquez la raison du refus..."></textarea>
                            <small class="text-muted">Ce motif sera envoyé à l'utilisateur par email</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-x-circle"></i> Confirmer le refus
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Refus Abonnement -->
    <div class="modal fade" id="modalRefusAbonnement" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-x-circle-fill me-2"></i>Refuser la demande d'abonnement
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="formRefusAbonnement">
                    <div class="modal-body">
                        <input type="hidden" id="abonnementIdRefus">
                        <div class="mb-3">
                            <label class="form-label">Motif du refus <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="motifRefusAbonnement" rows="4" required
                                      placeholder="Expliquez la raison du refus..."></textarea>
                            <small class="text-muted">Ce motif sera envoyé à l'utilisateur</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-x-circle"></i> Confirmer le refus
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({ duration: 600, once: true });
        
        const modalRefus = new bootstrap.Modal(document.getElementById('modalRefus'));
        const modalRefusAbonnement = new bootstrap.Modal(document.getElementById('modalRefusAbonnement'));
        
        function openRefuseModal(demandeId) {
            document.getElementById('demandeIdRefus').value = demandeId;
            document.getElementById('motifRefus').value = '';
            modalRefus.show();
        }

        function openRefuseAbonnementModal(abonnementId) {
            document.getElementById('abonnementIdRefus').value = abonnementId;
            document.getElementById('motifRefusAbonnement').value = '';
            modalRefusAbonnement.show();
        }
        
        document.getElementById('formRefus').addEventListener('submit', async function(e) {
            e.preventDefault();
            const demandeId = document.getElementById('demandeIdRefus').value;
            const motif = document.getElementById('motifRefus').value;

            await validerDemande(demandeId, 'refuser', motif);
            modalRefus.hide();
        });

        document.getElementById('formRefusAbonnement').addEventListener('submit', async function(e) {
            e.preventDefault();
            const abonnementId = document.getElementById('abonnementIdRefus').value;
            const motif = document.getElementById('motifRefusAbonnement').value;

            await validerAbonnement(abonnementId, 'refuser', motif);
            modalRefusAbonnement.hide();
        });
        
        async function validerDemande(demandeId, action, motif = '') {
            if (!confirm(`Êtes-vous sûr de vouloir ${action} cette demande ?`)) return;
            
            try {
                const response = await fetch('<?= url("api/admin-validations.php") ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: demandeId, action, motif })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert(data.message || 'Action réalisée avec succès');
                    location.reload();
                } else {
                    alert('Erreur : ' + (data.message || 'Une erreur est survenue'));
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('Erreur réseau');
            }
        }
        
        async function validerAbonnement(abonnementId, action, motif = '') {
            if (!confirm(`Êtes-vous sûr de vouloir ${action} cette demande d'abonnement ?`)) return;

            try {
                const response = await fetch('<?= url("api/admin-subscriptions.php") ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: abonnementId, action, motif })
                });

                const data = await response.json();

                if (data.success) {
                    alert(data.message || 'Action réalisée avec succès');
                    location.reload();
                } else {
                    alert('Erreur : ' + (data.message || 'Une erreur est survenue'));
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('Erreur réseau');
            }
        }

        async function marquerEnCours(demandeId) {
            console.log('marquerEnCours appelé avec ID:', demandeId);
            try {
                const payload = { id: demandeId, action: 'en_cours' };
                console.log('Payload envoyé:', payload);

                const response = await fetch('<?= url("api/admin-validations.php") ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                console.log('Réponse HTTP status:', response.status);
                const data = await response.json();
                console.log('Réponse JSON:', data);

                if (data.success) {
                    console.log('Succès - rechargement de la page');
                    location.reload();
                } else {
                    console.error('Erreur API:', data.message);
                    alert('Erreur : ' + data.message);
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('Erreur réseau');
            }
        }
    </script>
</body>
</html>

<?php
function getRoleColor($role) {
    return match($role) {
        'prestataire' => '#0099FF',
        'candidat' => '#00ccff',
        'prestataire_candidat' => '#FFD700',
        default => '#6c757d'
    };
}
?>
