<?php
    
    /* $Id$ */

    /**
    * Класс, представляющий собой запрос. Инкапсулирует в себе основные
    * параметры запроса.
    */
    class Http_Request {
        /**
        * Контейнер для параметров запроса.
        * 
        * @var array
        */
        protected $_storage = array();
        
        /**
        * Метод-конструктор класса. Инициализирует параметры запроса. Если они
        * не переданы в аргументе $values, то берёт их из суперглобальных
        * переменных.
        * 
        * @param  array $values Список параметров запроса.
        * @return void
        */
        public function __construct(array $values = array()) {
            if (empty($values))
            {
                $values = array(
                    'get'    => $_GET,
                    'post'   => $_POST,
                    'cookie' => $_COOKIE,
                    'files'  => $_FILES,
                    'server' => $_SERVER
                );
            }
            
            $this->setMulti($values);
        }
        
        /**
        * Создание экземпляра класса.
        * 
        * @param  array $values Список параметров запроса.
        * @return Http_Request
        */
        public static function create(array $values = array()) {
            return new self($values);
        }
        
        /**
        * Установка значения параметра.
        * 
        * @param  string $key   Название параметра.
        * @param  mixed  $value Значение параметра.
        * @return Http_Request
        */
        public function set($key, $value) {
            $this->_storage[$key] = $value;
            return $this;
        }
        
        /**
        * Установка значений нескольких параметров.
        * 
        * @param  array $values Массив пар "параметр => значение".
        * @return Http_Request
        */
        public function setMulti(array $values = array()) {
            foreach ($values as $key => $value) {
                $this->set($key, $value);
            }
            
            return $this;
        }
        
        /**
        * Установка значения параметра с использованием перегрузки атрибутов.
        * 
        * @link http://www.php.net/manual/en/language.oop5.overloading.php
        * 
        * @param  string $name  Название параметра.
        * @param  mixed  $value Значение параметра.
        * @return void
        */
        public function __set($name, $value) {
            $this->set($name, $value);
        }
        
        /**
        * Получение значения параметра с использованием перегрузки атрибутов.
        * Значения возвращаются по ссылке.
        * 
        * @link http://www.php.net/manual/en/language.oop5.overloading.php
        * 
        * @param  string $name Название параметра.
        * @return mixed
        */
        public function & __get($name) {
            if (!$this->__isset($name))
            {
                $msg = sprintf('Параметр "%s" не определён', $name);
                throw new InvalidArgumentException($msg);
            }
            
            return $this->_storage[$name];
        }
        
        /**
        * Проверка, установлено ли значение параметра или нет. Используется
        * перегрузка атрибутов.
        * 
        * @link http://www.php.net/manual/en/language.oop5.overloading.php
        * 
        * @param  string $name  Название параметра.
        * @return boolean
        */
        public function __isset($name) {
            return isset($this->_storage[$name]);
        }
        
        /**
        * Преобразование объекта в строку. Используется для отладки.
        * 
        * @return string
        */
        public function __toString() {
            return '"' . print_r($this, true) . '"';
        }
    }

?>