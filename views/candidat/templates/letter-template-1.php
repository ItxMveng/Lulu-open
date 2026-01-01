<?php
function renderLetterTemplate1($data) {
    $content = $data['content'] ?? 'Contenu de la lettre non disponible';
    $user = $data['user'] ?? [];
    $job = $data['job'] ?? [];
    
    $nom = ($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? '');
    $poste = $job['poste'] ?? 'le poste';
    $entreprise = $job['entreprise'] ?? 'votre entreprise';
    
    return "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
        <div style='text-align: right; margin-bottom: 30px;'>
            <strong>$nom</strong><br>
            " . ($user['email'] ?? '') . "<br>
            " . ($user['telephone'] ?? '') . "
        </div>
        
        <div style='margin-bottom: 30px;'>
            <strong>$entreprise</strong><br>
            Service Ressources Humaines
        </div>
        
        <div style='margin-bottom: 20px;'>
            <strong>Objet :</strong> Candidature pour le poste de $poste
        </div>
        
        <div style='line-height: 1.6; text-align: justify;'>
            $content
        </div>
        
        <div style='margin-top: 30px;'>
            Cordialement,<br><br>
            <strong>$nom</strong>
        </div>
    </div>";
}
?>