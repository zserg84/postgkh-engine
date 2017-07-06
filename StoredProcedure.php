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

    private $logger;

    /**
     * @var флаг для того чтобы валидировать данные только 1 раз
     */
    private $validate = false;

    /**
     * @param DbDriver $driver
     * @param ProcedureDataFormatter $dataFormatter
     * @param $logger
     * @param array $parameters
     */
    public function __construct(DbDriver $driver, $dataFormatter, $logger = null, $parameters = [])
    {
        $this->driver = $driver;
        $this->dataFormatter = $dataFormatter;

        if($logger){
            $logger->init();
        }
        $this->logger = $logger;

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

        if($this->logger){
            if($this->_procedureResult->isError()){
                $this->logger->error = $this->_procedureResult->message;
            }
            else{
                $this->logger->result = $this->_procedureResult->res;
            }
            $this->logger->insert();
        }

        return $this->_procedureResult;
    }

    /**
     * @throws
     * @return ProcDataProvider
     */
    public function execute()
    {
        $query = $this->getQuery($this->_attributes);
        $data = new ProcDataProvider($query, $this->driver, $this->dataFormatter, $this->logger);
        return $data;
    }

    /**
     * Метод getQuery.
     * Позволяет получить запрос, для выполнения процедуры
     * @throws \Exception
     * @return string
     */
    public function getQuery()
    {
        if (!$this->validate) {
            if (!$this->validation()) {
                $errors = $this->getErrors();
                $firstError = reset($errors);
                throw new \Exception(reset($firstError));
            }
            $this->validate = true;
        }
        return $this->driver->createQuery($this->name, $this->_attributes);
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
