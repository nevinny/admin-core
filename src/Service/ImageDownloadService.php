<?php
// src/Service/ImageDownloadService.php

namespace Nevinny\AdminCoreBundle\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class ImageDownloadService
{
    private string $uploadDir;
    private Filesystem $filesystem;
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;

    public function __construct(
        KernelInterface     $kernel,
        HttpClientInterface $httpClient,
        LoggerInterface     $logger
    )
    {
        $this->uploadDir = $kernel->getProjectDir() . '/public_html/images/';
        $this->filesystem = new Filesystem();
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    public function downloadAndSaveImage(string $imageUrl, string $subDir = 'product'): ?string
    {
        try {
            // Проверяем URL
            if (empty($imageUrl) || !filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                $this->logger->warning('Invalid image URL', ['url' => $imageUrl]);
                return null;
            }

            // Получаем изображение
            $response = $this->httpClient->request('GET', $imageUrl, [
                'timeout' => 30,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                    'Accept' => 'image/webp,image/apng,image/*,*/*;q=0.8'
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                $this->logger->warning('Failed to download image', [
                    'url' => $imageUrl,
                    'status' => $response->getStatusCode()
                ]);
                return null;
            }

            // Получаем содержимое и информацию о изображении
            $imageContent = $response->getContent();
            $imageSize = strlen($imageContent);

            // Проверяем минимальный размер (чтобы отсеить битые изображения)
            if ($imageSize < 1024) {
                $this->logger->warning('Image too small', ['url' => $imageUrl, 'size' => $imageSize]);
                return null;
            }

            // Определяем расширение файла
            $extension = $this->getImageExtension($imageUrl, $response->getHeaders());
            if (!$extension) {
                $extension = $this->guessExtensionFromContent($imageContent);
            }

            if (!$extension) {
                $this->logger->warning('Cannot determine image extension', ['url' => $imageUrl]);
                return null;
            }

            // Генерируем уникальное имя файла
            $filename = $this->generateFilename($extension);
            $filePath = $subDir . '/' . $filename;
            $fullPath = $this->uploadDir . $filePath;

            // Создаем директорию если не существует
            $dirPath = dirname($fullPath);
            if (!$this->filesystem->exists($dirPath)) {
                $this->filesystem->mkdir($dirPath, 0755);
            }

            // Сохраняем файл
            file_put_contents($fullPath, $imageContent);

            // Проверяем что файл валидный изображение
            if (!$this->isValidImage($fullPath)) {
                $this->filesystem->remove($fullPath);
                $this->logger->warning('Invalid image file', ['url' => $imageUrl, 'path' => $fullPath]);
                return null;
            }

            $this->logger->info('Image downloaded successfully', [
                'url' => $imageUrl,
                'path' => $filePath,
                'size' => $imageSize
            ]);

            return $filename;

        } catch (\Exception $e) {
            $this->logger->error('Error downloading image', [
                'url' => $imageUrl,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    private function getImageExtension(string $url, array $headers): ?string
    {
        // Пытаемся получить расширение из URL
        $path = parse_url($url, PHP_URL_PATH);
        if ($path) {
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            if ($extension && in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                return $extension;
            }
        }

        // Пытаемся получить из Content-Type
        $contentType = $headers['content-type'][0] ?? '';
        $mimeToExtension = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];

        return $mimeToExtension[$contentType] ?? null;
    }

    private function guessExtensionFromContent(string $content): ?string
    {
        // Определяем тип изображения по сигнатурам
        $signatures = [
            "\xFF\xD8\xFF" => 'jpg',
            "\x89\x50\x4E\x47" => 'png',
            "GIF8" => 'gif',
            "RIFF" => 'webp',
        ];

        foreach ($signatures as $signature => $extension) {
            if (strpos($content, $signature) === 0) {
                return $extension;
            }
        }

        return null;
    }

    private function generateFilename(string $extension): string
    {
        // Генерируем уникальное имя файла в формате совместимом с VichUploader
        $timestamp = round(microtime(true) * 1000);
        $random = bin2hex(random_bytes(8));
        return sprintf('%d_%s.%s', $timestamp, $random, $extension);
    }

    private function isValidImage(string $filePath): bool
    {
        try {
            $imageInfo = @getimagesize($filePath);
            return $imageInfo !== false && in_array($imageInfo[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP]);
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getUploadDir(): string
    {
        return $this->uploadDir;
    }

    /**
     * Удаляет старые файлы из указанной поддиректории
     */
    public function cleanupOldFiles(string $subDir, int $maxAge = 86400): void
    {
        $dirPath = $this->uploadDir . $subDir;

        if (!$this->filesystem->exists($dirPath)) {
            return;
        }

        $iterator = new \DirectoryIterator($dirPath);
        $now = time();

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() !== 'gitkeep') {
                if ($now - $file->getCTime() > $maxAge) {
                    $this->filesystem->remove($file->getPathname());
                }
            }
        }
    }
}
