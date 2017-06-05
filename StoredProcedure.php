<?php

namespace postgkhEngine;

use postgkhEngine\db_drivers\DbDriver;

/**
 * Class StoredProcedure
 * @package engine
 */
abstract class StoredProcedure extends \CComponent
{

    /**
     * Имя процедуры
     * @var
     */
    public $name;
    /**
     * @var
     */
    protected $_attributes = [];
    /**
     * Результат выполнения процедуры
     * @var ProcedureResult $_procedureResult
     */
    protected $_procedureResult;
    /**
     * список ошибок
     * @var array
     */
    private $_errors = [];
    /**
     * Драйвер для БД
     * @var DbDriver
     */
    private $driver;
    /**
     * @var ProcedureDataFormatter
     */
    private $dataFormatter;

    /**
     * @param DbDriver $driver
     * @param ProcedureDataFormatter $dataFormatter
     * @param array $parameters
     */
    public function __construct(DbDriver $driver, $dataFormatter, $parameters = [])
    {
        $this->driver = $driver;
        $this->dataFormatter = $dataFormatter;

        $this->fillAttributes($parameters);

    }

    /**
     * @param $values
     */
    private function fillAttributes($values)
    {
        $attributes = [];
        foreach ($this->getAttrs() as $attribute => $attributeParams) {
            $attributes[] = $attribute;
        }
        foreach ($values as $key => $value) {
            $value = is_array($value) ? implode(',', $value) : $value;
            $this->_attributes[$attributes[$key]] = $value;
        }
    }

    /**
     * @return mixed
     */
    abstract public function getAttrs();

    /**
     * Метод fetchAll.
     * Выполняет запрос
     * @return array
     */
    public function fetchAll()
    {
//        if($this->_procedureResult){
//            return $this->_procedureResult;
//        }
        $dataProvider = $this->execute();
        $dataProvider->setPagination(false);
        $result = $dataProvider->getData();
        $this->_procedureResult = new ProcedureResult($result);

        return $this->_procedureResult;
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
     * validation
     * Правила проверки входных параметров
     * и приведения их к типам, необходимым для вызова процедур
     */
    protected function validation()
    {
        return true;
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
     * Метод getQuery.
     * Позволяет получить запрос, для выполнения процедуры
     * @param array $attributes
     * @return string
     */
    public function getQuery($attributes = [])
    {
        return $this->driver->createQuery($this->name, $attributes);
    }

    /**
     * @return ProcedureResult
     */
    public function getProcedureResult()
    {
        return $this->_procedureResult ?: new ProcedureResult(null, null);
    }

    /**
     * Добавляем ошибку
     * @param $attribute
     * @param string $error
     */
    public function addError($attribute, $error = '')
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
     * @return array
     */
    public function getAttributes()
    {
        return $this->_attributes;
    }

    /**
     * Очищаем список ошибок
     */
    protected function clearErrors()
    {
        $this->_errors = [];
    }
}
