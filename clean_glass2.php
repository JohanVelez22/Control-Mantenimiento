<?php
$file = 'C:/ServBay/www/control-mantenimiento-equipos/public/css/glass.css';
$c = file_get_contents($file);

// Unicode characters (the actual codepoints in the string)
$c = preg_replace('/[\x{2500}\x{2501}\x{2502}\x{2503}\x{250c}\x{2510}\x{2514}\x{2518}\x{251c}\x{2524}\x{252c}\x{2534}\x{253c}]/u', '', $c);
$c = preg_replace('/\x{2014}/u', '--', $c);
$c = preg_replace('/\x{00e9}/u', 'e', $c);
$c = preg_replace('/\x{00f3}/u', 'o', $c);
$c = preg_replace('/\x{00e1}/u', 'a', $c);
$c = preg_replace('/\x{00ed}/u', 'i', $c);
$c = preg_replace('/\x{00fa}/u', 'u', $c);
$c = preg_replace('/\x{00f1}/u', 'n', $c);

file_put_contents($file, $c);
echo "OK\n";