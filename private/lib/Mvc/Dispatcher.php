<?php
    
    /* $Id$ */

    /**
    * 
    */
    class Mvc_Dispatcher {
        /**
        * Ошибка вызова обработчика: нет ошибок.
        * 
        * @var const
        */
        const ERROR_SUCCESS = 'error-success';
        
        /**
        * Ошибка вызова обработчика: контроллер не найден.
        * 
        * @var const
        */
        const ERROR_CONTROLLER_NOT_FOUND = 'error-controller-not-found';
        
        /**
        * Ошибка вызова обработчика: действие не найдено.
        * 
        * @var const
        */
        const ERROR_ACTION_NOT_FOUND = 'error-action-not-found';
        
        /**
        * Перехватывать ли различные ошибки. Если установлено в false, то
        * будут генерироваться исключения. Если в true, то управление будет
        * передаваться соответствующему обработчичку.
        * 
        * @see self::$_error_handler
        * 
        * @var boolean
        */
        protected $_catch_errors = array(
            403 => true,
            404 => true
        );
        
        /**
        * Обработчики для ошибок.
        * 
        * @var array
        */
        protected $_error_handlers = array
        (
            403 => array(
                'controller' => 'error',
                'action'     => '403'
            ),
            
            404 => array(
                'controller' => 'error',
                'action'     => '404'
            ),
        );
        
        /**
        * Приватные обработчики, для которых производится проверка прав доступа.
        * 
        * @var array
        */
        protected $_private_paths = array();
        
        /**
        * Права доступа для различных ролей пользователей.
        * 
        * @var array
        */
        protected $_permissions = array();
        
        /**
        * Метод-конструктор класса.
        * 
        * @param  array $permissions Права на доступ.
        * @return void
        */
        public function __construct(array $permissions = array()) {
            $this->setPermissions($permissions);
        }
        
        /**
        * Создание экземпляра класса.
        * 
        * @param  array $permissions Права на доступ.
        * @return Mvc_Router
        */
        public static function create(array $permissions = array()) {
            return new self($permissions);
        }
        
        /**
        * Установка параметра, перехватывать ли заданную ошибку.
        * 
        * @param  int     $e_code Код ошибки.
        * @param  boolean $value  Значение параметра.
        * @return void
        */
        public function catchError($e_code, $value) {
            $this->_catch_errors[$e_code] = $value;
        }
        
        /**
        * Установка обработчика для заданной ошибки.
        *
        * @param  int   $e_code  Код ошибки. 
        * @param  array $handler Обработчик.
        * @return void.
        */
        public function setErrorHandler($e_code, array $handler = null) {
            if (null === $handler) {
                return;
            }
            
            $this->_error_handlers[$e_code] = $handler;
        }
        
        /**
        * Установка прав на доступ к обработчикам.
        * 
        * @param  array $permissions Права на доступ.
        * @return void 
        */
        public function setPermissions(array $permissions = array()) {
            foreach ($permissions as $user_role => $paths)
            {
                foreach ($paths as $path)
                {
                    $this->setPermission($user_role, $path);
                }
            }
        }
        
        /**
        * Установка разрешения на доступ к обработчику для заданной роли
        * пользователя.
        * 
        * @param  mixed  $user_role Роль пользователя.
        * @param  string $path      Путь в формате "контроллер/действие".
        * @return void
        */
        public function setPermission($user_role, $path) {
            $this->_private_paths[$path] = true;
            $this->_permissions[$user_role][$path] = true;
        }
        
        /**
        * Определяет с помощью роутера обработчик и пытается вызвать его.
        * 
        * @param  $request Http_Request Объект запроса.
        * @return boolean
        */
        public function dispatch(Http_Request $request) {
            /* Получаем объект роутера */
            $router = Resources::getInstance()->router;
            $links = Resources::getInstance()->links;
            
            $base_path = $links->getBasePath();
            
            /* Если не получается определить обработчик, то генерируем ошибку */
            if (false === ($handler = $router->dispatch($request, $base_path)))
            {
                $msg = 'Контроллер/действие не определены';
                $this->_raise_error(404, $msg, $request);
                
                return false;
            }
              
            /* Проверям права на доступ */
            if (false === $this->_checkPermissions($handler)) {
                $msg = 'Доступ запрещён';
                $this->_raise_error(403, $msg, $request);
            }
            
            /* Пытаемся вызывать обработчик и обрабатываем возможные ошибки */
            if (self::ERROR_SUCCESS === ($r = $this->_call($handler, $request)))
            {
                return true;
            }
            /* Контроллер не найден */
            elseif (self::ERROR_CONTROLLER_NOT_FOUND === $r->error_code)
            {
                $msg = sprintf('Не найден класс контроллера "%s"', $r->class);
                $this->_raise_error(404, $msg, $request);
                
                return false;
            }
            /* Действие не найдено */
            elseif (self::ERROR_ACTION_NOT_FOUND === $r->error_code)
            {
                $msg = 'Не найден метод "%s" в контроллере "%s"';
                $msg = sprintf($msg, $r->method, $r->class);
                $this->_raise_error(404, $msg, $request);
                
                return false;
            }
        }
        
        /**
        * Проверка прав на доступ к контроллеру/действию.
        * 
        * @param  array $handler Обработчик запроса.
        * @return boolean
        */
        protected function _checkPermissions(array $handler) {
            $path = $handler['controller'] . '/' . $handler['action'];
            
            /* Если путь не общедоступный */
            if (isset($this->_private_paths[$path]))
            {
                $user = Model_User::create();
                
                /* Проверяем, авторизован ли пользователь */
                if (false === ($udata = $user->getAuth())) {
                    return false;
                }
                
                $udata = (object) $udata;
                $perms = & $this->_permissions[$udata->role];
                
                /* Проверяем, установлено ли разрешение на доступ */
                if (!isset($perms[$path])) {
                    return false;
                }
            }
            
            return true;
        }
        
        /**
        * Вызов обработчика. При ошибке возвращает экземпляр stdClass'а с
        * параметрами ошибки. Либо self::ERROR_SUCCESS, если повезёт =).
        * 
        * @param  array        $handler Параметры обработчика.
        * @param  Http_Request $request Объект запроса.
        * @return mixed
        */
        protected function _call(array $handler, Http_Request $request) {
            /* Если в объекте-запросе нет раздела роутера, создаём его */
            if (!isset($request->_router)) {
                $request->_router = array();
            }
            /* Сохраняем для запроса назначенный обработчик */
            $request->_router['handler'] = $handler; 
            
            /* Определяем название класса контроллера */
            $name = $handler['controller'];
            /* Переводим символы после подчёркиваний в верхний регистр, чтобы
            имена классов определялись корректно. Иначе на *nix-системах файлы с
            классами не подгружаются. */
            /* education_programs -> education_Programs */
            $name = preg_replace('/_([a-z]{1})/e', '"_".ucfirst("$1")', $name);
            /* education_Programs -> Education_Programs */
            $name = ucfirst($name);
            /* Education_Programs -> Controller_Education_Programs */
            $class = 'Controller_' . ucfirst($name);

            /* Если такой класс не найден, возвращаем ошибку */
            if (!class_exists($class /* $autoload = true */))
            {
                /* Заполняем объект параметрами ошибки */
                $r = new stdClass();        
                $r->error_code = self::ERROR_CONTROLLER_NOT_FOUND;
                $r->class = $class;
                
                return $r;
            }
            
            /* Определяем нужный метод в классе контроллера */
            $method = 'action_' . $handler['action'];
            
            /* Если такой метод недоступен, возвращаем ошибку */
            if (!$this->_isCallable($class, $method))
            {
                /* Заполняем объект параметрами ошибки */
                $r = new stdClass();
                $r->error_code = self::ERROR_ACTION_NOT_FOUND;
                $r->class = $class;
                $r->method = $method;
                
                return $r;
            }

            /* Инициализируем контроллер */
            $controller = new $class($request);
            
            /* Настраиваем параметры вызова */
            $callback = array($controller, $method);
            $params = array();
            
            if (!empty($handler['params'])) {
                $params[] = $handler['params'];
            }
            
            /* Вызываем обработчик */
            call_user_func_array($callback, $params);
            
            return self::ERROR_SUCCESS;
        }
        
        /**
        * Определение доступности метода в классе. Метод доступен, если он объявлен
        * в коде класса со спецификатором public.
        * 
        * @param  string $class  Название класса.
        * @param  string $method Название метода.
        * @return boolean
        */
        protected function _isCallable($class, $method) {
            return in_array($method, get_class_methods($class));
        }
        
        /**
        * Метод для обработки ошибок. Если в настройках не задан перехват данной
        * ошибки, то будет сгенерировано исключение. Иначе метод попытаеся 
        * вызвать заданный обработчик для ошибки.
        * 
        * @param  int          $code    Код ошибки.
        * @param  string       $reason  Причина возникновения ошибки.
        * @param  Http_Request $request Объект запроса.
        * @return
        */
        protected function _raise_error($code, $reason, Http_Request $request) {
            /* Если выключен перехват ошибки */
            if (!$this->_catch_errors[$code]) {
                /* Генерируем исключение */
                throw new Mvc_Router_Exception($reason);
            }
            
            /* Вызываем обработчик ошибки */
            $r = $this->_call($this->_error_handlers[$code], $request);
            
            /* Если не удалось вызывать обработчик */
            if (self::ERROR_SUCCESS !== $r) {
                /* Генерируем исключение */
                throw new Mvc_Router_Exception($reason);
            }
        }
    }

?>