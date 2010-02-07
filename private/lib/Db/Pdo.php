<?php

    /* $Id$ */

    /**
    * Класс для работы с БД. Является обёрткой для PDO.
    * 
    * @link http://www.php.net/manual/en/book.pdo.php
    */
    class Db_Pdo extends PDO {
        /**
        * Метод-конструктор класса.
        * 
        * @param  string $dsn            Информация для соединения с БД.
        * @param  string $username       Имя пользователя.
        * @param  string $password       Пароль.
        * @param  array  $driver_options Опции, специфичные для драйверов.
        * @return void
        */
        public function __construct
        (
            $dsn, $username = null, $password = null,
            array $driver_options = array()
        )
        {
            /**
            * В опции драйвера добавляем параметр - имя класса запросов, чтобы
            * задействовать обёртку для стандартного класса PDOStatement.
            */
            $driver_options[self::ATTR_STATEMENT_CLASS] = array(
                'Db_Pdo_Statement'
            );
            parent::__construct($dsn, $username, $password, $driver_options);
        }
        
        /**
        * Создание экземпляра класса.
        * 
        * @param  string $dsn            Информация для соединения с БД.
        * @param  string $username       Имя пользователя.
        * @param  string $password       Пароль.
        * @param  array  $driver_options Опции, специфичные для драйверов.
        * @return Db_Pdo
        */
        public static function create
        (
            $dsn, $username = null, $password = null,
            array $driver_options = array()
        )
        {
            return new self($dsn, $username, $password, $driver_options);
        }
    }

?>