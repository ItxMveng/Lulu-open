<?php
require_once __DIR__ . '/../../config/config.php';
$page_title = "Contact - Lulu-Open";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?php include __DIR__ . '/../layouts/header.php'; ?>
    <style>
        .contact-hero {
            background: linear-gradient(135deg, #000033 0%, #0099FF 100%);
            color: white;
            padding: 80px 0 60px;
            margin-bottom: 60px;
        }
        .contact-card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .contact-info-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
        }
        .contact-icon {
            width: 50px;
            height: 50px;
            background: #0099FF;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            margin-bottom: 15px;
        }
        .social-icon {
            width: 45px;
            height: 45px;
            background: #000033;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            margin: 0 5px;
            transition: all 0.3s;
            text-decoration: none;
        }
        .social-icon:hover {
            background: #0099FF;
            color: white;
            transform: translateY(-3px);
        }
        .form-control:focus, .form-select:focus {
            border-color: #0099FF;
            box-shadow: 0 0 0 0.2rem rgba(0,153,255,0.25);
        }
        .btn-submit {
            background: linear-gradient(135deg, #0099FF, #00ccff);
            color: white;
            border: none;
            padding: 12px 40px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-submit:hover {
            background: linear-gradient(135deg, #000033, #001a4d);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,153,255,0.3);
        }
        .btn-submit:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../includes/navbar.php'; ?>

    <div class="container mt-3">
        <nav aria-label="breadcrumb" class="breadcrumb-custom">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= url('') ?>">Accueil</a></li>
                <li class="breadcrumb-item active">Contact</li>
            </ol>
        </nav>
    </div>

    <section class="contact-hero">
        <div class="container text-center">
            <h1 class="display-4 fw-bold mb-3" data-aos="fade-up">Contactez-nous</h1>
            <p class="lead" data-aos="fade-up" data-aos-delay="100">Une question ? Une suggestion ? Nous sommes là pour vous aider</p>
        </div>
    </section>

    <div class="container mb-5">
        <div class="row g-4">
            <div class="col-lg-8" data-aos="fade-right">
                <div class="contact-card">
                    <h3 class="fw-bold mb-4" style="color: #000033;">Envoyez-nous un message</h3>
                    
                    <?php if (isset($_SESSION['flash_success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($_SESSION['flash_success']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['flash_success']); ?>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['flash_error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($_SESSION['flash_error']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['flash_error']); ?>
                    <?php endif; ?>
                    
                    <form method="POST" action="<?= url('contact.php') ?>" id="contactForm">
                        <?php echo csrf_field(); ?>
                        
                        <div class="mb-3">
                            <label for="nom" class="form-label fw-semibold">Nom complet <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nom" name="nom" required minlength="3" 
                                   value="<?= isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : '' ?>">
                            <div class="invalid-feedback">Veuillez entrer votre nom (minimum 3 caractères).</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" required 
                                   value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                            <div class="invalid-feedback">Veuillez entrer une adresse email valide.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="sujet" class="form-label fw-semibold">Sujet <span class="text-danger">*</span></label>
                            <select class="form-select" id="sujet" name="sujet" required>
                                <option value="">Choisissez un sujet...</option>
                                <option value="question_generale">Question générale</option>
                                <option value="support_technique">Support technique</option>
                                <option value="partenariat">Partenariat</option>
                                <option value="autre">Autre</option>
                            </select>
                            <div class="invalid-feedback">Veuillez sélectionner un sujet.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="message" class="form-label fw-semibold">Message <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="message" name="message" rows="6" required minlength="20"><?= isset($_POST['message']) ? htmlspecialchars($_POST['message']) : '' ?></textarea>
                            <div class="invalid-feedback">Veuillez entrer un message (minimum 20 caractères).</div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="rgpd" name="rgpd" required>
                                <label class="form-check-label" for="rgpd">
                                    J'accepte que mes données soient utilisées pour me recontacter <span class="text-danger">*</span>
                                </label>
                                <div class="invalid-feedback">Vous devez accepter cette condition.</div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-submit" id="submitBtn">
                            <i class="bi bi-send me-2"></i>Envoyer le message
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="col-lg-4" data-aos="fade-left">
                <div class="contact-info-card">
                    <div class="contact-icon">
                        <i class="bi bi-envelope"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Email</h5>
                    <p class="mb-0"><a href="mailto:contact@lulu-open.com" style="color: #0099FF; text-decoration: none;">contact@lulu-open.com</a></p>
                </div>
                
                <div class="contact-info-card">
                    <div class="contact-icon">
                        <i class="bi bi-telephone"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Téléphone</h5>
                    <p class="mb-0">+33 1 23 45 67 89</p>
                </div>
                
                <div class="contact-info-card">
                    <div class="contact-icon">
                        <i class="bi bi-clock"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Horaires</h5>
                    <p class="mb-0">Lundi - Vendredi<br>9h00 - 18h00</p>
                </div>
                
                <div class="contact-info-card">
                    <h5 class="fw-bold mb-3">Suivez-nous</h5>
                    <div class="text-center">
                        <a href="https://facebook.com/lulu-open" target="_blank" rel="noopener noreferrer" class="social-icon">
                            <i class="bi bi-facebook"></i>
                        </a>
                        <a href="https://twitter.com/lulu-open" target="_blank" rel="noopener noreferrer" class="social-icon">
                            <i class="bi bi-twitter"></i>
                        </a>
                        <a href="https://linkedin.com/company/lulu-open" target="_blank" rel="noopener noreferrer" class="social-icon">
                            <i class="bi bi-linkedin"></i>
                        </a>
                        <a href="https://instagram.com/lulu-open" target="_blank" rel="noopener noreferrer" class="social-icon">
                            <i class="bi bi-instagram"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../../includes/footer.php'; ?>
    
    <?php include __DIR__ . '/../layouts/scripts.php'; ?>
    
    <script>
    (function() {
        'use strict';
        const form = document.getElementById('contactForm');
        const submitBtn = document.getElementById('submitBtn');
        
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            } else {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Envoi en cours...';
            }
            form.classList.add('was-validated');
        }, false);
    })();
    </script>
</body>
</html>
