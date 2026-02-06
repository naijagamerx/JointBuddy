<?php
// Include bootstrap (loads all core services)
require_once __DIR__ . '/../../includes/bootstrap.php';

// Require authentication (admin only)
AuthMiddleware::requireAdmin();

// Get database connection from services
$db = Services::db();

$message = '';
$urls = [];
$missingMeta = [];

if (isset($_GET['generate']) && $db) {
    try {
        $base = getBaseUrl();
        $count = generateSitemap($base, $db);
        $message = 'Sitemap generated: ' . $count . ' URLs';
        $urls[] = rtrim($base, '/') . '/';
    } catch (Exception $e) {
        $message = AppError::handleDatabaseError($e, 'Error generating sitemap');
    }
}

if (isset($_GET['indexnow']) && !empty($urls)) {
    $message = $message ? $message . ' | IndexNow submission requires API key' : 'IndexNow submission requires API key';
}

/**
 * Generate sitemap.xml file
 */
function generateSitemap($baseUrl, $db) {
    $urls = [];

    // Add homepage
    $urls[] = [
        'loc' => rtrim($baseUrl, '/'),
        'lastmod' => date('Y-m-d'),
        'changefreq' => 'daily',
        'priority' => '1.0'
    ];

    // Get products
    $stmt = $db->query("SELECT slug, updated_at FROM products WHERE active = 1 ORDER BY name ASC");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($products as $product) {
        $urls[] = [
            'loc' => rtrim($baseUrl, '/') . '/product/' . $product['slug'],
            'lastmod' => date('Y-m-d', strtotime($product['updated_at'] ?? 'now')),
            'changefreq' => 'weekly',
            'priority' => '0.8'
        ];
    }

    // Get categories
    $stmt = $db->query("SELECT slug, updated_at FROM categories WHERE is_active = 1 ORDER BY name ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($categories as $category) {
        $urls[] = [
            'loc' => rtrim($baseUrl, '/') . '/category/' . $category['slug'],
            'lastmod' => date('Y-m-d', strtotime($category['updated_at'] ?? 'now')),
            'changefreq' => 'weekly',
            'priority' => '0.7'
        ];
    }

    // Static pages
    $staticPages = [
        ['url' => '/about/', 'priority' => '0.6'],
        ['url' => '/contact/', 'priority' => '0.6'],
        ['url' => '/privacy-policy/', 'priority' => '0.3'],
        ['url' => '/terms-of-service/', 'priority' => '0.3'],
    ];

    foreach ($staticPages as $page) {
        $urls[] = [
            'loc' => rtrim($baseUrl, '/') . $page['url'],
            'lastmod' => date('Y-m-d'),
            'changefreq' => 'monthly',
            'priority' => $page['priority']
        ];
    }

    // Generate XML
    $xml = new SimpleXMLElement('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>');

    foreach ($urls as $urlData) {
        $url = $xml->addChild('url');
        $url->addChild('loc', htmlspecialchars($urlData['loc']));
        $url->addChild('lastmod', $urlData['lastmod']);
        $url->addChild('changefreq', $urlData['changefreq']);
        $url->addChild('priority', $urlData['priority']);
    }

    // Save sitemap
    $sitemapPath = __DIR__ . '/../../sitemap.xml';
    $dom = new DOMDocument('1.0');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadXML($xml->asXML());
    $dom->save($sitemapPath);

    return count($urls);
}

$content = '<div class="max-w-7xl mx-auto">';
if ($message) { $content .= adminAlert('success', $message); }

$content .= '<div class="mb-6"><h2 class="text-2xl font-bold text-gray-900">SEO Tools</h2></div>';
$content .= '<div class="grid grid-cols-1 md:grid-cols-2 gap-6">';
$content .= '<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6"><h3 class="text-lg font-semibold text-gray-900 mb-4">Sitemap</h3><p class="text-gray-600 mb-4">Generate and update sitemap.xml</p><div class="flex space-x-3">' . adminButton('Generate Sitemap', adminUrl('/seo/?generate=1'), 'primary') . adminButton('Submit IndexNow', adminUrl('/seo/?generate=1&indexnow=1'), 'secondary') . '</div></div>';
$content .= '<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6"><h3 class="text-lg font-semibold text-gray-900 mb-4">SEO Audit</h3><p class="text-gray-600 mb-4">Pages missing meta description</p>';
if (!empty($missingMeta)) {
    $rows = [];
    foreach ($missingMeta as $path) { $rows[] = [htmlspecialchars($path)]; }
    $content .= adminTable(['File'], $rows);
} else { $content .= '<p class="text-sm text-green-700">All checked views contain meta description.</p>'; }
$content .= '</div>';

// Close grid container
$content .= '</div>';

echo adminSidebarWrapper('SEO', $content, 'seo');
