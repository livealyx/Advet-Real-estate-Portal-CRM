<?php
/**
 * FILE: includes/cache.php
 * Robust Caching Service for Advet Buildwell
 */

class AdvetCache {
    private static $cacheDir = __DIR__ . '/../storage/cache/';
    private static $enabled = false;
    private static $ttl_listing = 600; // 10 mins default
    private static $ttl_detail = 1800; // 30 mins default

    /**
     * Initialize cache with system settings
     */
    public static function init($settings) {
        self::$enabled = ($settings['cache_enabled'] ?? '0') === '1';
        self::$ttl_listing = (int)($settings['cache_ttl_listing'] ?? 600);
        self::$ttl_detail = (int)($settings['cache_ttl_detail'] ?? 1800);

        if (!is_dir(self::$cacheDir)) {
            mkdir(self::$cacheDir, 0777, true);
        }
    }

    /**
     * Get unique cache key based on URL and optional params
     */
    public static function generateKey($prefix, $params = []) {
        $id = $prefix . '_' . md5(serialize($params) . ($_SESSION['user']['role'] ?? 'guest'));
        return $id;
    }

    /**
     * Retrieve data from cache
     */
    public static function get($key) {
        if (!self::$enabled) return null;

        $file = self::$cacheDir . md5($key) . '.cache';
        if (!file_exists($file)) return null;

        $raw = file_get_contents($file);
        $data = json_decode($raw, true);

        if (!$data || !isset($data['expires']) || time() > $data['expires']) {
            @unlink($file);
            return null;
        }

        return $data['content'];
    }

    /**
     * Store data in cache
     */
    public static function set($key, $content, $ttl = null) {
        if (!self::$enabled) return false;

        $ttl = $ttl ?? self::$ttl_listing;
        $file = self::$cacheDir . md5($key) . '.cache';

        $data = [
            'expires' => time() + $ttl,
            'content' => $content,
            'created_at' => time(),
            'key_original' => $key
        ];

        // Compress if content is large
        if (strlen($content) > 10240) { // > 10KB
            // Optional: gzcompress could go here if Zlib is enabled
        }

        return file_put_contents($file, json_encode($data));
    }

    /**
     * Clear all cache files
     */
    public static function clear() {
        if (!is_dir(self::$cacheDir)) return true;
        
        $files = glob(self::$cacheDir . '*.cache');
        foreach ($files as $file) {
            if (is_file($file)) @unlink($file);
        }

        file_put_contents(self::$cacheDir . '.last_cleared', time());
        return true;
    }

    /**
     * Invalidate cache for a specific group or clear all
     */
    public static function invalidate() {
        return self::clear();
    }

    /**
     * Get cache statistics for Dashboard
     */
    public static function getStats() {
        if (!is_dir(self::$cacheDir)) return ['count' => 0, 'size' => 0, 'last_cleared' => null, 'enabled' => self::$enabled];
        
        $files = glob(self::$cacheDir . '*.cache');
        if ($files === false) $files = [];
        $size = 0;
        foreach ($files as $file) {
            $size += filesize($file);
        }

        $lastClearedFile = self::$cacheDir . '.last_cleared';
        $lastCleared = file_exists($lastClearedFile) ? filemtime($lastClearedFile) : null;

        return [
            'count' => count($files),
            'size' => $size,
            'last_cleared' => $lastCleared,
            'enabled' => self::$enabled
        ];
    }

    /**
     * Get TTL based on page type
     */
    public static function getTTL($type) {
        return ($type === 'detail') ? self::$ttl_detail : self::$ttl_listing;
    }
}
