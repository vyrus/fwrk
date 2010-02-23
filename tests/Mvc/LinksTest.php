<?php

    require_once dirname(__FILE__) . '/../init.php';

    class Mvc_LinksTest extends PHPUnit_Framework_TestCase {
        protected $_domain = 'http://whatever.tld';
        protected $_path = '/deeply/deeply/placed/path';
        
        /**
        * Тест создания экземпляра класса.
        */
        public function testCreate() {
            $links = Mvc_Links::create();
            $this->assertType('Mvc_Links', $links);
        }
        
        /**
        * Тест установки и получения базового пути.
        */
        public function testSetGetBasePath() {
            $links = Mvc_Links::create();
            
            /* Определяем адрес */
            $url = $this->_domain . $this->_path;
            
            /* Устанавливаем адрес и проверяем fluent interface */
            $this->assertEquals($links, $links->setBaseUrl($url));
            /* Проверяем правильность определения пути */
            $this->assertEquals($this->_path, $links->getBasePath());
            
            /* Базовый адрес без пути */
            $links->setBaseUrl($this->_domain);
            $this->assertEquals('', $links->getBasePath());
        }
        
        /**
        * Тест попытки получения ссылки по несуществующему маршруту.
        */
        public function testNonExistentRoute() {
            $links = Mvc_Links::create();
            
            /* Ловим исключение */
            $this->setExpectedException('InvalidArgumentException');
            $links->get('non-existent-alias');
        }
        
        /**
        * Тест попытки обработки маршрута несуществующего типа.
        */
        public function testUknownRouteType() {
            $links = Mvc_Links::create();
            
            /* Настраиваем менеджер ссылок */
            $links->setBaseUrl($this->_domain . $this->_path)
                  ->addRoute(array
                  (
                      'alias'   => 'test.route',
                      'type'    => 'uknown-route-type',
                      'pattern' => '/test-route',
                  ));
            
            /* Ловим исключение */
            $this->setExpectedException('Mvc_Links_Exception');
            $link = $links->get('test.route');
        }
        
        /**
        * Тест обработки статичных маршрутов.
        */
        public function testStaticRoute() {
            $links = Mvc_Links::create();
            
            /* Настраиваем менеджер ссылок */
            $links->setBaseUrl($this->_domain . $this->_path)
                  ->addRoute(array
                  (
                      'alias'   => 'test.route',
                      'type'    => Mvc_Router::ROUTE_STATIC,
                      'pattern' => '/test-route',
                  ));
            
            /* Получаем ссылку и проверяем её */
            $link = $links->get('test.route');
            $this->assertEquals($this->_path . '/test-route/', $link);
        }
        
        /**
        * Тест обработки маршрутов на регулярках.
        */
        public function testRegexRoute() {
            $links = Mvc_Links::create();
            
            /* Настраиваем менеджер ссылок */
            $links->setBaseUrl($this->_domain . $this->_path)
                  ->addRoute(array
                  (
                      'alias'   => 'test.route',
                      'type'    => Mvc_Router::ROUTE_REGEX,
                      'pattern' => array
                      (
                          'regex'  => '/test-route/([0-9]+)/([a-z]+)',
                          'params' => array('num', 'alpha')
                      )
                  ));
            
            /* Получаем ссылку по полному списку параметров */
            $link = $links->get('test.route', array('num'   => 7,
                                                    'alpha' => 'abc'));
            /* Проверяем её */
            $this->assertEquals($this->_path . '/test-route/7/abc/', $link);
            
            /* Получаем ссылку по списку с опущенным последним параметром */
            $link = $links->get('test.route', array('num' => 7));
            /* И проверяем её */
            $this->assertEquals($this->_path . '/test-route/7/', $link);
        }
    }

?>