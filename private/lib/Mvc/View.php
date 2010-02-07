<?php
  
    /* $Id$ */
    
    /**
    * Класс для отображения шаблонов.
    */
    class Mvc_View {
        /**
        * Расширение для файлов шаблонов по умолчанию.
        * 
        * @var const
        */
        const DEFAULT_TPL_EXTENSION = 'tpl';
        
        /**
        * Путь до директории с шаблонами.
        * 
        * @var string
        */
        protected $_templates_path;
        
        /**
        * Путь до директории с макетами.
        * 
        * @var string
        */
        protected $_layouts_path;
        
        /**
        * Расширение файлов шаблонов (без ".").
        * 
        * @var string
        */
        protected $_tpl_extension;
        
        /**
        * Директория с элементами для шаблонов.
        * 
        * @var string
        */
        protected $_elements_path;
        
        /**
        * Установленный макет для вывода шаблона.
        * 
        * @var string
        */
        protected $_layout = false;
        
        /**
        * Установленное название шаблона.
        * 
        * @var string
        */
        protected $_template;
        
        /**
        * Список переменных шаблона.
        * 
        * @var string
        */
        protected $_vars = array();
        
        /**
        * Метод-конструктор класса.
        * 
        * @param  string $templates_path Путь к файлам шаблонов.
        * @param  string $layouts_path   Путь к файлам макетов.
        * @param  string $tpl_extension  Расширение файлов шаблонов.
        * @return void
        */
        public function __construct
        (
            $templates_path, $layouts_path = null, $elements_path = null,
            $tpl_extension = self::DEFAULT_TPL_EXTENSION
        )
        {
            $this->setTemplatesPath($templates_path);
            $this->setTemplatesExtension($tpl_extension);
            
            if (null !== $layouts_path) {
                $this->setLayoutsPath($layouts_path);
            }
            
            if (null !== $elements_path) {
                $this->setElementsPath($elements_path);
            }
        }
        
        /**
        * Статичный метод для создания экземпляра класса
        * 
        * @param  string $templates_path Путь к файлам шаблонов.
        * @param  string $layouts_path   Путь к файлам макетов.
        * @param  string $tpl_extension  Расширение файлов шаблонов.
        * @return object
        */
        public static function create
        (
            $templates_path, $layouts_path = null, $elements_path = null,
            $tpl_extension = self::DEFAULT_TPL_EXTENSION
        )
        {
            return new self(
                $templates_path, $layouts_path, $elements_path, $tpl_extension
            );
        }
        
        /**
        * Установка пути к файлам шаблонов.
        * 
        * @param  string $path Путь к файлам шаблонов.
        * @return Mvc_View
        * @throws Mvc_View_Exception Если указанная директория не найдена.
        */
        public function setTemplatesPath($path) {
            if (!file_exists($path))
            {
                $msg = sprintf('Не найдена директория шаблонов "%s"', $path);
                throw new Mvc_View_Exception($msg);
            }
            
            $this->_templates_path = $path;
            
            return $this;
        }
        
        /**
        * Установка расширения файлов шаблонов.
        * 
        * @param  string $ext Расширение файлов шаблонов.
        * @return Mvc_View
        */
        public function setTemplatesExtension($ext) {
            $this->_tpl_extension = $ext;
        }
        
        /**
        * Установка пути к файлам макетов.
        * 
        * @param  string $path Путь к файлам макетов.
        * @return Mvc_View
        * @throws Mvc_View_Exception Если указанный путь не найден.
        */
        public function setLayoutsPath($path) {
            if (!file_exists($path))
            {
                $msg = sprintf('Не найдена директория макетов "%s"', $path);
                throw new Mvc_View_Exception($msg);
            }
            
            $this->_layouts_path = $path;
            
            return $this;
        }
        
        /**
        * Установка пути к элементам шаблонов.
        * 
        * @param  string $path Путь до директории.  
        * @return Mvc_View
        * @throws Mvc_View_Exception Если указанный путь не найден.
        */
        public function setElementsPath($path) {
            if (!file_exists($path))
            {
                $msg = sprintf('Не найдена директория элементов "%s"', $path);
                throw new Mvc_View_Exception($msg);
            }
            
            $this->_elements_path = $path;
            
            return $this;
        }
        
        /**
        * Установка макета для отображения шаблона.
        * 
        * @param  string $layout Название файла макета (без расширения).
        * @return Mvc_View
        */
        public function setLayout($layout) {
            $this->_layout = $layout;
            
            return $this;
        }
        
        /**
        * Установка шаблона для отображения.
        * 
        * @param  string $tpl Название файла шаблона (без расширения).
        * @return Mvc_View
        * @throws Mvc_View_Exception Если файл с шаблоном не найден.
        */
        public function setTemplate($tpl) {
            /* Определяем полный путь до файла шаблона */
            $tpl_path = $this->_templates_path .
                        $tpl .
                        '.' . $this->_tpl_extension;
            
            /* Проверям наличие такого файла */            
            if (!file_exists($tpl_path))
            {
                $msg = sprintf('Не найден файл шаблона "%s"', $tpl_path); 
                throw new Mvc_View_Exception($msg);
            }
            
            $this->_template = $tpl_path;
            
            return $this;
        }
        
        /**
        * Установка значения переменной шаблона.
        * 
        * @param  string $name  Имя переменной.
        * @param  mixed  $value Значение переменной.
        * @return Mvc_View
        */
        public function set($name, $value) {
            $this->_vars[$name] = $value;
            
            return $this;
        }
        
        /**
        * Установка значений нескольких переменных шаблона.
        * 
        * @param  array $vars Массив пар "переменная => значение".
        * @return Mvc_View
        */
        public function setVars(array $vars = array()) {
            foreach ($vars as $name => $value) {
                $this->set($name, $value);
            }
                
            return $this;
        }
        
        /**
        * Сборка и вывод шаблона.
        * 
        * @param  boolean $exit   Закончить ли работу скрипта после вывода
        *                         шаблона (из самих шаблонов этот параметр не
        *                         действует).
        * @param  boolean $return Не выводить шаблон, вернуть его в результате.
        */
        public function render($exit = true, $return = false) {
            /* Включаем буферизацию вывода */
            ob_start();
                /* Выводим шаблон в буфер */
                include $this->_template;
            /* Получаем из буфера содержимое шаблона */
            $out = ob_get_clean();
            
            /* Если установлен макет, то... */                         
            if (false !== $this->_layout)
            {
                /* создаём объект шаблона для макета и...*/
                $layout = self::create(
                    $this->_layouts_path, null, $this->_elements_path,
                    $this->_tpl_extension
                );
                
                /* собираем макет, передав в него содержимое текущего шаблона */
                $out = $layout->setTemplate($this->_layout)
                              ->setVars($this->_vars)
                              ->set('content', $out)
                              ->render(false, true);
            }
            
            /* Если включен возврат шаблона, то возвращаем его содержимое */
            if ($return) {
                return $out;
            } 
            /* Иначе просто выводим */
            else {
                echo $out;
            }
            
            if($exit) exit();
        }
        
        /**
        * Сборка и отображение элемента шаблона.
        * 
        * @param  string $element_name Название элемента.
        * @return void
        */
        public function renderElement($element_name) {
            /* Создаём объект шаблона для элемента */
            $element = self::create(
                $this->_elements_path, null, null, $this->_tpl_extension
            );
            
            /* Настраиваем его, собираем элемент и выводим его */
            $element->setTemplate($element_name)
                    ->setVars($this->_vars)
                    ->render(false);
        }
        
        /**
        * Получение значения переменной шаблона с использованием перегрузки
        * атрибутов.
        * 
        * @link http://www.php.net/manual/en/language.oop5.overloading.php
        * 
        * @param  string $name Имя переменной.
        * @return mixed
        */
        public function __get($name) {
            if (!$this->__isset($name))
            {
                $msg = sprintf('Переменная шаблона "%s" не определена', $name);
                throw new InvalidArgumentException($msg);
            }
            
            return $this->_vars[$name];
        }
        
        /**
        * Установка значения переменной шаблона с использованием перегрузки
        * атрибутов.
        * 
        * @link http://www.php.net/manual/en/language.oop5.overloading.php
        * 
        * @param  string $name  Имя переменой.
        * @param  mixed  $value Значение переменной.
        * @return void
        */
        public function __set($name, $value) {
            $this->set($name, $value);
        }
        
        /**
        * Проверка, установлено ли значение переменной шаблона или нет.
        * Используется перегрузка атрибутов.
        * 
        * @link http://www.php.net/manual/en/language.oop5.overloading.php
        * 
        * @param  string $name  Имя переменной.
        * @return boolean
        */
        public function __isset($name) {
            return isset($this->_vars[$name]);
        }
    }
  
?>