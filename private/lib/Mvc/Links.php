<?php

    /* $Id$ */

    /**
    * Класс менеджера ссылок. По списку маршрутов позволяет получать ссылки на 
    * страницы.
    */
    class Mvc_Links {
        /**
        * Список маршрутов, сохранённых по ключу - алиасу маршрута.
        * 
        * @var array
        */
        protected $_routes = array();
        
        /**
        * Базовый путь до директории, из которой работает движок.
        */
        protected $_base_path;
        
        /**
        * Создание объекта менеджера ссылок.
        * 
        * @return Mvc_Links
        */
        public static function create() {
            return new self();
        }
        
        /**
        * Установка базового адреса. Производится для определения пути к движку 
        * относительно корня домена.
        * 
        * @return Mvc_Links Fluent interface.
        */
        public function setBaseUrl($url) {
            /* Разбиваем адрес на части */
            $url = parse_url($url);
            /* И если в адресе указан путь, запоминаем его */
            $this->_base_path = (isset($url['path']) ? $url['path'] : '');
            
            return $this;
        }
        
        /**
        * Возвращает базовый путь на сайте.
        * 
        * @return string
        */
        public function getBasePath() {
            return $this->_base_path;
        }
        
        /**
        * Добавление нескольких маршрутов.
        * 
        * @param  array $routes
        * @return Mvc_Links Fluent interface.
        */
        public function addRoutes(array $routes = array()) {
            foreach ($routes as $route) {
                $this->addRoute($route);
            }
            
            return $this;
        }
        
        /**
        * Добавление маршрута.
        * 
        * @param  array $route
        * @return Mvc_Links Fluent interface.
        */
        public function addRoute(array $route) {
            $route = (object) $route;
            
            /* Если для маршрута задан алиас, */
            if (isset($route->alias)) {
                /* то используем его */
                $alias = $route->alias;
            } else {
                /* иначе составляем алиас по имени контроллера и действия */
                $alias = sprintf('%s:%s', $route->handler['controller'],
                                          $route->handler['action']);
            }
            
            /* Запоминаем маршрут по его алиасу */
            $this->_routes[$alias] = $route;
            
            return $this;
        }
        
        /**
        * Определение пути к файлу с учётом базового пути движка.
        * 
        * @param  string $path
        * @return string
        */
        public function getPath($path) {
            return $this->getBasePath() . $path;
        }
        
        /**
        * Получение ссылки по алиасу маршрута и списку параметров. Можно 
        * опускать значения крайних справа в шаблоне параметров.
        * 
        * @param  string $alias  Алиас маршрута.
        * @param  array  $params Значения параметров маршрута.
        * @param  string $ending Окончание ссылки (по умолчанию - "/").
        * @return string
        * @throws InvalidArgumentException Если не найден маршрут с указанным алиасом.
        * @throws Mvc_Links_Exception Если передан маршрут неизвестного типа.
        */
        public function get(
            $alias, array $params = array(), $ending = Mvc_Router::URL_DELIMITER
        ) {
            /* Проверяем наличие маршрута */
            if (!isset($this->_routes[$alias])) {
                /* И порождаем исключение, если его нет */
                $e = sprintf('Не найден маршрут с алиасом "%s"', $alias);
                throw new InvalidArgumentException($e);
            }
            
            /* Берём маршрут */
            $route = $this->_routes[$alias];
            
            /* И в зависимости от типа получаем нужный путь */
            switch ($route->type)
            {
                case Mvc_Router::ROUTE_STATIC:
                    $path = $this->_getStatic($route);
                    break;
                    
                case Mvc_Router::ROUTE_REGEX:
                    $path = $this->_getRegex($route, $params);
                    break;
                    
                default:
                    $e = sprintf('Неизвестный тип маршрута "%s"', $route->type);
                    throw new Mvc_Links_Exception($e);
                    break;
            }
            
            /* Обрезаем с конца строки слеши (if any) */
            $path = rtrim($path, Mvc_Router::URL_DELIMITER);
            
            /* Добавляем к пути базовую часть и окончание и возращаем */
            return $this->getBasePath() . $path . $ending;
        }
        
        /**
        * Получение пути ссылки по статичному маршруту.
        * 
        * @param  stdClass $route
        * @return string
        */
        protected function _getStatic(stdClass $route) {
            return $route->pattern;
        }
        
        /**
        * Получение пути ссылки по маршруту на регулярных выражениях.
        * 
        * @todo Делать проверку соответствия переданных значений регексам?
        * 
        * @param  stdClass $route  Маршрут.
        * @param  array    $params Значения параметров маршрута.
        * @return string
        */
        protected function _getRegex(stdClass $route, array $params = array()) {
            /* Регулярка для нахождения capturing subpatterns */
            $regex = 
            '/
                \(
                    [^?]{1} # Первый символ должен быть не знаком вопроса
                    [^\)]+  # А дальше - всё что душе угодно :)
                \)
                (?:\?)? # В конце вполне может стоят вопрос
            /ixu';
            
            /* Шаблон маршрута (регулярка + список параметров )*/
            $pattern = (object) $route->pattern;
            
            /* Ставим слеши перед необязательными параметрами, лишние потом 
            уберутся */
            $pattern->regex = str_replace('(?:/)?', '/', $pattern->regex);
            
            /* Создаём замыкание callback'а, чтобы передать ему дополнительные 
            аргументы */
            $closure = Php_Closure::create();
            $closure->setCallback(array($this, '_callback'))
                    /* названия параметров маршрута */
                    ->addArgument($pattern->params)
                    /* значения параметров */
                    ->addArgument($params);
                                
            /* Определяем callback на класс замыкания */
            $callback = array($closure, 'call');
            
            /* Обрабатываем шаблон маршрута, вставляя значения параметров */
            $path = preg_replace_callback($regex, $callback, $pattern->regex);
            
            return $path;
        }
        
        /**
        * Callback для вставки значений параметров в шаблон маршрута.
        * 
        * @param  array $params  Названия параметров (передаётся по ссылке и модифицируется).
        * @param  array $values  Список значений параметров (параметр => значение).
        * @param  array $matches Совпавшие с регулярным выражением элементы строки.
        * @return string Значение очередного параметра для подстановки в шаблон.
        */
        public function _callback(array & $params, array $values, $matches) {
            /* Выталкиваем из начала массива название очередного параметра */
            if (null === ($name = array_shift($params))) {
                /* А если параметров не осталось, то выходим */
                return '';
            }
            
            /* Подставляем значение параметра, если оно задано */
            $replace = (isset($values[$name]) ? $values[$name] : '');
            
            return $replace;
        }
    }

?>