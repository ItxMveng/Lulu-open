<?php
function renderCvTemplate1($data) {
    $cvData = $data['cvData'] ?? [];
    $user = $data['user'] ?? [];
    
    $title = $cvData['title'] ?? 'CV Professionnel';
    $summary = $cvData['summary'] ?? 'Professionnel expérimenté';
    $sections = $cvData['sections'] ?? [];
    
    $html = '<div style="font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px;">';
    $html .= '<h1 style="color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px;">' . htmlspecialchars($title) . '</h1>';
    $html .= '<div style="background: #ecf0f1; padding: 15px; margin: 20px 0; border-radius: 5px;">';
    $html .= '<p style="margin: 0; font-size: 16px; line-height: 1.6;">' . htmlspecialchars($summary) . '</p>';
    $html .= '</div>';
    
    foreach ($sections as $section) {
        $sectionTitle = $section['title'] ?? 'Section';
        $items = $section['items'] ?? [];
        
        $html .= '<h2 style="color: #34495e; margin-top: 30px;">' . htmlspecialchars($sectionTitle) . '</h2>';
        $html .= '<ul style="list-style-type: none; padding: 0;">';
        
        foreach ($items as $item) {
            $html .= '<li style="margin: 10px 0; padding: 10px; background: #f8f9fa; border-left: 4px solid #3498db;">';
            $html .= htmlspecialchars($item);
            $html .= '</li>';
        }
        
        $html .= '</ul>';
    }
    
    $html .= '</div>';
    
    return $html;
}
?>