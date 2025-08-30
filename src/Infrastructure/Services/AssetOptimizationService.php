<?php

declare(strict_types=1);

namespace BotMirzaPanel\Infrastructure\Services;

use BotMirzaPanel\Shared\Contracts\CacheInterface;
use RuntimeException;

/**
 * Asset Optimization Service
 * 
 * Provides asset optimization features including:
 * - CSS and JavaScript minification
 * - Image compression and optimization
 * - Asset bundling and versioning
 * - CDN integration
 * - Lazy loading implementation
 * - Cache busting strategies
 */
class AssetOptimizationService
{
    private array $assetManifest = [];
    private array $bundleCache = [];
    private string $assetsPath;
    private string $publicPath;
    private bool $enableMinification;
    private bool $enableBundling;
    private bool $enableVersioning;
    private bool $enableCdn;
    
    public function __construct(
        private CacheInterface $cache,
        private array $config = []
    ) {
        $this->assetsPath = $config['assets_path'] ?? 'assets';
        $this->publicPath = $config['public_path'] ?? 'public';
        $this->enableMinification = $config['enable_minification'] ?? true;
        $this->enableBundling = $config['enable_bundling'] ?? true;
        $this->enableVersioning = $config['enable_versioning'] ?? true;
        $this->enableCdn = $config['enable_cdn'] ?? false;
        
        $this->loadAssetManifest();
    }

    /**
     * Optimize CSS file
     */
    public function optimizeCss(string $filePath): string
    {
        $content = $this->readFile($filePath);
        
        if ($this->enableMinification) {
            $content = $this->minifyCss($content);
        }
        
        $optimizedPath = $this->generateOptimizedPath($filePath, 'css');
        $this->writeFile($optimizedPath, $content);
        
        if ($this->enableVersioning) {
            $versionedPath = $this->addVersionToAsset($optimizedPath);
            $this->updateAssetManifest($filePath, $versionedPath);
            return $versionedPath;
        }
        
        return $optimizedPath;
    }

    /**
     * Optimize JavaScript file
     */
    public function optimizeJs(string $filePath): string
    {
        $content = $this->readFile($filePath);
        
        if ($this->enableMinification) {
            $content = $this->minifyJs($content);
        }
        
        $optimizedPath = $this->generateOptimizedPath($filePath, 'js');
        $this->writeFile($optimizedPath, $content);
        
        if ($this->enableVersioning) {
            $versionedPath = $this->addVersionToAsset($optimizedPath);
            $this->updateAssetManifest($filePath, $versionedPath);
            return $versionedPath;
        }
        
        return $optimizedPath;
    }

    /**
     * Optimize image file
     */
    public function optimizeImage(string $filePath): string
    {
        $imageInfo = getimagesize($filePath);
        if (!$imageInfo) {
            throw new RuntimeException("Invalid image file: {$filePath}");
        }
        
        $mimeType = $imageInfo['mime'];
        $optimizedPath = $this->generateOptimizedPath($filePath, 'img');
        
        switch ($mimeType) {
            case 'image/jpeg':
                $this->optimizeJpeg($filePath, $optimizedPath);
                break;
            case 'image/png':
                $this->optimizePng($filePath, $optimizedPath);
                break;
            case 'image/webp':
                $this->optimizeWebp($filePath, $optimizedPath);
                break;
            default:
                // Copy as-is for unsupported formats
                copy($filePath, $optimizedPath);
        }
        
        if ($this->enableVersioning) {
            $versionedPath = $this->addVersionToAsset($optimizedPath);
            $this->updateAssetManifest($filePath, $versionedPath);
            return $versionedPath;
        }
        
        return $optimizedPath;
    }

    /**
     * Bundle multiple assets into one file
     */
    public function bundleAssets(array $filePaths, string $bundleName, string $type): string
    {
        if (!$this->enableBundling) {
            return implode(',', $filePaths);
        }
        
        $bundleKey = md5($bundleName . implode('', $filePaths));
        
        // Check if bundle is cached
        if (isset($this->bundleCache[$bundleKey])) {
            return $this->bundleCache[$bundleKey];
        }
        
        $bundledContent = '';
        
        foreach ($filePaths as $filePath) {
            $content = $this->readFile($filePath);
            
            if ($type === 'css' && $this->enableMinification) {
                $content = $this->minifyCss($content);
            } elseif ($type === 'js' && $this->enableMinification) {
                $content = $this->minifyJs($content);
            }
            
            $bundledContent .= $content . "\n";
        }
        
        $bundlePath = $this->generateBundlePath($bundleName, $type);
        $this->writeFile($bundlePath, $bundledContent);
        
        if ($this->enableVersioning) {
            $versionedPath = $this->addVersionToAsset($bundlePath);
            $this->bundleCache[$bundleKey] = $versionedPath;
            return $versionedPath;
        }
        
        $this->bundleCache[$bundleKey] = $bundlePath;
        return $bundlePath;
    }

    /**
     * Generate responsive image variants
     */
    public function generateResponsiveImages(string $imagePath, array $sizes = []): array
    {
        $defaultSizes = [320, 640, 768, 1024, 1366, 1920];
        $sizes = !empty($sizes) ? $sizes : $defaultSizes;
        
        $variants = [];
        $imageInfo = getimagesize($imagePath);
        
        if (!$imageInfo) {
            throw new RuntimeException("Invalid image file: {$imagePath}");
        }
        
        $originalWidth = $imageInfo[0];
        $originalHeight = $imageInfo[1];
        
        foreach ($sizes as $width) {
            if ($width >= $originalWidth) {
                continue; // Skip sizes larger than original
            }
            
            $height = (int)(($width / $originalWidth) * $originalHeight);
            $variantPath = $this->generateResponsiveImagePath($imagePath, $width, $height);
            
            $this->resizeImage($imagePath, $variantPath, $width, $height);
            $variants[$width] = $variantPath;
        }
        
        return $variants;
    }

    /**
     * Get asset URL with CDN support
     */
    public function getAssetUrl(string $assetPath): string
    {
        // Check manifest for versioned asset
        if (isset($this->assetManifest[$assetPath])) {
            $assetPath = $this->assetManifest[$assetPath];
        }
        
        if ($this->enableCdn && !empty($this->config['cdn_url'])) {
            return rtrim($this->config['cdn_url'], '/') . '/' . ltrim($assetPath, '/');
        }
        
        return '/' . ltrim($assetPath, '/');
    }

    /**
     * Generate lazy loading HTML for images
     */
    public function generateLazyImage(string $src, string $alt = '', array $attributes = []): string
    {
        $defaultAttributes = [
            'loading' => 'lazy',
            'decoding' => 'async',
        ];
        
        $attributes = array_merge($defaultAttributes, $attributes);
        $attributeString = $this->buildAttributeString($attributes);
        
        return "<img src=\"{$src}\" alt=\"{$alt}\" {$attributeString}>";
    }

    /**
     * Generate critical CSS
     */
    public function generateCriticalCss(array $cssFiles, array $criticalSelectors = []): string
    {
        $criticalCss = '';
        
        foreach ($cssFiles as $cssFile) {
            $content = $this->readFile($cssFile);
            $criticalCss .= $this->extractCriticalCss($content, $criticalSelectors);
        }
        
        return $this->minifyCss($criticalCss);
    }

    /**
     * Preload critical assets
     */
    public function generatePreloadTags(array $assets): string
    {
        $preloadTags = [];
        
        foreach ($assets as $asset) {
            $url = $this->getAssetUrl($asset['path']);
            $type = $asset['type'] ?? $this->getAssetType($asset['path']);
            
            $preloadTags[] = "<link rel=\"preload\" href=\"{$url}\" as=\"{$type}\">";
        }
        
        return implode("\n", $preloadTags);
    }

    /**
     * Clear asset cache
     */
    public function clearAssetCache(): bool
    {
        $this->bundleCache = [];
        $this->assetManifest = [];
        
        return $this->cache->flushTags(['assets']);
    }

    /**
     * Get asset optimization statistics
     */
    public function getOptimizationStats(): array
    {
        return [
            'total_assets' => count($this->assetManifest),
            'bundled_assets' => count($this->bundleCache),
            'minification_enabled' => $this->enableMinification,
            'bundling_enabled' => $this->enableBundling,
            'versioning_enabled' => $this->enableVersioning,
            'cdn_enabled' => $this->enableCdn,
            'cache_size' => $this->calculateCacheSize(),
        ];
    }

    /**
     * Minify CSS content
     */
    private function minifyCss(string $css): string
    {
        // Remove comments
        $css = preg_replace('/\/\*[^*]*\*+([^\/*][^*]*\*+)*\//', '', $css);
        
        // Remove whitespace
        $css = preg_replace('/\s+/', ' ', $css);
        
        // Remove unnecessary spaces
        $css = preg_replace('/;\s*}/', '}', $css);
        $css = preg_replace('/\s*{\s*/', '{', $css);
        $css = preg_replace('/;\s*/', ';', $css);
        $css = preg_replace('/}\s*/', '}', $css);
        
        return trim($css);
    }

    /**
     * Minify JavaScript content
     */
    private function minifyJs(string $js): string
    {
        // Remove single-line comments
        $js = preg_replace('/\/\/.*$/m', '', $js);
        
        // Remove multi-line comments
        $js = preg_replace('/\/\*[^*]*\*+([^\/*][^*]*\*+)*\//', '', $js);
        
        // Remove extra whitespace
        $js = preg_replace('/\s+/', ' ', $js);
        
        // Remove spaces around operators
        $js = preg_replace('/\s*([{}();,])\s*/', '$1', $js);
        
        return trim($js);
    }

    /**
     * Optimize JPEG image
     */
    private function optimizeJpeg(string $sourcePath, string $destinationPath): void
    {
        $quality = $this->config['jpeg_quality'] ?? 85;
        
        $image = imagecreatefromjpeg($sourcePath);
        if ($image) {
            imagejpeg($image, $destinationPath, $quality);
            imagedestroy($image);
        } else {
            copy($sourcePath, $destinationPath);
        }
    }

    /**
     * Optimize PNG image
     */
    private function optimizePng(string $sourcePath, string $destinationPath): void
    {
        $compression = $this->config['png_compression'] ?? 6;
        
        $image = imagecreatefrompng($sourcePath);
        if ($image) {
            imagepng($image, $destinationPath, $compression);
            imagedestroy($image);
        } else {
            copy($sourcePath, $destinationPath);
        }
    }

    /**
     * Optimize WebP image
     */
    private function optimizeWebp(string $sourcePath, string $destinationPath): void
    {
        $quality = $this->config['webp_quality'] ?? 80;
        
        $image = imagecreatefromwebp($sourcePath);
        if ($image) {
            imagewebp($image, $destinationPath, $quality);
            imagedestroy($image);
        } else {
            copy($sourcePath, $destinationPath);
        }
    }

    /**
     * Resize image to specified dimensions
     */
    private function resizeImage(string $sourcePath, string $destinationPath, int $width, int $height): void
    {
        $imageInfo = getimagesize($sourcePath);
        $sourceImage = null;
        
        switch ($imageInfo['mime']) {
            case 'image/jpeg':
                $sourceImage = imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $sourceImage = imagecreatefrompng($sourcePath);
                break;
            case 'image/webp':
                $sourceImage = imagecreatefromwebp($sourcePath);
                break;
        }
        
        if (!$sourceImage) {
            return;
        }
        
        $resizedImage = imagecreatetruecolor($width, $height);
        imagecopyresampled(
            $resizedImage, $sourceImage,
            0, 0, 0, 0,
            $width, $height,
            $imageInfo[0], $imageInfo[1]
        );
        
        switch ($imageInfo['mime']) {
            case 'image/jpeg':
                imagejpeg($resizedImage, $destinationPath, $this->config['jpeg_quality'] ?? 85);
                break;
            case 'image/png':
                imagepng($resizedImage, $destinationPath, $this->config['png_compression'] ?? 6);
                break;
            case 'image/webp':
                imagewebp($resizedImage, $destinationPath, $this->config['webp_quality'] ?? 80);
                break;
        }
        
        imagedestroy($sourceImage);
        imagedestroy($resizedImage);
    }

    /**
     * Generate optimized file path
     */
    private function generateOptimizedPath(string $originalPath, string $type): string
    {
        $pathInfo = pathinfo($originalPath);
        $directory = $pathInfo['dirname'];
        $filename = $pathInfo['filename'];
        $extension = $pathInfo['extension'];
        
        return "{$directory}/{$filename}.min.{$extension}";
    }

    /**
     * Generate bundle file path
     */
    private function generateBundlePath(string $bundleName, string $type): string
    {
        return "{$this->publicPath}/bundles/{$bundleName}.{$type}";
    }

    /**
     * Generate responsive image path
     */
    private function generateResponsiveImagePath(string $originalPath, int $width, int $height): string
    {
        $pathInfo = pathinfo($originalPath);
        $directory = $pathInfo['dirname'];
        $filename = $pathInfo['filename'];
        $extension = $pathInfo['extension'];
        
        return "{$directory}/{$filename}_{$width}x{$height}.{$extension}";
    }

    /**
     * Add version hash to asset
     */
    private function addVersionToAsset(string $assetPath): string
    {
        $hash = substr(md5_file($assetPath), 0, 8);
        $pathInfo = pathinfo($assetPath);
        
        return $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.' . $hash . '.' . $pathInfo['extension'];
    }

    /**
     * Update asset manifest
     */
    private function updateAssetManifest(string $originalPath, string $optimizedPath): void
    {
        $this->assetManifest[$originalPath] = $optimizedPath;
        $this->saveAssetManifest();
    }

    /**
     * Load asset manifest from cache
     */
    private function loadAssetManifest(): void
    {
        $manifest = $this->cache->get('asset_manifest');
        if ($manifest) {
            $this->assetManifest = $manifest;
        }
    }

    /**
     * Save asset manifest to cache
     */
    private function saveAssetManifest(): void
    {
        $this->cache->set('asset_manifest', $this->assetManifest, 86400); // 24 hours
    }

    /**
     * Read file content
     */
    private function readFile(string $filePath): string
    {
        if (!file_exists($filePath)) {
            throw new RuntimeException("File not found: {$filePath}");
        }
        
        return file_get_contents($filePath);
    }

    /**
     * Write content to file
     */
    private function writeFile(string $filePath, string $content): void
    {
        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        file_put_contents($filePath, $content);
    }

    /**
     * Build HTML attribute string
     */
    private function buildAttributeString(array $attributes): string
    {
        $attributePairs = [];
        
        foreach ($attributes as $key => $value) {
            $attributePairs[] = "{$key}=\"{$value}\"";
        }
        
        return implode(' ', $attributePairs);
    }

    /**
     * Extract critical CSS based on selectors
     */
    private function extractCriticalCss(string $css, array $criticalSelectors): string
    {
        if (empty($criticalSelectors)) {
            return $css; // Return all CSS if no critical selectors specified
        }
        
        $criticalCss = '';
        
        foreach ($criticalSelectors as $selector) {
            $pattern = '/(' . preg_quote($selector, '/') . ')\s*{[^}]*}/i';
            if (preg_match($pattern, $css, $matches)) {
                $criticalCss .= $matches[0] . "\n";
            }
        }
        
        return $criticalCss;
    }

    /**
     * Get asset type from file path
     */
    private function getAssetType(string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        switch ($extension) {
            case 'css':
                return 'style';
            case 'js':
                return 'script';
            case 'woff':
            case 'woff2':
            case 'ttf':
            case 'otf':
                return 'font';
            case 'jpg':
            case 'jpeg':
            case 'png':
            case 'gif':
            case 'webp':
            case 'svg':
                return 'image';
            default:
                return 'fetch';
        }
    }

    /**
     * Calculate total cache size
     */
    private function calculateCacheSize(): int
    {
        $size = 0;
        
        foreach ($this->assetManifest as $originalPath => $optimizedPath) {
            if (file_exists($optimizedPath)) {
                $size += filesize($optimizedPath);
            }
        }
        
        return $size;
    }
}