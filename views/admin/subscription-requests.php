<?php
$pendingCount = count($requests);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demandes d'abonnement - Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1400px; margin: 0 auto; }
        h1 { margin-bottom: 30px; }
        .badge { background: #dc3545; color: white; padding: 5px 15px; border-radius: 20px; font-size: 18px; font-weight: bold; }
        
        .alert { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        table { width: 100%; background: white; border-collapse: collapse; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #007bff; color: white; font-weight: bold; }
        tr:hover { background: #f8f9fa; }
        
        .status-pending { color: #ff9800; font-weight: bold; }
        .btn { padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; margin: 2px; }
        .btn-verify { background: #28a745; color: white; }
        .btn-verify:hover { background: #218838; }
        .btn-reject { background: #dc3545; color: white; }
        .btn-reject:hover { background: #c82333; }
        .btn-view { background: #007bff; color: white; }
        .btn-view:hover { background: #0056b3; }
        
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 1000; }
        .modal-content { background: white; margin: 50px auto; padding: 30px; border-radius: 10px; max-width: 600px; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .close { font-size: 30px; cursor: pointer; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚨 Demandes d'abonnement <span class="badge"><?= $pendingCount ?></span></h1>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error"><?= $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
        <?php endif; ?>

        <?php if (empty($requests)): ?>
            <p>Aucune demande en attente.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Utilisateur</th>
                        <th>Type</th>
                        <th>Durée</th>
                        <th>Montant</th>
                        <th>Méthode</th>
                        <th>Date</th>
                        <th>Preuve</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $req): ?>
                        <tr>
                            <td>#<?= $req['id'] ?></td>
                            <td><?= htmlspecialchars($req['prenom'] . ' ' . $req['nom']) ?><br><small><?= htmlspecialchars($req['email']) ?></small></td>
                            <td><?= $req['type_utilisateur'] ?></td>
                            <td><?= $req['duration_months'] ?> mois</td>
                            <td><?= number_format($req['amount_paid'], 2) ?> <?= $req['currency'] ?></td>
                            <td><?= $req['payment_method'] ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($req['submitted_at'])) ?></td>
                            <td><a href="/lulu/uploads/proofs/<?= $req['proof_document_path'] ?>" target="_blank" class="btn btn-view">Voir</a></td>
                            <td>
                                <button class="btn btn-verify" onclick="verifyRequest(<?= $req['id'] ?>)">✓ Vérifier</button>
                                <button class="btn btn-reject" onclick="rejectRequest(<?= $req['id'] ?>)">✗ Rejeter</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div id="verifyModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Vérifier le paiement</h2>
                <span class="close" onclick="closeModal('verifyModal')">&times;</span>
            </div>
            <form method="POST" action="/lulu/controllers/AdminSubscriptionController.php?action=verifyPayment">
                <input type="hidden" name="request_id" id="verify_request_id">
                <div class="form-group">
                    <label>Notes (optionnel)</label>
                    <textarea name="admin_notes" rows="3" placeholder="Commentaires..."></textarea>
                </div>
                <button type="submit" class="btn btn-verify" style="width:100%; padding:15px;">Activer l'abonnement</button>
            </form>
        </div>
    </div>

    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Rejeter la demande</h2>
                <span class="close" onclick="closeModal('rejectModal')">&times;</span>
            </div>
            <form method="POST" action="/lulu/controllers/AdminSubscriptionController.php?action=rejectPayment">
                <input type="hidden" name="request_id" id="reject_request_id">
                <div class="form-group">
                    <label>Raison du rejet *</label>
                    <textarea name="admin_notes" rows="3" placeholder="Expliquez pourquoi..." required></textarea>
                </div>
                <button type="submit" class="btn btn-reject" style="width:100%; padding:15px;">Confirmer le rejet</button>
            </form>
        </div>
    </div>

    <script>
        function verifyRequest(id) {
            document.getElementById('verify_request_id').value = id;
            document.getElementById('verifyModal').style.display = 'block';
        }

        function rejectRequest(id) {
            document.getElementById('reject_request_id').value = id;
            document.getElementById('rejectModal').style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>
