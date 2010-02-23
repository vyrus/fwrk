<?php
    
    $cur_dir = dirname(__FILE__);
    $root    = realpath($cur_dir . '/../private');
    
    require_once $root . '/lib/Init.php';
    require_once $root . '/lib/Init/Exception.php';
    
    Init::define('DS',          DIRECTORY_SEPARATOR);
    Init::define('ROOT',        $root);
    Init::define('LIB',         ROOT . DS . 'lib');
    Init::define('THIRD_PARTY', ROOT . DS . 'third_party');
    Init::define('TESTS',       realpath(ROOT . DS . '../tests'));
          
    Init::setIncludePath( array(LIB, THIRD_PARTY, TESTS) );
    
    require_once 'Zend/Loader/Autoloader.php';
    Zend_Loader_Autoloader::getInstance()
        ->setFallbackAutoloader(true)
        ->suppressNotFoundWarnings(false);
    
    require_once 'PHPUnit/Framework.php';
    
?>