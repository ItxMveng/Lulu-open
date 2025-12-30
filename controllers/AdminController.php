<?php
/**
 * Controller Admin Principal
 */
require_once __DIR__ . '/../models/Admin.php';
require_once __DIR__ . '/../models/Plan.php';

class AdminController {
    private $adminModel;
    private $planModel;
    
    public function __construct() {
        $this->adminModel = new Admin();
        $this->planModel = new Plan();
    }
    
    public function getStats() {
        return $this->adminModel->getStats();
    }
    
    public function logAction($action, $cible_type = null, $cible_id = null, $details = []) {
        if (isset($_SESSION['user_id'])) {
            return $this->adminModel->logAction($_SESSION['user_id'], $action, $cible_type, $cible_id, $details);
        }
        return false;
    }
    
    public function getLogs($limit = 50) {
        return $this->adminModel->getLogs($limit);
    }
}
?>
