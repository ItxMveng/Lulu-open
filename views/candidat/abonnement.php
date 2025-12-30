<?php
session_start();
require_once '../../config/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/sidebar.php';

if (!isLoggedIn() || !in_array($_SESSION['user_type'], ['candidat', 'prestataire_candidat'])) {
    header('Location: ../../login.php');
    exit;
}

global $database;
$user = $database->fetch("SELECT * FROM utilisateurs WHERE id = ?", [$_SESSION['user_id']]);

// Traitement des demandes d'abonnement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'request_subscription') {
        $plan_id = $_POST['plan_id'];
        $duree = $_POST['duree'];
        $montant = $_POST['montant'];
        
        // Gestion du fichier de paiement
        $fichier_paiement = null;
        if (isset($_FILES['preuve_paiement']) && $_FILES['preuve_paiement']['error'] === 0) {
            $filename = 'paiement_' . $_SESSION['user_id'] . '_' . time() . '_' . $_FILES['preuve_paiement']['name'];
            $upload_path = '../../uploads/paiements/' . $filename;
            
            if (!is_dir('../../uploads/paiements/')) {
                mkdir('../../uploads/paiements/', 0755, true);
            }
            
            if (move_uploaded_file($_FILES['preuve_paiement']['tmp_name'], $upload_path)) {
                $fichier_paiement = $filename;
            }
        }
        
        // Enregistrer la demande d'abonnement
        $database->query("INSERT INTO demandes_abonnement (utilisateur_id, plan_id, duree_mois, montant, fichier_paiement, statut, date_demande) VALUES (?, ?, ?, ?, ?, 'en_attente', NOW())", 
            [$_SESSION['user_id'], $plan_id, $duree, $montant, $fichier_paiement]);
        
        flashMessage('Demande d\'abonnement soumise avec succès ! Elle sera traitée sous 24h.', 'success');
    }
}

// Récupérer l'abonnement actuel
$abonnement_actuel = $database->fetch("SELECT * FROM abonnements WHERE utilisateur_id = ? AND statut = 'actif' ORDER BY date_fin DESC LIMIT 1", [$_SESSION['user_id']]);

// Récupérer les demandes en cours
$demandes_en_cours = $database->fetchAll("SELECT * FROM demandes_abonnement WHERE utilisateur_id = ? AND statut = 'en_attente' ORDER BY date_demande DESC", [$_SESSION['user_id']]);

// Plans disponibles
$plans = [
    [
        'id' => 1,
        'nom' => 'Plan Basique',
        'prix_mensuel' => 9.99,
        'prix_annuel' => 99.99,
        'fonctionnalites' => [
            'Profil candidat complet',
            'Candidatures illimitées',
            'Analyse IA basique',
            'Support email'
        ],
        'couleur' => 'primary'
    ],
    [
        'id' => 2,
        'nom' => 'Plan Premium',
        'prix_mensuel' => 19.99,
        'prix_annuel' => 199.99,
        'fonctionnalites' => [
            'Toutes les fonctionnalités Basique',
            'Analyse IA avancée',
            'Génération CV optimisé',
            'Lettres de motivation IA',
            'Priorité dans les recherches',
            'Support prioritaire'
        ],
        'couleur' => 'success',
        'populaire' => true
    ],
    [
        'id' => 3,
        'nom' => 'Plan Professionnel',
        'prix_mensuel' => 39.99,
        'prix_annuel' => 399.99,
        'fonctionnalites' => [
            'Toutes les fonctionnalités Premium',
            'Coaching personnalisé',
            'Entretiens simulés',
            'Réseau professionnel étendu',
            'Statistiques détaillées',
            'Support téléphonique'
        ],
        'couleur' => 'warning'
    ]
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Abonnement - LULU-OPEN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .subscription-card {
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: none;
            position: relative;
            overflow: hidden;
        }
        .subscription-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        }
        .plan-header {
            padding: 2rem;
            text-align: center;
            position: relative;
        }
        .plan-header.bg-primary { background: linear-gradient(135deg, #0099FF 0%, #0066CC 100%); }
        .plan-header.bg-success { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); }
        .plan-header.bg-warning { background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%); }
        
        .popular-badge {
            position: absolute;
            top: -10px;
            right: 20px;
            background: #ff6b6b;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            transform: rotate(10deg);
        }
        .price-display {
            font-size: 2.5rem;
            font-weight: bold;
            color: white;
        }
        .price-period {
            font-size: 1rem;
            opacity: 0.8;
        }
        .feature-list {
            padding: 0;
            list-style: none;
        }
        .feature-list li {
            padding: 0.75rem 0;
            border-bottom: 1px solid #f8f9fa;
            display: flex;
            align-items: center;
        }
        .feature-list li:last-child {
            border-bottom: none;
        }
        .feature-list li i {
            color: #28a745;
            margin-right: 0.75rem;
        }
        .current-plan {
            background: linear-gradient(135deg, #000033 0%, #0099FF 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
        }
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .status-actif { background: #d1edff; color: #0c5460; }
        .status-expire { background: #f8d7da; color: #721c24; }
        .status-en_attente { background: #fff3cd; color: #856404; }
    </style>
</head>
<body>
    <?php renderSidebar($_SESSION['user_type'], 'abonnement.php', $user); ?>
    
    <div class="main-content">
        <div class="container-fluid p-4">
            <?php if ($flashMessage = getFlashMessage()): ?>
                <div class="alert alert-<?= $flashMessage['type'] === 'error' ? 'danger' : $flashMessage['type'] ?> alert-dismissible fade show">
                    <?= htmlspecialchars($flashMessage['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- En-tête -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">💳 Mon Abonnement</h1>
                    <p class="text-muted mb-0">Gérez votre abonnement et débloquez toutes les fonctionnalités</p>
                </div>
            </div>

            <!-- Abonnement actuel -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="current-plan">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h3 class="mb-2">🎆 Abonnement Actuel</h3>
                                <?php if ($abonnement_actuel): ?>
                                    <h4 class="mb-1"><?= htmlspecialchars($abonnement_actuel['nom_plan'] ?? 'Plan Premium') ?></h4>
                                    <p class="mb-2 opacity-75">
                                        Actif jusqu'au <?= date('d/m/Y', strtotime($abonnement_actuel['date_fin'])) ?>
                                    </p>
                                    <?php 
                                    $jours_restants = ceil((strtotime($abonnement_actuel['date_fin']) - time()) / (60 * 60 * 24));
                                    ?>
                                    <div class="d-flex align-items-center">
                                        <span class="status-badge status-actif me-3">Actif</span>
                                        <span class="opacity-75"><?= $jours_restants ?> jours restants</span>
                                    </div>
                                <?php else: ?>
                                    <h4 class="mb-1">Plan Gratuit</h4>
                                    <p class="mb-2 opacity-75">Accès limité aux fonctionnalités de base</p>
                                    <span class="status-badge status-expire">Aucun abonnement actif</span>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="text-center">
                                    <i class="bi bi-shield-check" style="font-size: 4rem; opacity: 0.7;"></i>
                                    <?php if (!$abonnement_actuel): ?>
                                        <div class="mt-2">
                                            <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#upgradeModal">
                                                <i class="bi bi-arrow-up-circle me-2"></i>Passer au Premium
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Demandes en cours -->
            <?php if (!empty($demandes_en_cours)): ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">🕰️ Demandes en cours</h5>
                            </div>
                            <div class="card-body">
                                <?php foreach ($demandes_en_cours as $demande): ?>
                                    <div class="d-flex justify-content-between align-items-center p-3 bg-light rounded mb-2">
                                        <div>
                                            <h6 class="mb-1">Demande d'abonnement - Plan <?= $demande['plan_id'] ?></h6>
                                            <small class="text-muted">
                                                Soumise le <?= date('d/m/Y H:i', strtotime($demande['date_demande'])) ?>
                                            </small>
                                        </div>
                                        <span class="status-badge status-en_attente">En attente</span>
                                    </div>
                                <?php endforeach; ?>
                                <div class="alert alert-info mt-3">
                                    <i class="bi bi-info-circle me-2"></i>
                                    Vos demandes sont en cours de traitement. Vous recevrez une confirmation par email.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Plans disponibles -->
            <div class="row mb-4">
                <div class="col-12">
                    <h3 class="mb-4 text-center">🎆 Choisissez votre plan</h3>
                </div>
                <?php foreach ($plans as $plan): ?>
                    <div class="col-lg-4 mb-4">
                        <div class="subscription-card h-100">
                            <?php if (isset($plan['populaire'])): ?>
                                <div class="popular-badge">POPULAIRE</div>
                            <?php endif; ?>
                            
                            <div class="plan-header bg-<?= $plan['couleur'] ?>">
                                <h4 class="text-white mb-3"><?= $plan['nom'] ?></h4>
                                <div class="price-display" id="price-<?= $plan['id'] ?>">
                                    <?= number_format($plan['prix_mensuel'], 2) ?>€
                                </div>
                                <div class="price-period text-white" id="period-<?= $plan['id'] ?>">/mois</div>
                                
                                <div class="mt-3">
                                    <div class="form-check form-switch d-inline-block">
                                        <input class="form-check-input" type="checkbox" id="annual-<?= $plan['id'] ?>" 
                                               onchange="togglePricing(<?= $plan['id'] ?>, <?= $plan['prix_mensuel'] ?>, <?= $plan['prix_annuel'] ?>)">
                                        <label class="form-check-label text-white" for="annual-<?= $plan['id'] ?>">
                                            Facturation annuelle (-17%)
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card-body">
                                <ul class="feature-list">
                                    <?php foreach ($plan['fonctionnalites'] as $fonctionnalite): ?>
                                        <li>
                                            <i class="bi bi-check-circle-fill"></i>
                                            <?= htmlspecialchars($fonctionnalite) ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            
                            <div class="card-footer bg-transparent text-center">
                                <button class="btn btn-<?= $plan['couleur'] ?> w-100" 
                                        onclick="selectPlan(<?= $plan['id'] ?>, '<?= $plan['nom'] ?>')">
                                    <i class="bi bi-credit-card me-2"></i>Choisir ce plan
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Informations de paiement -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">💳 Informations de paiement</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <h6><i class="bi bi-info-circle me-2"></i>Processus de paiement manuel</h6>
                                <p class="mb-2">Pour souscrire à un abonnement, veuillez suivre ces étapes :</p>
                                <ol class="mb-2">
                                    <li>Sélectionnez le plan souhaité</li>
                                    <li>Effectuez le virement bancaire aux coordonnées ci-dessous</li>
                                    <li>Téléchargez la preuve de paiement</li>
                                    <li>Votre abonnement sera activé sous 24h</li>
                                </ol>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Coordonnées bancaires :</h6>
                                    <div class="bg-light p-3 rounded">
                                        <strong>LULU-OPEN SARL</strong><br>
                                        IBAN : FR76 1234 5678 9012 3456 7890 123<br>
                                        BIC : ABCDEFGH<br>
                                        Banque : Crédit Mutuel<br>
                                        <small class="text-muted">Mentionnez votre ID utilisateur : <?= $_SESSION['user_id'] ?></small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6>Moyens de paiement acceptés :</h6>
                                    <div class="d-flex flex-wrap gap-2">
                                        <span class="badge bg-primary">Virement bancaire</span>
                                        <span class="badge bg-success">Chèque</span>
                                        <span class="badge bg-info">PayPal</span>
                                        <span class="badge bg-warning">Crypto</span>
                                    </div>
                                    <small class="text-muted d-block mt-2">
                                        Contactez-nous pour les autres moyens de paiement
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de sélection de plan -->
    <div class="modal fade" id="upgradeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Souscrire à un abonnement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="request_subscription">
                        <input type="hidden" name="plan_id" id="selected_plan_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Plan sélectionné</label>
                            <input type="text" class="form-control" id="selected_plan_name" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Durée</label>
                            <select class="form-select" name="duree" id="duree_select" onchange="updatePrice()" required>
                                <option value="1">1 mois</option>
                                <option value="12">12 mois (-17%)</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Montant à payer</label>
                            <input type="text" class="form-control" name="montant" id="montant_display" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Preuve de paiement *</label>
                            <input type="file" class="form-control" name="preuve_paiement" 
                                   accept=".pdf,.jpg,.jpeg,.png" required>
                            <div class="form-text">Téléchargez votre reçu de virement, capture d'écran, etc.</div>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Important :</strong> Votre abonnement sera activé après vérification du paiement (sous 24h).
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Soumettre la demande</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const plans = <?= json_encode($plans) ?>;
        let selectedPlan = null;

        function togglePricing(planId, monthlyPrice, annualPrice) {
            const checkbox = document.getElementById(`annual-${planId}`);
            const priceElement = document.getElementById(`price-${planId}`);
            const periodElement = document.getElementById(`period-${planId}`);
            
            if (checkbox.checked) {
                priceElement.textContent = (annualPrice).toFixed(2) + '€';
                periodElement.textContent = '/an';
            } else {
                priceElement.textContent = monthlyPrice.toFixed(2) + '€';
                periodElement.textContent = '/mois';
            }
        }

        function selectPlan(planId, planName) {
            selectedPlan = plans.find(p => p.id === planId);
            document.getElementById('selected_plan_id').value = planId;
            document.getElementById('selected_plan_name').value = planName;
            updatePrice();
            
            const modal = new bootstrap.Modal(document.getElementById('upgradeModal'));
            modal.show();
        }

        function updatePrice() {
            if (!selectedPlan) return;
            
            const duree = document.getElementById('duree_select').value;
            const montant = duree === '12' ? selectedPlan.prix_annuel : selectedPlan.prix_mensuel;
            
            document.getElementById('montant_display').value = montant.toFixed(2) + ' €';
            document.querySelector('input[name="montant"]').value = montant;
        }
    </script>
</body>
</html>