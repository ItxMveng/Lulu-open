-- Visuel optionnel d'une prestation. L'image est fortement optimisée côté
-- serveur (redimensionnée + recompressée) et stockée sur le stockage objet R2,
-- pas sur le disque du serveur — l'empreinte reste minime (~50–100 Ko/image).
ALTER TABLE services ADD COLUMN image_path VARCHAR(255) NULL AFTER description;
