<?php
$file = 'C:/ServBay/www/control-mantenimiento-equipos/public/css/glass.css';
$c = file_get_contents($file);

$c = str_replace("\xe2\x94\x80", '-', $c);  // ─
$c = str_replace("\xe2\x94\x81", '-', $c);  // ━
$c = str_replace("\xe2\x94\x82", '', $c);   // │
$c = str_replace("\xe2\x94\x83", '', $c);   // ┃
$c = str_replace("\xe2\x94\x8c", '', $c);   // ┌
$c = str_replace("\xe2\x94\x90", '', $c);   // ┐
$c = str_replace("\xe2\x94\x94", '', $c);   // └
$c = str_replace("\xe2\x94\x98", '', $c);   // ┘
$c = str_replace("\xe2\x94\x9c", '', $c);   // ├
$c = str_replace("\xe2\x94\xa4", '', $c);   // ┤
$c = str_replace("\xe2\x94\xac", '', $c);   // ┬
$c = str_replace("\xe2\x94\xb4", '', $c);   // ┴
$c = str_replace("\xe2\x94\xbc", '', $c);   // ┼
$c = str_replace("\xe2\x80\x94", '--', $c); // —
$c = str_replace("\xc3\xa9", 'e', $c);      // é
$c = str_replace("\xc3\xb3", 'o', $c);      // ó
$c = str_replace("\xc3\xa1", 'a', $c);      // á
$c = str_replace("\xc3\xad", 'i', $c);      // í
$c = str_replace("\xc3\xba", 'u', $c);      // ú
$c = str_replace("\xc3\xb1", 'n', $c);      // ñ

file_put_contents($file, $c);
echo "OK\n";