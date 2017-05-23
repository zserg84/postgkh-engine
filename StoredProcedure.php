<?php

namespace postgkhEngine;

/**
 * Класс md_proc.
 * Является родительским для классов хранимых процедур.
 *
 * В классе реализованы методы:
 * run - выполнение строки запроса
 * validation - валидация данных класса
 * attrPrepare - подготовка входных параметров
 *
 * @param name - Имя СП
 * @param attrProcessed - флаг обработки входных параметров
 * @property ProcedureResult $procedureResult
 */
/**
 * Class StoredProcedure
 * @package engine
 */
abstract class StoredProcedure extends \CComponent
{

  /**
   * @var array
   */
  protected static $_instances = [];
  /**
   * @var
   */
  protected $_attributes = [];

  /**
   * Имя процедуры
   * @var
   */
  public $name;

  /**
   * список ошибок
   * @var array
   */
  private $_errors = [];

  /**
   * @var
   */
  private $_cacheIsolation;

  /**
   * Драйвер для БД
   * @var \CDbConnection
   */
  private $driver;

  /**
   * @var ProcedureDataFormatter
   */
  private $dataFormatter;

  /**
   * Результат выполнения процедуры
   * @var ProcedureResult $_procedureResult
   */
  protected $_procedureResult;


  /**
   * @param \CDbConnection $driver
   * @param ProcedureDataFormatter $dataFormatter
   * @param array $parameters
   */
  public function __construct(\CDbConnection $driver, $dataFormatter, $parameters = [])
  {
    $this->driver = $driver;
    $this->dataFormatter = $dataFormatter;

    $this->fillAttributes($parameters);

  }

  /**
   * @param $procedure
   * @param \CDbConnection $driver
   * @param ProcedureDataFormatter $dataFormatter
   * @param array $parameters
   * @return StoredProcedure
   * @throws \CDbException
   */
  public static function instance($procedure, \CDbConnection $driver, ProcedureDataFormatter $dataFormatter, $parameters = [])
  {
    $procedure = strtolower($procedure);
    if(!@class_exists($procedure))
      throw new \CDbException('Внутренняя ошибка', 0, 'Не найден мета-файл процедуры ' . $procedure);

    $procedureString = (new $procedure($driver, $dataFormatter, $parameters))->getQuery();
    if(!isset(static::$_instances[$procedureString])){
      static::$_instances[$procedureString] = new $procedure($driver, $dataFormatter, $parameters);
    }

    return static::$_instances[$procedureString];
  }

  /**
   * Метод fetchAll.
   * Выполняет запрос
   * @return array
   */
  public function fetchAll()
  {
    $result = $this->execute()->getData();
    $this->_procedureResult = new ProcedureResult($result);

    return $this->_procedureResult;
  }

  /**
   * @return ProcedureResult
   */
  public function getProcedureResult()
  {
    return $this->_procedureResult ?: new ProcedureResult(null, null);
  }

  /**
   * @throws
   * @return ProcDataProvider
   */
  public function execute()
  {

    if (!$this->validation()) {
      $errors = $this->getErrors();
      $firstError = reset($errors);
      throw new \Exception(reset($firstError));
    }

    $query = $this->getQuery($this->_attributes);
    $data = new ProcDataProvider($query, $this->driver, $this->dataFormatter);
    return $data;
  }

  /**
   * @return RCIsolation
   */
  public function getCacheIsolation()
  {
    return $this->_cacheIsolation ?: new RCIsolation (
      array (
        new RCAgentIsolator,
      )
    );
  }

  /**
   * Метод getQuery.
   * Позволяет получить запрос, для выполнения процедуры
   */
  protected function getQuery($attributes = [])
  {
    return $this->driver->createQuery($this->name, $attributes);
  }

  /**
   * @param $isolation
   * @return $this
   */
  public function cacheIsolation($isolation)
  {
    $this->_cacheIsolation = $isolation;
    return $this;
  }

  /**
   * validation
   * Правила проверки входных параметров
   * и приведения их к типам, необходимым для вызова процедур
   */
  protected function validation()
  {
    return true;
  }

  /**
   * Очищаем список ошибок
   */
  protected function clearErrors()
  {
    $this->_errors = [];
  }

  /**
   * @return mixed
   */
  abstract public function getAttrs();

  /**
   * Добавляем ошибку
   * @param $attribute
   * @param string $error
   */
  private function addError($attribute, $error = '')
  {
    $this->_errors[$attribute][] = $error;
  }

  /**
   * Имеется ли ошибка
   * @return bool
   */
  public function hasErrors()
  {
    return (bool)$this->getErrors();
  }

  /**
   * Список ошибок
   * @return array
   */
  public function getErrors()
  {
    return $this->_errors;
  }

  /**
   * @param $values
   */
  private function fillAttributes ($values)
  {
    $attributes = [];
    foreach($this->getAttrs() as $attribute => $attributeParams)
    {
      $attributes[] = $attribute;
    }
    foreach($values as $key => $value)
    {
      $this->_attributes[$attributes[$key]] = $value;
    }
  }
}
