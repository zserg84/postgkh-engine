<?php
/**
 * Created by PhpStorm.
 * User: sz
 * Date: 10.05.17
 * Time: 13:03
 */

namespace postgkhEngine\db_drivers;


class PgSqlDriver extends DbDriver
{
  public function createQuery($procedure, $attributes = [])
  {
    $attributes = implode(', ', $attributes);

    return 'SELECT * FROM ' . $this->dbName . '.' . $procedure . '('.$attributes.')';
  }

}
