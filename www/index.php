<?php

use provatar\proVatar;

require_once '../src/proVatar.php';

define('SHOW_DEMO', true);

$pv = new proVatar(5);

//echo $pv->build('roomet@gmail.com', '', true, 256);
if (SHOW_DEMO) {
    echo $pv->display_parts_demo();
} else {
    echo $pv->build('progeja@gmail.com', '', $img = true, $outsize = 512, $write = true, $random = true);
}

/**
 * Dumping value(s)
 *
 * @param mixed $var Dumped value
 */
function __($var)
{
    echo "<pre>";
    var_dump($var);
    echo "</pre>";
}

