<?php
$ch = curl_init('http://tecny-sistemas.local/mantenimientos/reportes?export=pdf');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
// Do NOT use NOBODY, because some servers require body to send headers correctly.
$res = curl_exec($ch);
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$header = substr($res, 0, $header_size);
file_put_contents('headers.txt', $header);
