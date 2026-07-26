<h2 class="h5">Mode du profil</h2>
<p class="text-secondary small">Une entreprise peut proposer des services, recruter, ou faire les deux.</p>
<div class="d-flex flex-column gap-2">
    <div class="form-check">
        <input class="form-check-input" type="radio" disabled <?= ($profile['type'] ?? 'mixte') === 'services' ? 'checked' : '' ?>>
        <label class="form-check-label">Services</label>
    </div>
    <div class="form-check">
        <input class="form-check-input" type="radio" disabled <?= ($profile['type'] ?? 'mixte') === 'recrutement' ? 'checked' : '' ?>>
        <label class="form-check-label">Recrutement</label>
    </div>
    <div class="form-check">
        <input class="form-check-input" type="radio" disabled <?= ($profile['type'] ?? 'mixte') === 'mixte' ? 'checked' : '' ?>>
        <label class="form-check-label">Mixte</label>
    </div>
</div>
<div class="mt-3">
    <label class="form-label" for="type">Changer le mode</label>
    <select class="form-select" id="type" name="type" form="entreprise-profile-form">
        <option value="services" <?= ($profile['type'] ?? 'mixte') === 'services' ? 'selected' : '' ?>>Services</option>
        <option value="recrutement" <?= ($profile['type'] ?? 'mixte') === 'recrutement' ? 'selected' : '' ?>>Recrutement</option>
        <option value="mixte" <?= ($profile['type'] ?? 'mixte') === 'mixte' ? 'selected' : '' ?>>Mixte</option>
    </select>
</div>