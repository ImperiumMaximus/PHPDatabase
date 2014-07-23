<?php

require_once('php_unit_test_framework/php_unit_test.php');
require_once('php_unit_test_framework/text_test_runner.php');
require_once('select.php');

$suite = new TestSuite;
$suite->AddTest('SelectTestCase');

$runner = new TextTestRunner;
$runner->run($suite, 'results');

?>