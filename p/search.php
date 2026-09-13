<?php
session_start();
require_once 'core/engine.php';

header('Content-Type: application/json');

$query = isset($_POST['query']) ? trim($_POST['query']) : '';
$secret_code = isset($_POST['secret_code']) ? trim($_POST['secret_code']) : '';

if (empty($query)) {
    echo json_encode(['status' => 'error', 'message' => 'Empty query.']);
    exit;
}

// Cek Kode Rahasia
$is_censored = true;
$codes_file = __DIR__ . '/data/codes.json';

if (file_exists($codes_file)) {
    $codes = json_decode(file_get_contents($codes_file), true);
    
    if (!empty($secret_code) && isset($codes[$secret_code])) {
        // Cek apakah kode aktif dan sisa penggunaan masih ada
        if ($codes[$secret_code]['status'] === 'active' && $codes[$secret_code]['used'] < $codes[$secret_code]['limit']) {
            $is_censored = false;
            
            // Tambah penggunaan
            $codes[$secret_code]['used']++;
            
            // Jika limit sudah habis, nonaktifkan
            if ($codes[$secret_code]['used'] >= $codes[$secret_code]['limit']) {
                $codes[$secret_code]['status'] = 'inactive';
            }
            
            // Simpan kembali
            file_put_contents($codes_file, json_encode($codes));
        }
    }
}

// Catat log
if (!file_exists(__DIR__ . '/logs')) mkdir(__DIR__ . '/logs', 0777, true);
$log_entry = date('Y-m-d H:i:s') . " | IP: {$_SERVER['REMOTE_ADDR']} | Q: {$query} | Code: {$secret_code} | Censored: " . ($is_censored ? 'YES' : 'NO') . "\n";
file_put_contents(__DIR__ . '/logs/search_logs.txt', $log_entry, FILE_APPEND);

// Jalankan pencarian utama
$engine = new OSINTEngineV3();
$result = $engine->search($query);

// ==========================================
// SISTEM SENSOR OTOMATIS JIKA TANPA KODE
// ==========================================
if ($is_censored && isset($result['profiles'])) {
    $result['is_censored'] = true;
    foreach ($result['profiles'] as &$profile) {
        // Sensor Nama (Sisakan 3 huruf awal)
        $len = strlen($profile['primary_name']);
        if ($len > 3) {
            $profile['primary_name'] = substr($profile['primary_name'], 0, 3) . str_repeat('*', $len - 3);
        }

        foreach ($profile['records'] as &$record) {
            if ($record['nik'] !== '-') $record['nik'] = substr($record['nik'], 0, 4) . '********' . substr($record['nik'], -4);
            if ($record['phone'] !== '-') $record['phone'] = substr($record['phone'], 0, 4) . '****' . substr($record['phone'], -3);
            if ($record['email'] !== '-') {
                $parts = explode('@', $record['email']);
                if (count($parts) == 2) $record['email'] = substr($parts[0], 0, 2) . '***@' . $parts[1];
            }
        }

        // Sensor Nodes Info
        if (!empty($profile['nodes']['phones'])) {
            foreach ($profile['nodes']['phones'] as &$p) $p = substr($p, 0, 4) . '****' . substr($p, -3);
        }
        if (!empty($profile['nodes']['niks'])) {
            foreach ($profile['nodes']['niks'] as &$n) $n = substr($n, 0, 4) . '********' . substr($n, -4);
        }
        
        // Sensor NIK Analysis result
        if (!empty($profile['nik_analysis'])) {
            $new_analysis = [];
            foreach ($profile['nik_analysis'] as $old_nik => $analysis) {
                $censored_nik = substr($old_nik, 0, 4) . '********' . substr($old_nik, -4);
                if (isset($analysis['birthdate']) && $analysis['birthdate'] !== '-') {
                    $bparts = explode('-', $analysis['birthdate']);
                    if(count($bparts) == 3) $analysis['birthdate'] = '**-**-' . $bparts[2];
                }
                $analysis['nik'] = $censored_nik;
                $new_analysis[$censored_nik] = $analysis;
            }
            $profile['nik_analysis'] = $new_analysis;
        }
    }
} else {
    $result['is_censored'] = false;
}

echo json_encode($result);
?>
