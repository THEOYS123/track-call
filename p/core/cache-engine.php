<?php

class CacheEngine {

    private $cache_file = __DIR__ . '/../cache/search_cache.json';

    private $max_size = 5242880; 
    // 5MB

    private $max_age = 86400; 
    // 24 jam

    public function __construct() {

        if (!file_exists($this->cache_file)) {
            file_put_contents($this->cache_file, '{}');
        }

        $this->autoClean();
    }

    private function autoClean() {

        clearstatcache();

        if (!file_exists($this->cache_file)) {
            return;
        }

        $size = filesize($this->cache_file);
        $age = time() - filemtime($this->cache_file);

        if ($size > $this->max_size || $age > $this->max_age) {

            file_put_contents($this->cache_file, '{}');

        }
    }

    public function get($key) {

        $data = json_decode(file_get_contents($this->cache_file), true);

        if (isset($data[$key])) {
            return $data[$key];
        }

        return null;

    }

    public function set($key, $value) {

        $data = json_decode(file_get_contents($this->cache_file), true);

        $data[$key] = $value;

        file_put_contents($this->cache_file, json_encode($data));

        // 🔥 AUTO CLEAN SETELAH WRITE
        $this->autoClean();

    }

}
?>
