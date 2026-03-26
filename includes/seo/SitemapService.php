<?php

class SitemapService
{
    public static function generate($baseUrl, $db)
    {
        $urls = [];
        $urls[] = rtrim($baseUrl, '/') . '/';
        $urls[] = rtrim($baseUrl, '/') . '/shop';
        
        // Static Pages
        $staticPages = [
            'about', 'contact', 'privacy', 'terms', 'refund-policy', 
            'shipping', 'returns', 'compliance', 'help', 'sell'
        ];
        
        foreach ($staticPages as $page) {
            $urls[] = rtrim($baseUrl, '/') . '/' . $page;
        }

        try {
            $stmt = $db->query('SELECT slug FROM products WHERE status = "active"');
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($products as $p) {
                $urls[] = rtrim($baseUrl, '/') . '/product/' . $p['slug'];
            }
        } catch (Throwable $e) {}
        
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . htmlspecialchars($u, ENT_XML1) . '</loc>' . "\n";
            $xml .= '    <changefreq>weekly</changefreq>' . "\n";
            $xml .= '    <priority>' . (strpos($u, '/product/') !== false ? '0.8' : ($u === rtrim($baseUrl, '/') . '/' ? '1.0' : '0.6')) . '</priority>' . "\n";
            $xml .= '  </url>' . "\n";
        }
        $xml .= '</urlset>';
        file_put_contents(__DIR__ . '/../../sitemap.xml', $xml);
        return count($urls);
    }
}

