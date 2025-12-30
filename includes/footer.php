<footer class="footer mt-5" style="background: linear-gradient(135deg, #000033 0%, #001a4d 100%); color: white;">
    <!-- Section principale -->
    <div class="container py-5">
        <div class="row g-4">
            <!-- Colonne 1 : À propos -->
            <div class="col-lg-3 col-md-6">
                <h5 class="fw-bold mb-3" style="color: #0099FF;">
                    <i class="bi bi-briefcase-fill me-2"></i>LULU-OPEN
                </h5>
                <p class="text-white-50 small">
                    La marketplace qui connecte les professionnels et les talents. Trouvez le prestataire ou le candidat idéal en quelques clics.
                </p>
                <div class="social-icons mt-3">
                    <a href="https://facebook.com/lulu-open" target="_blank" rel="noopener noreferrer" 
                       class="btn btn-sm btn-outline-light rounded-circle me-2" style="width: 40px; height: 40px;">
                        <i class="bi bi-facebook"></i>
                    </a>
                    <a href="https://twitter.com/lulu-open" target="_blank" rel="noopener noreferrer" 
                       class="btn btn-sm btn-outline-light rounded-circle me-2" style="width: 40px; height: 40px;">
                        <i class="bi bi-twitter"></i>
                    </a>
                    <a href="https://linkedin.com/company/lulu-open" target="_blank" rel="noopener noreferrer" 
                       class="btn btn-sm btn-outline-light rounded-circle me-2" style="width: 40px; height: 40px;">
                        <i class="bi bi-linkedin"></i>
                    </a>
                    <a href="https://instagram.com/lulu-open" target="_blank" rel="noopener noreferrer" 
                       class="btn btn-sm btn-outline-light rounded-circle" style="width: 40px; height: 40px;">
                        <i class="bi bi-instagram"></i>
                    </a>
                </div>
            </div>
            
            <!-- Colonne 2 : Navigation -->
            <div class="col-lg-3 col-md-6">
                <h6 class="fw-bold mb-3" style="color: #0099FF;">Navigation</h6>
                <ul class="list-unstyled footer-links">
                    <li><a href="<?= url('') ?>"><i class="bi bi-chevron-right me-2"></i>Accueil</a></li>
                    <li><a href="<?= url('services.php') ?>"><i class="bi bi-chevron-right me-2"></i>Services</a></li>
                    <li><a href="<?= url('emplois.php') ?>"><i class="bi bi-chevron-right me-2"></i>Emplois</a></li>
                    <li><a href="<?= url('about.php') ?>"><i class="bi bi-chevron-right me-2"></i>À propos</a></li>
                    <li><a href="<?= url('contact.php') ?>"><i class="bi bi-chevron-right me-2"></i>Contact</a></li>
                </ul>
            </div>

            <!-- Colonne 3 : Légal -->
            <div class="col-lg-3 col-md-6">
                <h6 class="fw-bold mb-3" style="color: #0099FF;">Informations légales</h6>
                <ul class="list-unstyled footer-links">
                    <li><a href="<?= url('cgu.php') ?>"><i class="bi bi-chevron-right me-2"></i>CGU</a></li>
                    <li><a href="<?= url('privacy.php') ?>"><i class="bi bi-chevron-right me-2"></i>Confidentialité</a></li>
                    <li><a href="<?= url('legal.php') ?>"><i class="bi bi-chevron-right me-2"></i>Mentions légales</a></li>
                </ul>
            </div>

            <!-- Colonne 4 : Contact -->
            <div class="col-lg-3 col-md-6">
                <h6 class="fw-bold mb-3" style="color: #0099FF;">Contactez-nous</h6>
                <ul class="list-unstyled text-white-50 small">
                    <li class="mb-2">
                        <i class="bi bi-envelope me-2"></i>
                        <a href="mailto:contact@lulu-open.com" class="text-white-50 text-decoration-none">
                            contact@lulu-open.com
                        </a>
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-telephone me-2"></i>
                        <span>+33 1 23 45 67 89</span>
                    </li>
                    <li class="mb-2">
                        <i class="bi bi-clock me-2"></i>
                        <span>Lun-Ven : 9h-18h</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Barre copyright -->
    <div class="border-top border-secondary py-3" style="background: rgba(0, 0, 0, 0.2);">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start">
                    <small class="text-white-50">
                        © <?= date('Y') ?> Lulu-Open. Tous droits réservés.
                    </small>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <small class="text-white-50">
                        Fait avec <i class="bi bi-heart-fill text-danger"></i> pour connecter les talents
                    </small>
                </div>
            </div>
        </div>
    </div>
</footer>

<style>
.footer-links li {
    margin-bottom: 0.5rem;
}

.footer-links a {
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    display: inline-block;
}

.footer-links a:hover {
    color: #0099FF;
    transform: translateX(5px);
}

.social-icons .btn:hover {
    background: #0099FF;
    border-color: #0099FF;
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0, 153, 255, 0.4);
}
</style>
