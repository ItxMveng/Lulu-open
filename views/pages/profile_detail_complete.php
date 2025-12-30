<div class="container-fluid py-4">
    <!-- Header du profil -->
    <div class="profile-header mb-5">
        <div class="row align-items-center">
            <div class="col-lg-3 text-center">
                <div class="profile-avatar-large mb-3">
                    <?php if ($profile['photo_profil']): ?>
                        <img src="/uploads/profiles/<?= $profile['photo_profil'] ?>" 
                             alt="Photo de <?= htmlspecialchars($profile['prenom']) ?>" 
                             class="rounded-circle img-fluid">
                    <?php else: ?>
                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white mx-auto">
                            <?= strtoupper(substr($profile['prenom'], 0, 1) . substr($profile['nom'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Badge de vérification -->
                <?php if ($profile['abonnement_actif']): ?>
                    <div class="verification-badge mb-3">
                        <i class="bi bi-patch-check-fill text-success"></i>
                        <span class="text-success fw-bold">Profil vérifié</span>
                    </div>
                <?php endif; ?>
                
                <!-- Actions -->
                <?php if (isLoggedIn() && $_SESSION['user_id'] != $profile['id']): ?>
                    <div class="profile-actions d-grid gap-2">
                        <button class="btn btn-primary" onclick="openContactModal()">
                            <i class="bi bi-chat-dots"></i> Contacter
                        </button>
                        <button class="btn btn-outline-secondary" onclick="toggleFavorite(<?= $profile['id'] ?>)" id="favoriteBtn">
                            <i class="bi bi-heart"></i> <span id="favoriteText">Ajouter aux favoris</span>
                        </button>
                        <button class="btn btn-outline-info" onclick="shareProfile()">
                            <i class="bi bi-share"></i> Partager
                        </button>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="col-lg-9">
                <div class="profile-info">
                    <h1 class="profile-name"><?= htmlspecialchars($profile['prenom'] . ' ' . $profile['nom']) ?></h1>
                    
                    <?php if ($profileType === 'prestataire'): ?>
                        <h2 class="profile-title text-primary"><?= htmlspecialchars($profile['titre_professionnel']) ?></h2>
                    <?php else: ?>
                        <h2 class="profile-title text-info"><?= htmlspecialchars($profile['titre_poste_recherche']) ?></h2>
                    <?php endif; ?>
                    
                    <div class="profile-meta mb-4">
                        <span class="meta-item">
                            <i class="bi bi-geo-alt text-muted"></i>
                            <?= htmlspecialchars($profile['ville'] . ', ' . ($profile['region'] ?? $profile['pays'])) ?>
                        </span>
                        
                        <span class="meta-item">
                            <i class="bi bi-tag text-muted"></i>
                            <?= htmlspecialchars($profile['categorie_nom']) ?>
                        </span>
                        
                        <span class="meta-item">
                            <i class="bi bi-calendar text-muted"></i>
                            Membre depuis <?= date('M Y', strtotime($profile['date_inscription'])) ?>
                        </span>
                    </div>
                    
                    <!-- Statistiques -->
                    <div class="profile-stats">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="stat-card">
                                    <div class="stat-icon">
                                        <i class="bi bi-star-fill text-warning"></i>
                                    </div>
                                    <div class="stat-content">
                                        <div class="stat-value"><?= number_format($profile['note_moyenne'] ?? 0, 1) ?></div>
                                        <div class="stat-label">Note moyenne</div>
                                        <div class="stat-sublabel"><?= $profile['nombre_avis'] ?? 0 ?> avis</div>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if ($profileType === 'prestataire'): ?>
                                <div class="col-md-3">
                                    <div class="stat-card">
                                        <div class="stat-icon">
                                            <i class="bi bi-currency-euro text-success"></i>
                                        </div>
                                        <div class="stat-content">
                                            <div class="stat-value"><?= formatPrice($profile['tarif_horaire']) ?></div>
                                            <div class="stat-label">Tarif/heure</div>
                                            <?php if ($profile['tarif_forfait']): ?>
                                                <div class="stat-sublabel">Forfait: <?= formatPrice($profile['tarif_forfait']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="stat-card">
                                        <div class="stat-icon">
                                            <i class="bi bi-briefcase text-primary"></i>
                                        </div>
                                        <div class="stat-content">
                                            <div class="stat-value"><?= $profile['experience_annees'] ?? 0 ?></div>
                                            <div class="stat-label">Années d'expérience</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="stat-card">
                                        <div class="stat-icon">
                                            <i class="bi bi-check-circle text-<?= $profile['disponibilite'] === 'disponible' ? 'success' : 'warning' ?>"></i>
                                        </div>
                                        <div class="stat-content">
                                            <div class="stat-value"><?= ucfirst($profile['disponibilite'] ?? 'Inconnu') ?></div>
                                            <div class="stat-label">Disponibilité</div>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="col-md-3">
                                    <div class="stat-card">
                                        <div class="stat-icon">
                                            <i class="bi bi-trophy text-info"></i>
                                        </div>
                                        <div class="stat-content">
                                            <div class="stat-value"><?= ucfirst($profile['niveau_experience']) ?></div>
                                            <div class="stat-label">Niveau</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="stat-card">
                                        <div class="stat-icon">
                                            <i class="bi bi-file-text text-secondary"></i>
                                        </div>
                                        <div class="stat-content">
                                            <div class="stat-value"><?= strtoupper($profile['type_contrat']) ?></div>
                                            <div class="stat-label">Type de contrat</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="stat-card">
                                        <div class="stat-icon">
                                            <i class="bi bi-currency-euro text-success"></i>
                                        </div>
                                        <div class="stat-content">
                                            <div class="stat-value"><?= $profile['salaire_souhaite'] ? formatPrice($profile['salaire_souhaite']) : 'Négociable' ?></div>
                                            <div class="stat-label">Salaire souhaité</div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Contenu principal -->
    <div class="row g-4">
        <!-- Colonne principale -->
        <div class="col-lg-8">
            <!-- Description/Présentation -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-person-lines-fill"></i> 
                        <?= $profileType === 'prestataire' ? 'Présentation des services' : 'Profil professionnel' ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($profileType === 'prestataire'): ?>
                        <p class="lead"><?= nl2br(htmlspecialchars($profile['description_services'])) ?></p>
                        
                        <?php if ($profile['diplomes']): ?>
                            <div class="mt-4">
                                <h6><i class="bi bi-mortarboard"></i> Diplômes et certifications</h6>
                                <p><?= nl2br(htmlspecialchars($profile['diplomes'])) ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($profile['certifications']): ?>
                            <div class="mt-4">
                                <h6><i class="bi bi-award"></i> Certifications</h6>
                                <p><?= nl2br(htmlspecialchars($profile['certifications'])) ?></p>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if ($profile['competences']): ?>
                            <div class="mb-4">
                                <h6><i class="bi bi-gear"></i> Compétences principales</h6>
                                <div class="competences-cloud">
                                    <?php foreach (explode(',', $profile['competences']) as $competence): ?>
                                        <span class="competence-tag"><?= htmlspecialchars(trim($competence)) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($profile['formations']): ?>
                            <div class="mb-4">
                                <h6><i class="bi bi-mortarboard"></i> Formations</h6>
                                <div class="formation-content"><?= nl2br(htmlspecialchars($profile['formations'])) ?></div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($profile['experiences_professionnelles']): ?>
                            <div class="mb-4">
                                <h6><i class="bi bi-briefcase"></i> Expériences professionnelles</h6>
                                <div class="experience-content"><?= nl2br(htmlspecialchars($profile['experiences_professionnelles'])) ?></div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($profile['langues']): ?>
                            <div class="mb-4">
                                <h6><i class="bi bi-translate"></i> Langues</h6>
                                <div class="langues-list">
                                    <?php 
                                    $langues = json_decode($profile['langues'], true) ?: [];
                                    foreach ($langues as $langue): 
                                    ?>
                                        <span class="langue-item">
                                            <strong><?= htmlspecialchars($langue['nom']) ?></strong>
                                            <span class="niveau-badge"><?= ucfirst($langue['niveau']) ?></span>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Portfolio/Réalisations (pour prestataires) -->
            <?php if ($profileType === 'prestataire' && !empty($portfolio)): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-images"></i> Portfolio
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="portfolio-grid">
                            <?php foreach ($portfolio as $item): ?>
                                <div class="portfolio-item" onclick="openPortfolioModal('<?= $item['image'] ?>', '<?= htmlspecialchars($item['description']) ?>')">
                                    <img src="/uploads/portfolios/<?= $item['image'] ?>" alt="Réalisation" class="img-fluid">
                                    <div class="portfolio-overlay">
                                        <i class="bi bi-eye"></i>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- CV prévisualisable (pour candidats) -->
            <?php if ($profileType === 'candidat' && $profile['cv_fichier']): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-file-earmark-pdf"></i> Curriculum Vitae
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="cv-viewer">
                            <h6 class="mb-3">CV de <?= htmlspecialchars($profile['prenom']) ?></h6>

                            <!-- Boutons d'actions -->
                            <div class="d-flex justify-content-center gap-2 mb-3">
                                <button class="btn btn-outline-primary btn-sm" onclick="togglePreview()" id="previewBtn">
                                    <i class="bi bi-eye"></i> Prévisualiser
                                </button>
                                <a href="/uploads/cvs/<?= $profile['cv_fichier'] ?>" class="btn btn-primary btn-sm" download>
                                    <i class="bi bi-download"></i> Télécharger
                                </a>
                            </div>

                            <!-- Conteneur du visualiseur PDF -->
                            <div id="pdfViewer" class="pdf-viewer-container" style="display: none;">
                                <iframe src="/uploads/cvs/<?= $profile['cv_fichier'] ?>"
                                        class="pdf-iframe"
                                        width="100%"
                                        height="600px"
                                        style="border: 1px solid #dee2e6; border-radius: 8px;"></iframe>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Portfolio/Réalisations (pour prestataires) -->
            <?php if ($profileType === 'prestataire' && !empty($portfolio)): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-images"></i> Portfolio
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="portfolio-grid">
                            <?php foreach ($portfolio as $item): ?>
                                <div class="portfolio-item" onclick="openPortfolioModal('<?= $item['image'] ?>', '<?= htmlspecialchars($item['description']) ?>')">
                                    <img src="/uploads/portfolios/<?= $item['image'] ?>" alt="Réalisation" class="img-fluid">
                                    <div class="portfolio-overlay">
                                        <i class="bi bi-eye"></i>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Informations de contact -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-info-circle"></i> Informations
                    </h6>
                </div>
                <div class="card-body">
                    <div class="info-item">
                        <i class="bi bi-geo-alt text-muted"></i>
                        <span><?= htmlspecialchars($profile['ville'] . ', ' . $profile['pays']) ?></span>
                    </div>
                    
                    <?php if ($profileType === 'prestataire' && $profile['rayon_intervention']): ?>
                        <div class="info-item">
                            <i class="bi bi-compass text-muted"></i>
                            <span>Rayon d'intervention: <?= $profile['rayon_intervention'] ?> km</span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($profileType === 'candidat' && $profile['mobilite']): ?>
                        <div class="info-item">
                            <i class="bi bi-airplane text-muted"></i>
                            <span>Ouvert à la mobilité</span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="info-item">
                        <i class="bi bi-calendar text-muted"></i>
                        <span>Membre depuis <?= date('F Y', strtotime($profile['date_inscription'])) ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Avis récents -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0">
                        <i class="bi bi-star"></i> Avis (<?= count($reviews) ?>)
                    </h6>
                    <?php if (isLoggedIn() && $_SESSION['user_id'] != $profile['id']): ?>
                        <button class="btn btn-sm btn-outline-primary" onclick="openReviewModal()">
                            <i class="bi bi-plus"></i> Avis
                        </button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (!empty($reviews)): ?>
                        <?php foreach (array_slice($reviews, 0, 3) as $review): ?>
                            <div class="review-item-compact">
                                <div class="d-flex align-items-start">
                                    <div class="review-avatar me-2">
                                        <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center text-white" 
                                             style="width: 32px; height: 32px; font-size: 0.8rem;">
                                            <?= strtoupper(substr($review['donneur_nom'], 0, 1)) ?>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="fw-bold"><?= htmlspecialchars($review['donneur_nom']) ?></small>
                                            <div class="rating-small">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="bi bi-star<?= $i <= $review['note'] ? '-fill text-warning' : ' text-muted' ?>" style="font-size: 0.7rem;"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        <?php if ($review['commentaire']): ?>
                                            <p class="small text-muted mb-1"><?= htmlspecialchars(substr($review['commentaire'], 0, 80)) ?>...</p>
                                        <?php endif; ?>
                                        <small class="text-muted"><?= formatDate($review['date_avis']) ?></small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if (count($reviews) > 3): ?>
                            <div class="text-center mt-3">
                                <button class="btn btn-sm btn-outline-secondary" onclick="showAllReviews()">
                                    Voir tous les avis (<?= count($reviews) ?>)
                                </button>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-3 text-muted">
                            <i class="bi bi-star" style="font-size: 2rem;"></i>
                            <p class="small mt-2 mb-0">Aucun avis pour le moment</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.profile-avatar-large {
    width: 150px;
    height: 150px;
    margin: 0 auto;
}

.profile-avatar-large img,
.profile-avatar-large > div {
    width: 150px;
    height: 150px;
    font-size: 3rem;
}

.profile-name {
    font-size: 2.5rem;
    font-weight: 700;
    color: #000033;
    margin-bottom: 0.5rem;
}

.profile-title {
    font-size: 1.5rem;
    font-weight: 500;
    margin-bottom: 1rem;
}

.profile-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 1.5rem;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #6c757d;
}

.stat-card {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
    transition: all 0.3s ease;
    height: 100%;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 153, 255, 0.15);
}

.stat-icon {
    font-size: 2rem;
    margin-bottom: 1rem;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #000033;
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.9rem;
    color: #6c757d;
    margin-bottom: 0.25rem;
}

.stat-sublabel {
    font-size: 0.8rem;
    color: #adb5bd;
}

.competences-cloud {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.competence-tag {
    background: linear-gradient(135deg, #0099FF, #00ccff);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-size: 0.85rem;
    font-weight: 500;
    transition: transform 0.3s ease;
}

.competence-tag:hover {
    transform: scale(1.05);
}

.langues-list {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
}

.langue-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.niveau-badge {
    background: #f8f9fa;
    color: #495057;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
}

.portfolio-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
}

.portfolio-item {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    cursor: pointer;
    transition: transform 0.3s ease;
}

.portfolio-item:hover {
    transform: scale(1.05);
}

.portfolio-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
    color: white;
    font-size: 1.5rem;
}

.portfolio-item:hover .portfolio-overlay {
    opacity: 1;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.info-item:last-child {
    border-bottom: none;
}

.review-item-compact {
    padding: 1rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.review-item-compact:last-child {
    border-bottom: none;
}

.verification-badge {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
}

.cv-download {
    padding: 2rem;
}

@media (max-width: 768px) {
    .profile-meta {
        flex-direction: column;
        gap: 0.75rem;
    }
    
    .profile-name {
        font-size: 2rem;
    }
    
    .profile-title {
        font-size: 1.25rem;
    }
}
</style>

<script>
function openContactModal() {
    alert('Fonctionnalité de contact à implémenter');
}

function toggleFavorite(profileId) {
    const btn = document.getElementById('favoriteBtn');
    const text = document.getElementById('favoriteText');
    
    if (text.textContent.includes('Ajouter')) {
        text.textContent = 'Retirer des favoris';
        btn.classList.remove('btn-outline-secondary');
        btn.classList.add('btn-danger');
    } else {
        text.textContent = 'Ajouter aux favoris';
        btn.classList.remove('btn-danger');
        btn.classList.add('btn-outline-secondary');
    }
}

function shareProfile() {
    if (navigator.share) {
        navigator.share({
            title: document.title,
            url: window.location.href
        });
    } else {
        navigator.clipboard.writeText(window.location.href);
        alert('Lien copié dans le presse-papiers !');
    }
}

function openReviewModal() {
    alert('Fonctionnalité d\'avis à implémenter');
}

function showAllReviews() {
    alert('Affichage de tous les avis à implémenter');
}

function openPortfolioModal(image, description) {
    alert('Portfolio: ' + description);
}

function togglePreview() {
    const pdfViewer = document.getElementById('pdfViewer');
    const previewBtn = document.getElementById('previewBtn');

    if (pdfViewer.style.display === 'none') {
        pdfViewer.style.display = 'block';
        previewBtn.innerHTML = '<i class="bi bi-eye-slash"></i> Masquer';
        previewBtn.classList.remove('btn-outline-primary');
        previewBtn.classList.add('btn-secondary');
    } else {
        pdfViewer.style.display = 'none';
        previewBtn.innerHTML = '<i class="bi bi-eye"></i> Prévisualiser';
        previewBtn.classList.remove('btn-secondary');
        previewBtn.classList.add('btn-outline-primary');
    }
}
</script>
