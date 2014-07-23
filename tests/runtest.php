<?php

require_once('php_unit_test_framework/php_unit_test.php');
require_once('php_unit_test_framework/text_test_runner.php');

require_once ('../src/database.php');

require_once('select.php');
require_once('insert.php');

$suite = new TestSuite;
$suite->AddTest('SelectTestCase');
$suite->AddTest('InsertTestCase');

$runner = new TextTestRunner;
$runner->run($suite, 'results');

?>