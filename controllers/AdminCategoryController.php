<?php
require_once 'BaseController.php';
require_once 'models/Category.php';

class AdminCategoryController extends BaseController {
    
    public function __construct() {
        $this->requireRole('admin');
    }
    
    public function index() {
        try {
            $categoryModel = new Category();
            $categories = $categoryModel->getAll();
            
            $data = [
                'title' => 'Gestion des Catégories - Admin',
                'categories' => $categories
            ];
            
            $this->render('admin/categories/index', $data);
            
        } catch (Exception $e) {
            flashMessage('Erreur lors du chargement des catégories: ' . $e->getMessage(), 'error');
            redirect('admin/dashboard.php');
        }
    }
    
    public function create() {
        if ($this->isPost()) {
            try {
                $this->validateCSRF();
                
                $data = $this->getAllInput();
                $categoryModel = new Category();
                
                $categoryId = $categoryModel->create($data);
                
                $this->logActivity('category_create', ['category_id' => $categoryId]);
                flashMessage('Catégorie créée avec succès !', 'success');
                redirect('admin/categories.php');
                
            } catch (Exception $e) {
                flashMessage($e->getMessage(), 'error');
            }
        }
        
        $data = [
            'title' => 'Nouvelle Catégorie - Admin',
            'csrf_token' => $this->generateCSRF(),
            'category' => null
        ];
        
        $this->render('admin/categories/form', $data);
    }
    
    public function edit() {
        $id = $this->getInput('id');
        
        if ($this->isPost()) {
            try {
                $this->validateCSRF();
                
                $data = $this->getAllInput();
                $categoryModel = new Category();
                
                $categoryModel->update($id, $data);
                
                $this->logActivity('category_update', ['category_id' => $id]);
                flashMessage('Catégorie mise à jour avec succès !', 'success');
                redirect('admin/categories.php');
                
            } catch (Exception $e) {
                flashMessage($e->getMessage(), 'error');
            }
        }
        
        try {
            $categoryModel = new Category();
            $category = $categoryModel->getById($id);
            
            if (!$category) {
                throw new Exception('Catégorie non trouvée');
            }
            
            $data = [
                'title' => 'Modifier Catégorie - Admin',
                'csrf_token' => $this->generateCSRF(),
                'category' => $category
            ];
            
            $this->render('admin/categories/form', $data);
            
        } catch (Exception $e) {
            flashMessage($e->getMessage(), 'error');
            redirect('admin/categories.php');
        }
    }
    
    public function delete() {
        if ($this->isPost()) {
            try {
                $this->validateCSRF();
                
                $id = $this->getInput('id');
                $categoryModel = new Category();
                
                $categoryModel->delete($id);
                
                $this->logActivity('category_delete', ['category_id' => $id]);
                flashMessage('Catégorie supprimée avec succès !', 'success');
                
            } catch (Exception $e) {
                flashMessage($e->getMessage(), 'error');
            }
        }
        
        redirect('admin/categories.php');
    }
    
    public function toggle() {
        if ($this->isPost()) {
            try {
                $this->validateCSRF();
                
                $id = $this->getInput('id');
                $categoryModel = new Category();
                
                $categoryModel->toggleStatus($id);
                
                $this->logActivity('category_toggle', ['category_id' => $id]);
                flashMessage('Statut de la catégorie modifié !', 'success');
                
            } catch (Exception $e) {
                flashMessage($e->getMessage(), 'error');
            }
        }
        
        redirect('admin/categories.php');
    }
    
    public function stats() {
        try {
            $id = $this->getInput('id');
            $categoryModel = new Category();
            
            $stats = $categoryModel->getCategoryStats($id);
            
            $this->json($stats);
            
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }
}
?>