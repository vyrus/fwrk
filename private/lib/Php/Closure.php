<?php

    /**
    * Класс замыканий функций/методов для PHP < 5.3.0.
    */
    class Php_Closure {
        /**
        * Функция, для которой создано замыкание.
        * 
        * @var callback
        */
        protected $_callback;
        
        /**
        * Список значений аргументов для функции.
        * 
        * @var array
        */
        protected $_args = array();
        
        /**
        * Создание нового замыкания.
        * 
        * @return Php_Closure
        */
        public static function create() {
            return new self();
        }
        
        /**
        * Установка функции/метода замыкания.
        * 
        * @param  callback $callback
        * @return Php_Closure Fluent interface.
        */
        public function setCallback($callback) {
            $this->_callback = $callback;
            return $this;
        }
        
        /**
        * Добавление значения аргумента для вызова функции.
        * 
        * @param  mixed $value Значение аргумента (передаётся по ссылке).
        * @return Php_Closure Fluent interface.
        */
        public function addArgument(& $value) {
            $this->_args[] = & $value;
            return $this;
        }
        
        /**
        * Вызов функции замыкания, с передачей объединённого списка аргументов
        * (установленные ранее для замыкания + переданные для этого метода).
        * 
        * @return mixed Значение, которое вернула функция/метод.
        */
        public function call() {
            $args = func_get_args();
            $args = array_merge($this->_args, $args);
            
            return call_user_func_array($this->_callback, $args);
        }
    }

?>