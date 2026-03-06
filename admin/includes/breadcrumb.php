<?php
/**
 * Admin breadcrumb helper.
 * Usage: admin_breadcrumb([ ['label' => 'Torneos', 'url' => 'torneos.php'], ['label' => 'Juegos'] ]);
 * Last item without 'url' is rendered as current page (no link).
 */
function admin_breadcrumb(array $items = []): string {
    $html = '<nav class="admin-breadcrumb" aria-label="Breadcrumb">';
    if (empty($items)) {
        $html .= '<span>Dashboard</span>';
    } else {
        $html .= '<a href="dashboard.php">Dashboard</a>';
        foreach ($items as $item) {
        $label = $item['label'] ?? '';
        $html .= '<span class="separator" aria-hidden="true">/</span>';
        if (!empty($item['url'])) {
            $html .= '<a href="' . htmlspecialchars($item['url']) . '">' . htmlspecialchars($label) . '</a>';
        } else {
            $html .= '<span>' . htmlspecialchars($label) . '</span>';
        }
    }
    }
    $html .= '</nav>';
    return $html;
}
