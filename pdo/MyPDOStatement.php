<?php

namespace postgkhEngine\pdo;

/**
 * Description of MyPDOStatement
 * 
 * Эмулятор PDOStatement для MySQL
 *
 * @author dmezentsev
 */
class MyPDOStatement
{
  /**
   * @var string 
   * sql-выражение 
   */
  private $queryString;
  
  /**
   * @var mysqli_result
   * Результат выполнения sql-выражения
   */
  protected $_result;
  
  /**
   * @var MyPDO
   * Объект эмулятора PDO
   */
  protected $_PDO;
  
  public function __construct ($PDO, $statement, $options=array())
  {
    $this->_PDO = $PDO;
    $this->queryString = $statement;
  }
  
  /**
   * Закрытие курсора
   * @return boolean 
   */
  public function closeCursor() 
  {
    if($this->_result)
      $this->_result->close();
    
    return true;
  }
  
  /**
   * Выполнение sql-выражения, определенного в queryString
   * @param array $input_parameters совместимость с PDOStatement
   * @return boolean 
   */
  public function execute ($input_parameters=array())
  {
    if($this->queryString)
    {
      $connection = $this->_PDO->getConnection();
      $connection->multi_query ($this->queryString);
      if($connection->errno){
        throw new \PDOException($connection->error, $connection->errno);
      }
      $this->_result = $connection->store_result();
      while ($connection->more_results() && $connection->next_result());
    } else {
      return false;
    }
    return true;
  }
  
  /**
   * Количество записей, возвращенных запросом
   * @return int 
   */
  public function rowCount ()
  {
    //var_dump($this->getResult());
    return $this->getResult() ? $this->getResult()->num_rows : 0;
  }
  
  /**
   * Ссылка на результат выполнения sql-выражения
   * @return mysqli_result
   */
  public function getResult ()
  {
    return $this->_result;
  }
  
  /**
   * Количество колонок в результате выполнения sql-выражения
   * @return int
   */
  public function columnCount ()
  {
    return $this->getResult()->field_count;
  }
  
  /**
   * Код ошибки sql-выражения
   * @return int
   */
  public function errorCode()
  {
    return $this->_PDO->errorCode();
  }
  
  /**
   * Текст ошибки при выполнении sql-выражения
   * @return string
   */
  public function errorInfo()
  {
    return $this->_PDO->errorInfo();
  }
  
  /**
   * Считать строку из набора данных mysql_result, полученных в результате
   * выполения sql-выражения queryString
   * @param int $fetch_style тип возвращаемого массива PDO::FETCH_*
   * @param int $cursor_orientation совместимость с PDO
   * @param int $cursor_offset совместимость с PDO
   * @return array 
   */
  public function fetch($fetch_style = \PDO::FETCH_ASSOC, $cursor_orientation = 0, $cursor_offset = 0)
  {
    $result = $this->getResult();
    if($result)
      return $result->fetch_array(MyPDO::pdo2mysql($fetch_style));
    else
      return null;
  }
  
  /**
   * Считать все строки результата работы sql-выражения
   * @param int $fetch_style тип возвращаемого массива PDO::FETCH_*
   * @param array $fetch_argument
   * @return array[] 
   */
  public function fetchAll($fetch_style = \PDO::FETCH_ASSOC, $fetch_argument = null)
  {
    $result = array();
    while ($row = $this->fetch($fetch_style))
      $result[] = $row;
    return $result;
  }
  
  /** 
   * Считывание значение колонки в текущей строки по номеру колонки
   * @param int $column_number
   * @return array 
   */
  public function fetchColumn($column_number=0)
  {
    $row = $this->fetch(\PDO::FETCH_NUM);
    return isset($row[$column_number]) ? $row[$column_number] : false;
  }
  
  /**
   * Создание и заполнение экземпляра класса $class_name
   * результатами текущей строки, где наименование колонки соответствует 
   * наименованию атрибута класса
   * @param string $class_name
   * @return \stdClass
   */
  public function fetchObject($class_name = "stdClass") 
  {
    $obj = new $class_name;
    $row = $this->fetch(PDO::FETCH_ASSOC);
    foreach($row as $key => $value)
      if(isset($obj->$key)) 
        $obj->$key = $value;
    return $obj;
  }
  
  /**
   * Следующий набор результатов
   * @return boolean 
   */
  public function nextRowset() 
  {
    $this->_PDO->getConnection()->next_result();
    return true;
  }
  
  /**
   * Совместимость с PDOStatement
   * @return boolean 
   */
  public function setFetchMode ()
  {
    return true;
  }
}
