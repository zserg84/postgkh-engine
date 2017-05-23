<?php

namespace postgkhEngine;

/**
 * Created by PhpStorm.
 * User: sz
 * Date: 12.05.17
 * Time: 10:27
 *
 * @property $result
 * @property $res
 * @property $message
 */
class ProcedureResult extends \CComponent implements \Countable, \Iterator, \ArrayAccess
{

  const DEFAULT_MESSAGE_ERROR = 'Ошибка выполнения.';
  const DEFAULT_MESSAGE_SUCCESS = 'Успешно выполнено.';

  public $result = [];

  private $res;

  private $message;

  private $_position;

  public function __construct($result)
  {
    $this->result = $result;
    if(count($result) == 1){
      $result = reset($result);
      if(isset($result['RES'])){
        $this->res = $result['RES'];
        $this->message = isset($result['MSG']) ? $result['MSG'] : null;
      }
    }

    $this->_position = 0;
  }

  /**
   * @return mixed
   */
  public function getRes()
  {
    return $this->res;
  }

  /**
   * @return mixed
   */
  public function getMessage()
  {
    if($this->isError()){
      return $this->message ?: self::DEFAULT_MESSAGE_ERROR;
    }
    return $this->message ?: self::DEFAULT_MESSAGE_SUCCESS;
  }

  public function setMessage($message)
  {
    $this->message = $message;
  }

  public function isError()
  {
    return is_array($this->res) ? true : ($this->res + 0 <= 0);
  }

  public function rewind()
  {
    $this->_position = 0;
  }

  public function next()
  {
    $this->_position++;
  }

  public function key()
  {
    return $this->_position;
  }

  public function current()
  {
    return $this->result[$this->_position];
  }

  public function valid()
  {
    return isset($this->result[$this->_position]);
  }


  public function count()
  {
    return count($this->result);
  }

  public function offsetExists($offset)
  {
    return isset($this->result[$offset]);
  }

  public function offsetGet($offset)
  {
    return $this->result[$offset];
//    return $this->offsetExists($offset) ? $this->result[$offset] : null;
  }

  public function offsetSet($offset, $value)
  {
    if (is_null($offset)) {
      $this->result[] = $value;
    } else {
      $this->result[$offset] = $value;
    }
  }

  public function offsetUnset($offset)
  {
    unset($this->result[$offset]);
  }

  public function toArray()
  {
    return $this->result;
  }
}
