<?php
// src/Service/WebPageCacheService.php

namespace Nevinny\AdminCoreBundle\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

class WebPageCacheService
{
    private string $cacheDir;
    private Filesystem $filesystem;

    public function __construct(KernelInterface $kernel)
    {
        $this->cacheDir = $kernel->getProjectDir() . '/var/cache/websites/';
        $this->filesystem = new Filesystem();
    }

    public function getCachedContent(string $url, int $ttl = 3600): ?string
    {
        $cacheFile = $this->getCacheFilePath($url);

        if ($this->filesystem->exists($cacheFile)) {
            if (filemtime($cacheFile) + $ttl > time()) {
                $content = file_get_contents($cacheFile);
                if ($content !== false) {
                    return $content;
                }
            } else {
                // Удаляем просроченный кеш
                $this->filesystem->remove($cacheFile);
            }
        }

        return null;
    }

    public function saveToCache(string $url, string $content): void
    {
        $cacheFile = $this->getCacheFilePath($url);
        $cacheDir = dirname($cacheFile);

        // Создаем директорию если не существует
        if (!$this->filesystem->exists($cacheDir)) {
            $this->filesystem->mkdir($cacheDir, 0755);
        }

        file_put_contents($cacheFile, $content);

        // Логируем создание кеша
        error_log(sprintf('Cached page: %s -> %s', $url, $cacheFile));
    }

    public function getCacheFilePath(string $url): string
    {
        $parsedUrl = parse_url($url);
        $domain = $parsedUrl['host'] ?? 'unknown';
        $path = $parsedUrl['path'] ?? '/';

        // Заменяем недопустимые символы в пути
        $path = preg_replace('/[^a-zA-Z0-9_\-\/]/', '_', $path);
        if (empty($path) || $path === '/') {
            $path = 'index';
        }

        // Добавляем query string если есть
        if (isset($parsedUrl['query'])) {
            $queryHash = substr(md5($parsedUrl['query']), 0, 8);
            $path .= '_' . $queryHash;
        }

        $filename = $domain . $path . '.html';
        return $this->cacheDir . $domain . '/' . ltrim($filename, '/');
    }

    public function clearCache(string $domain = null): void
    {
        if ($domain) {
            $cachePath = $this->cacheDir . $domain;
        } else {
            $cachePath = $this->cacheDir;
        }

        if ($this->filesystem->exists($cachePath)) {
            $this->filesystem->remove($cachePath);
        }
    }

    public function getCacheStats(string $domain = null): array
    {
        $searchPath = $domain ? $this->cacheDir . $domain : $this->cacheDir;

        if (!$this->filesystem->exists($searchPath)) {
            return ['total_files' => 0, 'total_size' => 0, 'domains' => []];
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($searchPath, \FilesystemIterator::SKIP_DOTS)
        );

        $stats = [
            'total_files' => 0,
            'total_size' => 0,
            'domains' => []
        ];

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'html') {
                $stats['total_files']++;
                $stats['total_size'] += $file->getSize();

                $domain = $file->getPathInfo()->getFilename();
                if (!isset($stats['domains'][$domain])) {
                    $stats['domains'][$domain] = ['files' => 0, 'size' => 0];
                }
                $stats['domains'][$domain]['files']++;
                $stats['domains'][$domain]['size'] += $file->getSize();
            }
        }

        $stats['total_size_mb'] = round($stats['total_size'] / 1024 / 1024, 2);
        foreach ($stats['domains'] as &$domainStats) {
            $domainStats['size_mb'] = round($domainStats['size'] / 1024 / 1024, 2);
        }

        return $stats;
    }
}
