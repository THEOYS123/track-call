<?php
header('Content-Type: application/json');

$code = isset($_POST['code']) ? trim($_POST['code']) : '';
$codes_file = __DIR__ . '/data/codes.json';

if (empty($code)) {
    echo json_encode(['status' => 'error', 'message' => 'Kode tidak boleh kosong.']);
    exit;
}

if (file_exists($codes_file)) {
    $codes = json_decode(file_get_contents($codes_file), true);
    if (isset($codes[$code])) {
        $data = $codes[$code];
        $sisa = $data['limit'] - $data['used'];
        echo json_encode([
            'status' => 'success',
            'limit' => $data['limit'],
            'used' => $data['used'],
            'sisa' => $sisa,
            'state' => $data['status']
        ]);
        exit;
    }
}

echo json_encode(['status' => 'error', 'message' => 'Kode rahasia tidak ditemukan atau salah.']);
?>
