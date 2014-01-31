<?php
require_once __DIR__ . '/vendor/autoload.php';

define('OIC_TEST_FW_ROOT', __DIR__);

// -------------
function _dump($value)
{
    error_log(print_r($value, true));
}