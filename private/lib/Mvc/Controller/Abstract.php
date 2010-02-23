<?php
    
    /* $Id$ */

    /**
    * Абстрактный контроллер, родительский класс для всех контроллеров.
    */
    abstract class Mvc_Controller_Abstract {
        /**
        * Объект запроса.
        * 
        * @var Http_Request
        */
        private $_request;
        
        /**         
        * Список переменных для шаблона.
        * 
        * @var array
        */
        private $_view_vars = array();
        
        /**
        * Метод-конструктор класса.
        * 
        * @param  Http_Request $request Объект запроса.
        * @return void
        */
        public function __construct(Http_Request $request) {
            $this->setRequest($request);
        }
        
        /**
        * Получение объекта запроса.
        * 
        * @return Http_Request
        */
        protected function getRequest() {
            return $this->_request;
        }
        
        /**
        * Установка объекта запроса.
        * 
        * @param  Http_Request $request Объект запроса.
        * @return void
        */
        protected function setRequest(Http_Request $request) {
            $this->_request = $request;
        }
        
        /**
        * Установка значения для переменной шаблона.
        * 
        * @param  string $var   Имя переменной.
        * @param  mixed  $value Значение переменной.
        * @return void
        */
        protected function set($var, $value) {
            $this->_view_vars[$var] = $value;
        }
        
        /**
        * Сборка и вывод шаблона. Если шаблон не указан, автоматически
        * выбирается соответствующий контроллеру/действию. Значения переменных,
        * переданных в этот метод имеют приоритет над значениями, установленными
        * в методе set().
        * 
        * @see self::set()
        * 
        * @param  string $template Название шаблона.
        * @param  array  $vars     Переменные для шаблона.
        * @param  string $layout   Название макета.
        * @return void
        */
        public function render
        (
            $template = null, array $vars = array(), $layout = 'default'
        )
        {   
            /* Если шаблон не указан, то... */
            if (null === $template) {
                /* определяем его автоматически на основе данных от роутера */
                $handler = $this->getRequest()->_router['handler'];
                $template = sprintf(
                    '%s/%s', $handler['controller'], $handler['action']
                );
            }              
            
            /* Объединяем переменные для шаблона с заданными через set() */
            $view_vars = array_merge($this->_view_vars, $vars);
            
            /* Настраиваем объект работы с шаблонами и выводим страницу */
            $view = Resources::getInstance()->view;
            $links = Resources::getInstance()->links;
            
            $view->setLayout($layout)
                 ->setTemplate($template)
                 ->set('_request', $this->_request)
                 ->set('_links', $links)
                 ->setVars($view_vars)
                 ->render();
        }
        
        /**
        * Показывает простое сообщения пользователю с переадресацией на указнный
        * адрес. Если в $timeout передать значение false, то автоматический
        * переход не будет осуществляться.
        * 
        * @param  string      $message Сообщение.
        * @param  string      $link    Адрес для перехода.
        * @param  int|boolean $timeout Задержка перед автоматическим переходом.
        * @return void
        */
        public function flash($message, $link, $timeout = 5) {
            $vars = array(
                'message' => $message,
                'link'    => $link,
                'timeout' => $timeout
            );
            
            $this->render('common/flash', $vars);
        }
    }

?>