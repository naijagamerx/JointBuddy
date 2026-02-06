<?php

class SitemapService
{
    public static function generate($baseUrl, $db)
    {
        $urls = [];
        $urls[] = rtrim($baseUrl, '/') . '/';
        $urls[] = rtrim($baseUrl, '/') . '/shop';
        try {
            $stmt = $db->query('SELECT slug FROM products WHERE status = "active"');
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($products as $p) {
                $urls[] = rtrim($baseUrl, '/') . '/product/' . $p['slug'];
            }
        } catch (Throwable $e) {}
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($urls as $u) {
            $xml .= '<url><loc>' . htmlspecialchars($u, ENT_XML1) . '</loc></url>';
        }
        $xml .= '</urlset>';
        file_put_contents(__DIR__ . '/../../sitemap.xml', $xml);
        return count($urls);
    }
}

