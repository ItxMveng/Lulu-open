@echo off
echo Configuration du script automatique de gestion des abonnements
echo ==============================================================

REM Créer le dossier logs s'il n'existe pas
if not exist "logs" mkdir logs

REM Créer une tâche planifiée Windows pour exécuter le script quotidiennement à 2h du matin
schtasks /create /tn "LULU-OPEN Subscription Management" /tr "php c:\wamp64\www\lulu\scripts\cron-subscriptions.php" /sc daily /st 02:00 /f

if %errorlevel% == 0 (
    echo ✅ Tâche planifiée créée avec succès
    echo Le script s'exécutera automatiquement tous les jours à 2h du matin
) else (
    echo ❌ Erreur lors de la création de la tâche planifiée
    echo Vous devrez exécuter manuellement le script ou configurer la tâche via l'interface Windows
)

echo.
echo Pour vérifier la tâche : Gestionnaire des tâches > Bibliothèque du Planificateur de tâches
echo Pour exécuter manuellement : php c:\wamp64\www\lulu\scripts\cron-subscriptions.php
echo.
pause