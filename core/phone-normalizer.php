<?php
function normalizePhone($phone) {
    if (empty($phone) || $phone === '-') return '-';
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (substr($phone, 0, 1) === '0') $phone = '62' . substr($phone, 1);
    elseif (substr($phone, 0, 3) === '062') $phone = substr($phone, 1);
    return $phone;
}
?>
