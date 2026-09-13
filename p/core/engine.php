<?php
require_once 'phone-normalizer.php';
require_once 'nik-analyzer.php';
require_once 'identity-correlator.php';
require_once 'cache-engine.php';

class OSINTEngineV3 {
    private $datasetPath = __DIR__ . '/../data/dataset.txt';
    private $cache;
    private $correlator;
    private $nikAnalyzer;

    public function __construct() {
        $this->cache = new CacheEngine();
        $this->correlator = new IdentityCorrelator();
        $this->nikAnalyzer = new NIKAnalyzer();
    }

    public function search($rawQuery) {
        $startTime = microtime(true);
        $rawQuery = strtolower(trim(htmlspecialchars($rawQuery, ENT_QUOTES, 'UTF-8')));
        
        // Cek Cache
        $cachedData = $this->cache->get($rawQuery);
        if ($cachedData) {
            $cachedData['speed'] = round(microtime(true) - $startTime, 4);
            $cachedData['source'] = 'CACHE_ENGINE';
            return $cachedData;
        }

        // Advanced Search Parsing (name:andi, phone:628...)
        $searchType = 'all';
        $query = $rawQuery;
        if (strpos($rawQuery, ':') !== false) {
            list($searchType, $query) = explode(':', $rawQuery, 2);
        }

        // Jalankan pencarian (Stream Search Mode untuk hemat RAM)
        $rawResults = $this->streamSearch($query, $searchType);
        
        // Korelasi Data
        $intelData = $this->correlator->correlate($rawResults);
        
        // Analisis NIK pada setiap node
        foreach ($intelData['profiles'] as &$profile) {
            $profile['nik_analysis'] = [];
            foreach ($profile['nodes']['niks'] as $nik) {
                $analysis = $this->nikAnalyzer->analyze($nik);
                if ($analysis) $profile['nik_analysis'][$nik] = $analysis;
            }
        }

        $response = [
            'status' => 'success',
            'query' => $rawQuery,
            'source' => 'LIVE_DATASET',
            'speed' => round(microtime(true) - $startTime, 4),
            'records_found' => count($rawResults),
            'profiles' => $intelData['profiles'],
            'threats' => $intelData['threats']
        ];

        // Simpan ke Cache
        if ($response['records_found'] > 0) {
            $this->cache->set($rawQuery, $response);
        }

        return $response;
    }

    private function streamSearch($query, $type) {
        $results = [];
        $handle = fopen($this->datasetPath, 'r');
        if (!$handle) return [];

        $isPhoneSearch = preg_match('/^[0-9]+$/', $query);
        if ($isPhoneSearch && $type === 'all') $query = normalizePhone($query);

        $limit = 50000; // Hard limit mencegah RAM crash
        while (($line = fgets($handle)) !== false) {
            $match = false;
            if ($type === 'name' && strpos(strtolower($line), $query) !== false) $match = true;
            elseif ($type === 'phone' && strpos(normalizePhone($line), $query) !== false) $match = true;
            elseif ($type === 'all' && strpos(strtolower($line), $query) !== false) $match = true;

            if ($match) {
                $cols = explode(',', trim($line));
                if (count($cols) >= 5) {
                    $results[] = [
                        'nik' => ($cols[0] === '-' || empty($cols[0])) ? '-' : trim($cols[0]),
                        'phone' => ($cols[1] === '-' || empty($cols[1])) ? '-' : normalizePhone(trim($cols[1])),
                        'name' => ($cols[2] === '-' || empty($cols[2])) ? '-' : trim($cols[2]),
                        'email' => ($cols[3] === '-' || empty($cols[3])) ? '-' : trim($cols[3]),
                        'birthdate' => ($cols[4] === '-' || empty($cols[4])) ? '-' : trim($cols[4])
                    ];
                    if (count($results) >= $limit) break;
                }
            }
        }
        fclose($handle);
        return $results;
    }
}
?>
