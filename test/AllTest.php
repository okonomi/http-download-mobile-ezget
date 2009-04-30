<?php

require_once 'PHPUnit/Framework/TestSuite.php';

require_once dirname(__FILE__) . '/EZgetTestCase.php';


class AllTests
{
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite();

        $suite->addTestSuite('HTTP_Download_Mobile_EZgetTestCase');

        return $suite;
    }

}
