<?php
/**
 * Created by PhpStorm.
 * User: sz
 * Date: 19.05.17
 * Time: 11:30
 */

namespace postgkhEngine;


abstract class ProcedureDataFormatter extends \CApplicationComponent
{
  abstract public function format($data);
}
