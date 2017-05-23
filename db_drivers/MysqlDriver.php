<?php

/**
 * Created by PhpStorm.
 * User: sz
 * Date: 10.05.17
 * Time: 12:39
 */
namespace postgkhEngine\db_drivers;


class MysqlDriver extends DbDriver
{

  public function createQuery($procedure, $attributes = [])
  {
    $attributes = implode(', ', $attributes);

    return 'CALL ' . $this->dbName . '.' . $procedure . '('.$attributes.')';
  }
}
