<?php
class IdentityCorrelator {
    public function correlate($rawData) {
        $profiles = [];
        $threats = [];
        $phoneCount = [];
        $nikCount = [];

        foreach ($rawData as $row) {
            $nik = $row['nik'];
            $phone = $row['phone'];
            
            // Hitung duplikasi untuk Threat Indicator
            if ($phone !== '-') $phoneCount[$phone] = ($phoneCount[$phone] ?? 0) + 1;
            if ($nik !== '-') $nikCount[$nik] = ($nikCount[$nik] ?? 0) + 1;

            // Kunci profil berdasarkan NIK, lalu Phone, lalu Nama
            $profileKey = ($nik !== '-') ? $nik : (($phone !== '-') ? $phone : $row['name']);
            $profileKey = md5(strtolower($profileKey));

            if (!isset($profiles[$profileKey])) {
                $profiles[$profileKey] = [
                    'primary_name' => $row['name'],
                    'records' => [],
                    'nodes' => ['phones' => [], 'niks' => [], 'emails' => []]
                ];
            }

            $profiles[$profileKey]['records'][] = $row;
            if ($phone !== '-' && !in_array($phone, $profiles[$profileKey]['nodes']['phones'])) 
                $profiles[$profileKey]['nodes']['phones'][] = $phone;
            if ($nik !== '-' && !in_array($nik, $profiles[$profileKey]['nodes']['niks'])) 
                $profiles[$profileKey]['nodes']['niks'][] = $nik;
            if ($row['email'] !== '-' && !in_array($row['email'], $profiles[$profileKey]['nodes']['emails'])) 
                $profiles[$profileKey]['nodes']['emails'][] = $row['email'];
        }

        // Generate Threats
        foreach ($phoneCount as $ph => $count) {
            if ($count > 1) $threats[] = "Phone number $ph appears in $count records. Possible identity masking.";
        }

        return ['profiles' => array_values($profiles), 'threats' => $threats];
    }
}
?>
