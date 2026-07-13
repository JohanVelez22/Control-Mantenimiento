<?php
$c = file_get_contents('C:/ServBay/www/control-mantenimiento-equipos/public/css/glass.css');
foreach(explode("\n",$c) as $i=>$l) if(strpos($l,"scrollbar")!==false) echo ($i+1).": ".trim($l)."\n";