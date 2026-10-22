<?php

require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

use Bolt\helpers\FileCache;

$cache = new FileCache();
$key = 'test_lock_key';
if ($cache->lock($key)) {
    sleep(3);
    $cache->set($key, 123);
    $cache->unlock($key);
}
