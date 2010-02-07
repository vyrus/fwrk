<?php

    /* $Id$ */
    
    /**
    * Класс инициализации для установки общих настроек PHP.
    */
    class Init {
        /**
        * Флаг, обозначающий, проверять ли при объявлении константы
        * существование константы с таким же именем. 
        * 
        * @var boolean 
        */
        public static $check_if_const_defined = true;
        
        /**
        * Объявление константы.
        * 
        * @param  string $name  Имя константы.
        * @param  mixed  $value Значение.
        * @return boolean
        */
        public static function define($name, $value) {
            /* Если включена проверка на существование константы, проверяем */
            if (self::$check_if_const_defined && defined($name)) {          
                $msg = 'Константа уже объявлена: "' . $name . '"';                  
                throw new Init_Exception($msg);
            }
            
            return define($name, $value);
        }
        
        /**
        * Вспомогательный метод, для изменения опций конфигурации PHP. 
        * 
        * @param  string  $option Название опции.
        * @param  mixed   $value  Новое значение.
        * @param  boolean $check  Проверять ли успешность установки значения.
        * @return mixed Старое значение опции или false.
        * @throws Init_Exception Если включена проверка и произошла ошибка.
        */
        public static function iniSet($option, $value, $check = true) {
            $result = ini_set($option, $value);
            
            if ($check && false === $result) {
                $msg = 'Не удалось установить значение "%s" для опции "%s"';
                throw new Init_Exception( sprintf($msg, $value, $option) );
            }
            
            return $result;
        }
        
        
        /**
        * Добавление новых элементов в список директорий, в которых будет
        * производиться поиск подключаемых файлов.
        * 
        * @param  array $paths Список добавляемых директорий.
        * @return mixed Старое значение опции или false.
        */
        public static function setIncludePath($paths = array()) {
            /* Добавляем к списку с новыми папками, строку со списком старых */
            $paths[] = get_include_path();
            
            /* Сворачиваем массив в строку, разделяя элементы PATH_SEPARATOR */
            $paths = implode(PATH_SEPARATOR, $paths);
            $paths .= PATH_SEPARATOR;
            
            return self::iniSet('include_path', $paths); 
        }
        
        /**
        * Установка уровня ошибок, о которых будет сообщать PHP.
        * 
        * @param  int $level Новый уровень.
        * @return mixed Старое значение уровень или false.
        */
        public static function setErrorReporting($level) {
            return self::iniSet('error_reporting', $level);
        }
           
        /**
        * Установка опции, выводить ли ошибки на экран или нет.
        * 
        * @param  boolean $value Новое значение.
        * @return mixed Предыдущее значение опции или false.
        */                      
        public static function displayErrors($value) {
            return self::iniSet('display_errors', $value);
        }           
        
        /**
        * Установка опции, записывать ли сообщения об ошибках в лог.
        * 
        * @param  boolean $value Новое значение.
        * @return mixed Предыдущее значение опции или false.
        */
        public static function logErrors($value) {
            return self::iniSet('log_errors', $value);
        }
        
        /**
        * Установка файла, куда будут записываться сообщения об ошибках.
        * 
        * @param  string $value Путь до файла.
        * @return mixed Предыдущее значение опции или false.
        */
        public static function setErrorLog($value) {
            return self::iniSet('error_log', $value, false);
        }
        
        /**
        * Установка перехватчика ошибок, который выводить их на экран в удобном
        * виде.
        * 
        * @return void
        */                      
        public static function setupErrorHandler() {
            require_once 'ErrorReporter.php';
            
            $class = 'ErrorReporter';
            $callback['error']     = array($class, 'errorHandler');
            $callback['exception'] = array($class, 'exceptionHandler');
            
            set_error_handler($callback['error'], ini_get('error_reporting'));                      
            set_exception_handler($callback['exception']);
        }
        
        /**
        * Установка локали (только для категорий LC_COLLATE, LC_CTYPE и LC_TIME)
        * 
        * @see setlocale()
        * @return void
        */
        public static function setLocale($locale) {
            setlocale(LC_COLLATE, $locale);
            setlocale(LC_CTYPE,   $locale);
            setlocale(LC_TIME,    $locale);    
        }
        
        /**
        * Установка часового пояса.
        * 
        * @param  string $zone Идентификатор.
        * @return boolean
        */
        public static function setTimezone($zone) {
            if(phpversion() >= 5) return date_default_timezone_set($zone);
        }
        
        /**
        * Установка максимального времени выполнения скрипта.
        * 
        * @param  int $sec Время в секундах.
        * @return mixed Предыдущее значение опции или false.
        */
        public static function setMaxExecutionTime($sec) {
            return self::iniSet('max_execution_time', $sec);
        }
        
        /**
        * Включение обработки assert'ов с прерыванием выполнения скрипта при
        * невыполнении условия.
        * 
        * @see assert_options()
        * 
        * @return void.
        */
        public static function enableAssertions() {
            assert_options(ASSERT_ACTIVE, 1);
            assert_options(ASSERT_BAIL,   1);
        }
    }

?>
