#!/usr/bin/env php
<?php

error_reporting(E_STRICT | E_ALL);

$libraryDirectory = dirname(__FILE__) . '/../library/';

if (!class_exists('SplClassLoader')) {
    require_once $libraryDirectory . '/PHPTools/SPL/SplClassLoader.php';
    $loader = new \PHPTools\SPL\SplClassLoader('PHPTools', $libraryDirectory);
} else {
    $loader = new \SplClassLoader('PHPTools', $libraryDirectory);
}

$loader->register();
\PHPTools\Namespacer\CLIRunner::main();
