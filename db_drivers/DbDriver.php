<?php
/**
 * Created by PhpStorm.
 * User: sz
 * Date: 16.05.17
 * Time: 18:02
 */

namespace postgkhEngine\db_drivers;


abstract class DbDriver extends \CDbConnection
{

  public function getDbName()
  {
    $connectionString = $this->connectionString;
    $dbname = substr($connectionString, strpos($connectionString, 'dbname'));
    if($pos = strpos($dbname, ';'))
      $dbname = substr($dbname, 0, strpos($dbname, ';'));
    $dbname = explode('=', $dbname);
    $dbname = $dbname[1];
    return $dbname;
  }
}
