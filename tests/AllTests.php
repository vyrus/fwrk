<?php
    
    require_once dirname(__FILE__) . '/init.php';
    
    class AllTests {
        public static function suite() {
            $suite = new PHPUnit_Framework_TestSuite('fwrk');
            
            $suite->addTest(Mvc_AllTests::suite());
            
            return $suite;
        }
    }

?>