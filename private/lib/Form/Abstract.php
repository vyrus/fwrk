<?php
    
    /* $Id$ */

    /**
    * Класс для упрощения обработки HTML-форм.
    */
    class Form_Abstract {
        /**
        * Метод HTTP-протокола: Get.
        * 
        * @var const
        */
        const METHOD_GET = 'get';
        
        /**
        * Метод HTTP-протокола: Post.
        * 
        * @var const
        */
        const METHOD_POST = 'post';
        
        /**
        * Тип валидатора поля: на основе регулярного выражения.
        * 
        * @var const
        */
        const VALIDATOR_REGEX = 'regex';
        
        /**
        * Тип валидатора поля: на основе функции.
        * 
        * @var const
        */
        const VALIDATOR_FUNCTION = 'func';
        
        /**
        * Список установленных ошибок в заполнении полей формы.
        * 
        * @var array
        */
        protected $_errors = array();
        
        /**
        * Значения полей, которые прошли проверку.
        * 
        * @var array
        */
        protected $_values = array();
        
        /**
        * Кэш для запрошенных объектов-полей.
        * 
        * @see self::__get()
        * 
        * @var array
        */
        protected $_cache = array();
        
        /**
        * Последний использованный идентификатор поля. Используется для
        * поддержки fluent interface при описании структуры формы.
        * 
        * @var string
        */
        protected $_last_id;
        
        /**
        * Флаг, обозначающий, прошли ли все переданные значения формы проверку
        * или нет.
        * 
        * @var boolean
        */
        protected $_valid;
        
        /**
        * Значение атрибута "action" тега "form".
        * 
        * @var string
        */
        protected $_action;
        
        /**
        * Значение атрибута "method" тега "form".
        * 
        * @var string
        */
        protected $_method = self::METHOD_POST;
        
        /**
        * Список полей формы и их параметров.
        * 
        * @var array
        */
        protected $_fields = array();
        
        /**
        * Установка значения атрибута "action".
        * 
        * @param  string $action Значение атрибута.
        * @return Form_Abstract  Fluent interface.
        */
        public function setAction($action) {
            $this->_action = $action;
            return $this;
        }
        
        /**
        * Установка значения атрибута "method".
        * 
        * @param  string $method Значение атрибута.
        * @return Form_Abstract  Fluent interface.
        */
        public function setMethod($method) {
            $this->_method = $method;
            return $this;
        }                
        
        /**
        * Добавление нового поля. По умолчанию, если не указывать второй
        * аргумент, имя поля устанавливается равным идентификатору.
        * 
        * @param  string $id    Идентификатор поля.
        * @param  string $name  Имя поля (значение атрибута "name").
        * @return Form_Abstract Fluent interface.
        */
        public function addField($id, $name = null) {  
            /* Определяем идентификатор и добавляем поле */
            $name = (null !== $name ? $name : $id);
            $this->_fields[$id]['name'] = $name;
            
            /* Запоминаем использованный идентификатор */
            $this->_setLastId($id);
            
            return $this;
        }
        
        /**
        * Установка валидатора для проверки значений поля. По умолчанию значение
        * идентификатор поля устанавливается в предыдущий использованный
        * идентификатор. Типа валидатора по умолчанию - на основе регулярных
        * выражений.
        * 
        * @param  string $param Параметр валидатора.
        * @param  mixed  $type  Тип валидатора.
        * @param  string $id    Идентификатор поля.
        * @return Form_Abstract Fluent interface.
        */
        public function setValidator
        (
            $param, $type = self::VALIDATOR_REGEX, $id = null
        )
        {
            /* Определяем идентификатор */
            $id = (null !== $id ? $id : $this->_getLastId());
            
            /* Заполняем параметры валидатора в зависимости от типа */
            $validator['type'] = $type;
            switch ($type)
            {
                case self::VALIDATOR_REGEX:
                    $validator['regex'] = $param;
                    break;
                    
                case self::VALIDATOR_FUNCTION:
                    $validator['callback'] = $param;
                    break;
                    
                default:
                    $msg = 'Неизвестный тип валидатора: ' . $type;
                    throw new InvalidArgumentException($msg);
                    break;
            }
            
            $this->_fields[$id]['validator'] = $validator;
            
            /* Запоминаем использованный идентификатор */
            $this->_setLastId($id);
            
            return $this;
        }
        
        /**
        * Установка теста ошибки, которая будет выдываться, если значение поля
        * не прошло проверку. По умолчанию значение идентификатор поля
        * устанавливается в предыдущий использованный идентификатор.
        * 
        * @param  string $error Текст ошибки.
        * @param  string $id    Идентификатор поля.
        * @return Form_Abstract Fluent interface.
        */
        public function setError($error, $id = null) {
            /* Определяем индентификатор и устанавливаем ошибку */
            $id = (null !== $id ? $id : $this->_getLastId());
            $this->_fields[$id]['error'] = $error;
            
            /* Запоминаем использованный идентификатор */
            $this->_setLastId($id);
                        
            return $this;
        }
        
        /**
        * Возвращает значение атрибута "action".
        * 
        * @return string
        */
        public function action() {
            return $this->_action;
        }
        
        /**
        * Вовзращает значение атрибута "method".
        * 
        * @return string
        */
        public function method() {
            return $this->_method;
        }
        
        /**
        * Проверка значений формы из заданного объекта-запроса. Возвращает true,
        * если все значения прошли проверку. Значения, прошедшие проверку
        * запоминаются, к ним можно будет обратиться через $form->поле->value.
        * Для полей, значения которых не прошли проверку, устанавливаются
        * ошибки, к котором также можно обратиться через $form->поле->error.
        * 
        * @param  Http_Request $request
        * @return boolean
        * @todo Split to attachRequest and validate.
        */
        public function validate(Http_Request $request) {
            /**
            * @todo Проверять на наличие нужно параметра запроса?
            */
            /* Берём значения для формы из запроса */
            $values = $request->{$this->method()};
            
            /* Перебираем все поля */
            foreach ($this->_fields as $id => $field)
            {
                $name = $field['name'];
                $value = (isset($values[$name]) ? $values[$name] : '');

                /* Если не установлен валидатор, просто запоминаем значение */
                if (!isset($field['validator'])) {
                    $this->setValue($id, $value);
                    continue;
                }
                
                $validator = $field['validator'];
                
                /* Если значение введено некорректно, то... */
                if (!$this->_isFieldValid($value, $validator)) {
                    /* устанавливаем текст ошибки для поля */
                    $this->setValidationError($id, $field['error']);
                } else {
                    /* Иначе запоминаем значение */
                    $this->setValue($id, $value);
                }
            }
            
            /* Если ни одной ошибки не установлено - форма заполнена верно */
            $this->_valid = !$this->_hasErrors();
            
            return $this->_valid;
        }
        
        /**
        * Устанавливает результат проверки значений полей в false. Может
        * применяться для дополнительных проверок полей.
        * 
        * @return void
        */
        public function invalidate() {
            $this->_valid = false;
        }
        
        /**
        * Возвращает результат проверки значений полей.
        * 
        * @return boolean
        */
        public function valid() {
            return $this->_valid;
        }
        
        /**
        * Установка значения поля с защитой от XSS-атак.
        * 
        * @param  string $id    Идентифкатор поля.
        * @param  mixed  $value Значение.
        * @return Form_Abstract  Fluent interface.
        */
        public function setValue($id, $value) {
            $this->_values[$id] = $this->_preventXss($value);
            /* Не забываем удалить закэшированный объект поля */
            $this->_deleteFromCache($id);

			return $this;
        }
        
        /**
        * Установка текста ошибки для поля. Может применяться для дополнительных
        * проверок полей.
        * 
        * @param  string $id    Идентификатор поля.
        * @param  string $error Текст ошибки.
        * @return void
        */
        public function setValidationError($id, $error) {
            $this->_errors[$id] = $error;
            /* Не забываем удалить закэшированный объект поля */
            $this->_deleteFromCache($id);
        }
        
        /**
        * Установка последнего использованного идентификатора поля. Используется
        * для реализации fluent interface.
        * 
        * @param  string $id Идентификатор поля.
        * @return void
        */
        protected function _setLastId($id) {
            $this->_last_id = $id;
        }
        
        /**
        * Получение последнего использованного индентификатора поля.
        * Используется для реализации fluent interface.
        * 
        * @return string
        */
        protected function _getLastId() {
            return $this->_last_id;
        }
        
        /**
        * Проверка значения поля на корректность.
        * 
        * @param  mixed $value     Значение поля.
        * @param  array $validator Параметры валидатора.
        * @return boolean
        */
        protected function _isFieldValid($value, array $validator) {
            /* В зависимости от типа выполняем проверку значения */
            switch ($validator['type'])
            {
                case self::VALIDATOR_REGEX:
                    return $this->_validateRegex($value, $validator['regex']);
                    break;
                    
                case self::VALIDATOR_FUNC:
                    /**
                    * @todo Реализовать этот метод проверки.
                    */
                    throw new Exception('Plz, implement me!');
                    break;
                    
                default:
                    $msg = 'Неизвестный тип валидатора: ' . $validator['type'];
                    throw new InvalidArgumentException($msg);
                    break;
            }
        } 
        
        /**
        * Проверка значения поля на корректность с использованием регулярного
        * выражения.
        * 
        * @param  mixed  $value Значение поле.
        * @param  string $regex Регулярное выражение.
        * @return boolean
        */
        protected function _validateRegex($value, $regex) {
            $num_matches = preg_match($regex, $value);
            $result = ($num_matches > 0);
            
            return $result;
        }
        
        /**
        * Проверяет, установлены ли какие-либо ошибки для полей.
        *                
        * @return boolean
        */
        protected function _hasErrors() {
            return (sizeof($this->_errors) > 0);
        }
        
        /**
        * Проверяет, установлена ли для поля ошибка.
        * 
        * @param  string $id Идентификатор поля.
        * @return boolean
        */
        public function hasError($id) {
            return isset($this->_errors[$id]);
        }
        
        /**
        * Защита от XSS, заменяет опасные символы на HTML-entities.
        * 
        * @param  mixed $var Значение поля.
        * @return mixed
        */
        protected function _preventXss($var) {
            /**
            * Если включено автоматическое экранирование значений запроса,
            * отменяем его
            */
            if (get_magic_quotes_gpc()) $var = stripslashes($var);
            $var = htmlentities($var, ENT_QUOTES, 'UTF-8');
        
            return $var; 
        }
        
        /**
        * Добавление объекта с актуальной информацией о поле в кэш.
        * 
        * @see self::__get()
        * 
        * @param  string   $id    Идентификатор поля.
        * @param  stdClass $field Объект поля.
        * @return
        */
        protected function _addToCache($id, stdClass $field) {
            $this->_cache[$id] = $field;    
        }
        
        /**
        * Удаление из кэша объектов-полей.
        * 
        * @see self::__get()
        * 
        * @param  string $id
        * @return void
        */
        protected function _deleteFromCache($id) {
            if (isset($this->_cache[$id])) {
                unset($this->_cache[$id]);
            }
        }
        
        /**
        * Получение сведений о полях. Для каждого запрошенного поля возвращается
        * объект класса stdClass, который имеет следующие атрибуты:
        *     name  - название поле (значение атрибута "name");
        *     value - текущее значение поля (если это значение прошло проверку);
        *     error - текст ошибки (если значение поля не прошло проверку).
        *     
        * @link http://www.php.net/manual/en/language.oop5.overloading.php
        * 
        * @param  string $id Идентификатор поля.
        * @return stdClass
        */
        public function __get($id) {
            /* Проверяем наличие запрашиваемого поля */
            if (!$this->__isset($id)) {
                $msg = sprintf('Поле с идентификатором "%s" не найдено', $id);
                throw new InvalidArgumentException($msg);
            }
            
            /* Если объект с информацией о поле есть в кэше, возвращаем его */
            if (isset($this->_cache[$id])) {
                return $this->_cache[$id];
            }
            
            /* Заполняем массив информацией о поле */
            $field_rec = $this->_fields[$id];
            $field = array('name' => $field_rec['name']);
            
            /* Если есть ошибки для этого поля, добавляем в массив */
            if (isset($this->_errors[$id])) {
                $field['error'] = $this->_errors[$id];
            }
            
            /* Если есть значения для поля - в массив */
            $field['value'] = '';
            if (isset($this->_values[$id])) {
                $field['value'] = $this->_values[$id];
            }
            
            /* Преобразуем массив в объект */
            $field = (object) $field;
            /* Заносим в кэш */
            $this->_addToCache($id, $field);
            
            return $field;
        }
        
        /**
        * Проверка наличия описания поля по его идентификатору.
        * 
        * @link http://www.php.net/manual/en/language.oop5.overloading.php
        * 
        * @param  string $id Идентификатор поля.
        * @return boolean
        */
        public function __isset($id) {
            return isset($this->_fields[$id]);
        }
        
    }

?>