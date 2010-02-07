<?php

    /* $Id$ */
    
    /**
    * Абстрактный класс менеджера ресурсов для хранения конфигурации и загрузки
    * в соответствии с ней различных ресурсов приложения. Реализует паттерн
    * Singleton.
    */
    class Resources_Abstract {
        /**
        * Экземпляр класса.
        * 
        * @var Resources_Abstract
        */
        protected static $_instance = null;
        
        /**
        * Список уже инициализированных ресурсов.
        * 
        * @var array
        */
        protected $_resources = array();
        
        /**
        * Конфигурация для инициализации ресурсов.
        * 
        * @var array
        */
        protected $_config;
        
        /**
        * Метод-конструктор класса.
        * 
        * @param  array $config Массив с конфигурацией.
        * @return void
        */
        public function __construct(array $config = array()) {
            $this->_config = $config;
        }
        
        /**
        * Получение экземпляра класса. Этот метод не выполняет автоматическое
        * создание экземпляра, поэтому перед вызовом этого метода, надо
        * убедиться, что экземпляр установлен с помощью метода setInstance. 
        * 
        * @see self::setInstance()
        * 
        * @return Resources_Abstract
        */
        public static function getInstance() {
            return self::$_instance;
        }
        
        /**
        * Уставнока активного экземпляра класса.
        * 
        * @param  Resources_Abstract $resources Экземпляр класса.
        * @return void
        */
        protected static function setInstance(Resources_Abstract $resources) {
            self::$_instance = $resources;
        }
        
        /**
        * Получение ресурса и его автоматическая инициализация, если такой
        * ресурс ещё не запрашивался.
        * 
        * @link http://www.php.net/manual/en/language.oop5.overloading.php
        * 
        * @param  string $name Название ресурса.
        * @return mixed
        */
        public function __get($name) {
            /**
            * Если такой ресурс уже находится в списке инициализированных
            * ресурсов, то возвращаем его.
            */
            if ($this->__isset($name)) {
                return $this->_resources[$name];
            }
            
            /* Определяем метод, ответственный за инициализацию ресурса */
            $method = 'get_' . $name;
            
            /* Если такого метода нет, генерируем исключение */
            if (!method_exists($this, $method))
            {
                $msg = 'Ресурс не найден: ' . $name;
                throw new InvalidArgumentException($msg);
            }
            
            /* Вызываем метод и сохраняем новый ресурс в списке */
            $callback = array($this, $method);
            $resource = call_user_func($callback);
            $this->_resources[$name] = $resource;
            
            return $resource;
        }
        
        /**
        * Проверка на наличие ресурса в списке уже инициализированных ресурсов.
        * 
        * @link http://www.php.net/manual/en/language.oop5.overloading.php
        * 
        * @param  string $resource Название ресурса.
        * @return boolean
        */
        public function __isset($resource) {
            return isset($this->_resources[$resource]);
        }
    }
        
?>