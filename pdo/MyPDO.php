<?php

namespace postgkhEngine\pdo;

/**
 * Description of MyPDO
 *
 * Эмулятор PDO для MySQL
 * В данном классе реализована эмуляция PDO класса через стандарные механизмы
 * mysqli
 *
 * @author dmezentsev
 */
class MyPDO extends \PDO
{
  /**
   * @var mysqli
   * Подключение к базе
   */
  protected $_connection;

  /**
   * @var string
   * Наименование БД для подключения
   */
  protected $dbname;

  /**
   * @var string
   * Хост для подключения к БД
   */
  protected $host='localhost';

  /**
   * @var string
   * имя пользователя для подключения к БД
   */
  protected $username;

  /**
   * @var string
   * Пароль для подключения к БД
   */
  protected $password;

  /**
   * @var string
   * Название драйвера
   */
  protected $driver;

  /**
   * @var boolean
   * Состояние транзакции false - закрыта, true - открыта
   */
  protected $_inTransaction=false;

  private static $_pdo2mysql = array (
    \PDO::FETCH_ASSOC => MYSQLI_ASSOC,
    \PDO::FETCH_BOTH => MYSQLI_BOTH,
    \PDO::FETCH_NUM => MYSQLI_NUM,
  );

  /**
   * Конструктор класса
   * @param string $dsn строка подключения в формате PDO mysql:username=%s;password=%s...
   * @param string $username имя пользователя
   * @param string $passwd пароль
   * @param array $options дополнительные опции (для совместимости)
   */
  public function __construct ($dsn, $username=null, $passwd=null, $options=null)
  {
    $this->dsnParse($dsn);
    $this->username = $username;
    $this->password = $passwd;
  }

  /**
   * Подготовка statement к выполнению
   * @param string $statement sql-выражение
   * @return MyPDOStatement
   */
  public function query ($statement)
  {
    return $this->createPDOStatement($statement);
  }

  /**
   * Подготовка statement к выполнению
   * @param string $statement sql-выражение
   * @param string $options массив опций выполнения запроса
   * @return MyPDOStatement
   */
  public function prepare ($statement, $options=array())
  {
    return $this->createPDOStatement($statement, $options);
  }

  /**
   * @param string $statement sql-выражение
   * @param string $options опции для выполнения запроса
   * @return MyPDOStatement
   */
  protected function createPDOStatement($statement, $options=array())
  {
    return new MyPDOStatement($this, $statement, $options);
  }

  /**
   * @return mysqli
   * @throws CDbException
   */
  public function getConnection ()
  {
    if(!$this->_connection)
    {
      $this->_connection = new \mysqli($this->host, $this->username, $this->password, $this->dbname);
      $this->_connection->multi_query("CALL postgkh.TIMEZONE_SET(NULL);");
    }
    if(mysqli_connect_errno())
      throw new CDbException();
    return $this->_connection;
  }

  /**
   * Выполняет sql-выражение statement и возвращает количество полученных строк
   * @param string $statement
   * @return int
   */
  public function exec ($statement)
  {
    $PDOStatement = $this->createPDOStatement($statement);
    $PDOStatement->execute();
    $rowCount = $PDOStatement->rowCount();
    $PDOStatement->closeCursor();
    return $rowCount;
  }

  /**
   * Подтврерждение транзакции
   * @return boolean
   */
  public function commit ()
  {
    $this->_inTransaction = false;
    $this->exec('COMMIT;');
    return true;
  }

  /**
   * Откат транзакции
   * @return boolean
   */
  public function rollback ()
  {
    $this->_inTransaction = false;
    $this->exec('ROLLBACK;');
    return true;
  }

  /**
   * Старт транзакции
   * @return boolean
   */
  public function beginTransaction()
  {
    $this->_inTransaction = true;
    $this->exec('START TRANSACTION;');
    return true;
  }

  /**
   * Идентификатор последней добавленной записи
   * @param string $name параметр оставлен для совместимости
   * @return int
   */
  public function lastInsertId($name = null)
  {
    return $this->getConnection()->insert_id;
  }

  /**
   * Состояние транзакции
   * @return boolean
   */
  public function inTransaction()
  {
    return $this->_inTransaction;
  }

  /**
   * Список доступных драйверов
   * @return array
   */
  public static function getAvailableDrivers ()
  {
    return array('mysql');
  }

  /**
   * Код ошибки
   * @return int
   */
  public function errorCode()
  {
    return $this->getConnection()->errno;
  }

  /**
   * Текст ошибки
   * @return string
   */
  public function errorInfo()
  {
    return $this->getConnection()->error;
  }

  /**
   * Парсер для строки подключения в формате PDO
   * По строке dsn заполняются атрибуты объекта
   * @param string $dsn
   */
  protected function dsnParse ($dsn)
  {
    $driver = preg_split('/[\s:]+/',$dsn,-1,PREG_SPLIT_NO_EMPTY);

    if(count($driver))
    {
      $this->driver = $driver[0];
      if(isset($driver[1]))
      {
        $paramsVal = preg_split('/[\s;]+/',$driver[1],-1,PREG_SPLIT_NO_EMPTY);
        foreach($paramsVal as $paramVal)
        {
          $param = preg_split('/[\s=]+/',$paramVal,-1,PREG_SPLIT_NO_EMPTY);
          if(isset($param[0]) && isset($param[1]))
          {
            $prop = $param[0];
            if(isset($this->$prop))
              $this->$prop = $param[1];
          }
        }
      }
    }
  }

  /**
   * Совместимость с PDO
   * @return boolean
   */
  public function setAttribute($attribute, $value)
  {
    return true;
  }

  /**
   * Совместимость с PDO
   * @param int $attr константа PDO::*
   * @return string|null
   */
  public function getAttribute ($attr)
  {
    switch ($attr)
    {
      case 16: return 'mysql';
        break;
      default: return null;
        break;
    }
  }

  /**
   * Строка в кавычки. Совместимость с PDO
   * @param string $string
   * @return string
   */
  public function quote($string, $paramtype = NULL)
  {
    return "'$string'";
  }
  /**
   * Преобразование констант PDO в константы mysql
   * @param mixed $pdoConstant
   * @return integer
   */
  public static function pdo2mysql ($pdoConstant)
  {
    return isset(self::$_pdo2mysql[$pdoConstant]) ? self::$_pdo2mysql[$pdoConstant] : false;
  }

}
