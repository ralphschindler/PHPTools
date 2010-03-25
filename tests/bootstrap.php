<?php 

$libraryDirectory = realpath(dirname(__FILE__) . '/../library/');

if (!class_exists('SplClassLoader')) {
    require_once $libraryDirectory . '/PHPTools/SPL/SplClassLoader.php';
    $loader = new \PHPTools\SPL\SplClassLoader('PHPTools', $libraryDirectory);
} else {
    $loader = new \SplClassLoader('PHPTools', $libraryDirectory);
}
$loader->register();