<?php
/**
 * Multi-sélection sous forme de chips (cases à cocher stylées).
 * @var string $name    Nom du champ (sera name="{$name}[]")
 * @var array  $options Liste des valeurs proposées
 * @var array  $selected Valeurs pré-cochées
 */
$name = $name ?? 'items';
$options = $options ?? [];
$selected = array_map('strval', $selected ?? []);
?>
<div class="chip-group">
    <?php foreach ($options as $i => $option): $option = (string) $option; $id = $name . '_' . $i; ?>
        <div class="chip">
            <input type="checkbox" id="<?= e($id) ?>" name="<?= e($name) ?>[]" value="<?= e($option) ?>" <?= in_array($option, $selected, true) ? 'checked' : '' ?>>
            <label for="<?= e($id) ?>"><?= e($option) ?></label>
        </div>
    <?php endforeach; ?>
</div>
