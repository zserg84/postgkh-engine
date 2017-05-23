<?php
/**
 * Created by PhpStorm.
 * User: sz
 * Date: 18.05.17
 * Time: 11:09
 */

namespace postgkhEngine;


/**
 * Class ProcDataProvider
 * @package engine
 */
class ProcDataProvider extends \CDataProvider
{

  protected $_keys = [];

  /**
   * Фильтр
   * Формат:
   *    $cfilter['ACCOUNT_ID'] = ['type'=>'dropDownFilter', 'subType' => 'arrayFilter', 'value' => 123);
   * @var array
   */
  public $cfilter = [];

  /**
   * Список данных датапровайдера
   * @var array
   */
  protected $rawData = [];

  /**
   * Драйвер для БД
   * @var \CDbConnection
   */
  private $driver;

  /**
   * @var
   */
  private $dataReader;

  /**
   * Запрос в виде 'CALL ACCOUNT_LST()'
   * @var
   */
  protected $request;

  /**
   * @TODO постараться выпилить
   * Херь, преобразующая данные в нужный формат
   * @var ProcedureDataFormatter
   */
  private $dataFormatter;

  /**
   * Список данных для фильтра
   * @var
   */
  private $rawFilterData;

  /**
   * ProcDataProvider constructor.
   * @param $request
   * @param \CDbConnection $driver
   * @param ProcedureDataFormatter $dataFormatter
   */
  public function __construct($request, \CDbConnection $driver, ProcedureDataFormatter $dataFormatter = null)
  {
    if (!$request)
      throw new \RuntimeException('The request must be defined.');
    if (!$driver)
      throw new \RuntimeException('The driver must be defined.');

    $this->request = $request;
    $this->driver = $driver;
    $this->dataFormatter = $dataFormatter;
  }

  public function getRawData()
  {
    return $this->rawData;
  }

  /**
   * @return array
   */
  public function fetchAll()
  {
    if (!$this->rawData) {
      while ($row = $this->fetch()) {
        $this->rawData[] = $row;
      }
    }
    return $this->rawData;
  }

  /**
   * @param bool $skip
   * @return bool
   */
  public function fetch($skip = false)
  {
    $dataReader = $this->getDataReader();
    if (!$dataReader)
      return false;

    $row = $dataReader->read();

    if (!$skip && $row) {
      foreach ($row as $key => $value) {
        $value = $this->dataFormatter->format($value);

        $row[strtoupper($key)] = $value;
      }
    }

    return $row;
  }

  /**
   * @return null
   */
  private function getDataReader()
  {
    if ($this->dataReader === null)
      $this->execute();
    return $this->dataReader;
  }

  /**
   * @throws ProcException
   */
  private function execute()
  {
    try {
      $command = $this->driver->createCommand($this->request);
      $command->prepare();
      $this->dataReader = $command->query();
    } catch (\CDbException $e) {
      throw new ProcException($e->getMessage(), $e->getCode(), $e->getPrevious());
    }
  }

  /**
   * @return array
   */
  protected function fetchData()
  {
    if (!$this->rawData) {
      if (($pagination = $this->getPagination()) !== false) {
        $pagination->setItemCount($this->getTotalItemCount());
        $limit = $pagination->getLimit();
        $offset = $pagination->getOffset();
      } else {
        $limit = $this->getTotalItemCount();
        $offset = 0;
      }

      $allRawData = [];
      while ($row = $this->fetch()) {
        $allRawData[] = $row;
      }

      $allRawData = $this->applyFilter($allRawData);
      $i = 0;
      foreach ($allRawData as $row) {
        if ($i < $offset + $limit) {
          $this->rawData[] = $row;
        }
        $i++;
      }
    }
    return $this->rawData;
  }

  /**
   * @param $rows
   * @return mixed
   */
  protected function applyFilter($rows)
  {
    if ($this->cfilter) {
      foreach ($this->cfilter as $columnName => $filterParams) {
        foreach ($rows as $row) {
          if (isset($row[$columnName])) {
            $this->rawFilterData[$columnName][$row[$columnName]] = $row[$columnName];
          }
        }
      }
    }
    return $rows;
  }

  /**
   * @return mixed
   */
  protected function fetchKeys()
  {
    if (!$this->_keys)
      for ($i = 0; $i < $this->itemCount; $i++)
        $this->_keys[$i] = $i;
    return $this->_keys;
  }

  /**
   * @return mixed
   */
  protected function calculateTotalItemCount()
  {
    return $this->getDataReader()->rowCount;
  }
}

class ProcException extends \Exception
{

}
