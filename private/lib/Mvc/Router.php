<?php
    
    /* $Id$ */

    /**
    * Класс маршрутизатора запросов. Определяет, какой обработчик надо вызывать.
    */
    class Mvc_Router {
        /**
        * Тип маршрута: статичный.
        * 
        * @var const
        */
        const ROUTE_STATIC = 'static';
        
        /**
        * Тип маршрута: на регулярных выражениях.
        * 
        * @var const
        */
        const ROUTE_REGEX  = 'regex';
        
        /**
        * Разделитель, используемый в строке запроса.
        * 
        * @var const
        */
        const URL_DELIMITER = '/';
        
        /**
        * Список маршрутов.
        * 
        * @var array
        */
        protected $_routes = array();
        
        /**
        * Метод-конструктор класса.
        * 
        * @param  array $routes Список маршрутов.
        * @return void
        */
        public function __construct(array $routes = array()) {                 
            $this->addRoutes($routes);
        }
        
        /**
        * Создание экземпляра класса.
        * 
        * @param  array $routes Список маршрутов.
        * @return Mvc_Router
        */
        public static function create(array $routes = array()) {
            return new self($routes);
        }
        
        /**
        * Добавление списка маршрутов.
        * 
        * @param  array $routes Список маршрутов.
        * @return void
        */
        public function addRoutes(array $routes = array()) {
            foreach ($routes as $route) {
                $this->addRoute($route['type'], $route['pattern'],
                                $route['handler']);
            }
        }
        
        /**
        * Добавление маршрута.
        * 
        * @param  mixed        $type Тип маршрута.
        * @param  string|array $type Шаблон строки запроса.
        * @return void
        */
        public function addRoute($type, $pattern, $handler) {
            $this->_routes[] = array(
                'type'    => $type,
                'pattern' => $pattern,
                'handler' => $handler
            );
        }
        
        /**
        * В соответствии с заданными маршрутами определяет, какому обработчику
        * надо передать управление.
        * 
        * @param  Http_Request $request Объект запроса.
        * @return array|false Указатель на обработчик либо false.
        */
        public function dispatch(Http_Request $request) {
            /**
            * @todo Делать urldecode?
            */
            /* Определяем строку запроса */
            $server = $request->server;
            if (isset($server['REDIRECT_URL'])) {
                $path = strtolower($server['REDIRECT_URL']);
            } else {
                $path = '/';
            }
            $params = array();
            
            /* Перебираем список маршрутов на совпадение со строкой запроса */
            foreach ($this->_routes as $route)
            { 
                if ($this->_match($path, $route, $params))
                {                    
                    /**
                    * Если для обработчика не задано параметров, инициализируем
                    * их пустым массивом
                    */
                    if (!isset($route['handler']['params'])) {
                        $route['handler']['params'] = array();
                    }
                    
                    /* Добавляем параметры, полученные из строки запроса */
                    $route['handler']['params'] += $params;
                    
                    /* Возвращаем указатель на обработчик */
                    return $route['handler'];
                }
            }          
            
            /* Если ни один из маршрутов не подошёл, то пробуем стандартный */
            return $this->_defaultRoute($path, $request);
        }
        
        /**
        * Сопоставление строки запроса и маршрута.
        * 
        * @param  string $path  Строка запроса.
        * @param  array $route  Параметры маршрута.
        * @param  array $params Параметры, полученные из строки запроса.
        * @return boolean
        */
        protected function _match($path, array $route, array & $params) {
            /* В зависимости от типа маршрута, вызываем соответствующий метод */
            switch ($route['type'])
            {
                case self::ROUTE_STATIC:
                    return $this->_matchStatic(
                        $path, $route['pattern'], $params
                    );
                    break;
                
                case self::ROUTE_REGEX:
                    $regex = $route['pattern']['regex'];
                    $names = $route['pattern']['params'];
                    
                    return $this->_matchRegex($path, $regex, $names, $params);
                    break;
                    
                default:
                    throw new Mvc_Router_Exception(
                        sprintf('Неизвестный тип маршрута "%s"', $route['type'])
                    );
                    break;
            }
        }
        
        /**
        * Сопоставление статичного маршрута с запросом.
        * 
        * @param  string $path    Строка запроса.
        * @param  string $pattern Шаблон запроса для маршрута.
        * @param  array  $params  Параметры, полученные из строки запроса.
        * @return boolean
        */
        protected function _matchStatic($path, $pattern, array & $params) {
            /**
            * Параметры не разбираются для статичных маршрутов, поэтому
            * возвращаем пустой массив
            */
            $params = array();
            
            /* Обрезаем с конца строки запроса символ разделителя */
            $path = rtrim($path, self::URL_DELIMITER);
            
            /* Если запрос совпадает с шаблоном, то... */
            if ($path == $pattern) {
                /* засчитываем совпадение */
                return true;
            }
            
            return false;
        }
        
        /**
        * Сопоставление маршрута на регулярном выражении с запросом.
        * 
        * @param  string $path   Строка запроса.
        * @param  string $regex  Шаблон маршрута.
        * @param  array  $names  Имена вырезаемых из запроса параметров.
        * @param  array  $params Параметры, полученные из строки запроса.
        * @return boolean
        */
        protected function _matchRegex
        (
            $path, $regex, array $names = array(),  array & $params
        )
        {
            /* Сравниваем регулярное выражение со строкой запроса */
            $regex = '#^' . $regex . '$#i';
            $num_matches = preg_match($regex, $path, $matches);
            
            /* Если строка запроса не совпала с регулярным выражением, то... */
            if ($num_matches < 1) {
                /* не засчитываем и совпадение маршрута с запросом */
                return false;
            }
            
            $params = array();
            
            /* Заполняем массив именованных параметров */
            array_shift($matches);
            foreach ($matches as $key => $param) {
                $key = (isset($names[$key]) ? $names[$key] : $key);
                $params[$key] = $param;
            }
            
            return true;
        }
        
        /**
        * Определение обработчика для запроса в соответствии со стандартным
        * маршрутом (/контроллер/действие/параметры).
        * 
        * @param  string       $path    Строка запроса.
        * @param  Http_Request $request Объект запроса.
        * @return array|boolean Указатель на обработчик либо false.
        */
        protected function _defaultRoute($path, Http_Request $request) {
            /* Удаляем разделитель с концов строки запроса */
            $path = trim($path, self::URL_DELIMITER);
            
            /* Если в ней ещё что-нибудь осталось... */
            if (strlen($path)) {
                /* разбиваем строку на части. */
                $path = explode(self::URL_DELIMITER, $path);
            } else {
                /* Иначе добавляем параметры обработчика по умолчанию */
                $path = array('index', 'index');
            }
            
            /** 
            * Если в массиве осталось меньше двух параметров, то мы не можем
            * определить обработчик.
            */
            if (sizeof($path) < 2) {
                return false;
            }
            
            /* Иначе, создаём массив с параметрами обработчика */
            $handler = array(
                /* Первый - контроллер */
                'controller' => array_shift($path),
                /* Второй - действие */
                'action' => array_shift($path),
                /* Третий - параметры запроса */
                'params' => $path
            );
            
            /* Вовзращаем указатель на обработчик */
            return $handler;
        }
    }

?>