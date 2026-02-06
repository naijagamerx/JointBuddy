<?php

class SeoAudit
{
    public function auditMetaDescriptions($basePath)
    {
        $results = [];
        $dir = new RecursiveDirectoryIterator($basePath, FilesystemIterator::SKIP_DOTS);
        $it = new RecursiveIteratorIterator($dir);
        foreach ($it as $file) {
            if (substr($file->getFilename(), -4) === '.php') {
                $content = @file_get_contents($file->getPathname());
                $hasMeta = strpos($content, 'meta name="description"') !== false;
                if (!$hasMeta) {
                    $results[] = str_replace($basePath, '', $file->getPathname());
                }
            }
        }
        return $results;
    }
}

