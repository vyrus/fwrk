<?php
    
    require_once dirname(__FILE__) . '/../init.php';
    
    class Mvc_AllTests {
        public static function suite() {
            $suite = new PHPUnit_Framework_TestSuite('Mvc');
            
            $suite->addTestSuite('Mvc_LinksTest');
            
            return $suite;
        }
    }

?>