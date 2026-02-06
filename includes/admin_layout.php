<?php
require_once __DIR__ . '/../admin_sidebar_components.php';

function renderAdminPage($title, $content, $activeTab = '') {
    return adminSidebarWrapper($title, $content, $activeTab);
}
