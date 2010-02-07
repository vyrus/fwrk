<?php
    
    /* $Id$ */

    /**
    * Абстрактная модель, родительский класс для всех моделей.
    */
    abstract class Mvc_Model_Abstract {                       
        /**
        * Объект для работы с БД.
        * 
        * @var Db_Pdo
        */
        private $_db;
        
        /**
        * Метод-конструктор класса.
        * 
        * @return void.
        */
        public function __construct() {
            /* Получаем объект для работы с БД */
            $this->_db = Resources::getInstance()->db;
        }
        
        /**
        * Выполняет SQL-запрос и возвращает количество кортежей, которые этот
        * запрос затронул.
        * 
        * @link http://www.php.net/manual/en/pdo.exec.php
        * @param  string $sql SQL-запрос.
        * @return int
        */
        protected function execute($sql) {
            return $this->_db->execute($sql);
        }
        
        /**
        * Выполняет SQL-запрос, возвращая результирующий набор данных как объект
        * класса Db_Pdo_Statement.
        * 
        * @link http://www.php.net/manual/en/pdo.query.php
        * 
        * @param  string $sql SQL-запрос.
        * @return Db_Pdo_Statement
        */
        protected function query($sql) {
            return $this->_db->query($sql);
        }
        
        /**
        * Подготавливает запрос к выполнению и возвращает объект запроса.
        * 
        * @link http://www.php.net/manual/en/pdo.prepare.php
        * 
        * @param  string $sql SQL-запрос.
        * @return Db_Pdo_Statement
        */
        protected function prepare($sql) {
            return $this->_db->prepare($sql);
        }
        
        /**
        * Выполняет запрос и возвращает массив, содержащий все результирующие
        * кортежи.
        * 
        * @link http://www.php.net/manual/en/pdostatement.fetchall.php
        * 
        * @param  string $sql         SQL-запрос.
        * @param  mixed  $fetch_style Указывает, как заполнять выходной массив.
        * @return array
        */
        protected function fetchAll($sql, $fetch_style = Db_Pdo::FETCH_BOTH) {
            return $this->query($sql)->fetchAll($fetch_style);
        }
        
        /**
        * Возвращает ID последнего вставленного кортежа.
        * 
        * @link http://www.php.net/manual/en/pdo.lastinsertid.php
        * 
        * @return string
        */
        protected function lastInsertId() {
            return $this->_db->lastInsertId();
        }
        
        /**
        * Закрывает строку в кавычки для использования в запросах.
        * 
        * @link http://www.php.net/manual/en/pdo.quote.php
        * 
        * @param  string $string Исходная строка.
        * @return string
        */
        protected function quote($string) {
            return $this->_db->quote($string);
        }
    }

?>