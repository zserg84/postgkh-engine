<?php
/**
 * Created by PhpStorm.
 * User: sz
 * Date: 05.06.17
 * Time: 11:53
 */

namespace postgkhEngine;


use postgkhEngine\db_drivers\DbDriver;

class StoredProcedureStore
{
    /**
     * @var array
     */
    protected static $_instances = [];

    /**
     * @param $procedure
     * @param DbDriver $driver
     * @param ProcedureDataFormatter $dataFormatter
     * @param $logger
     * @param array $parameters
     * @return StoredProcedure
     * @throws \Exception
     */
    public static function instance($procedure, DbDriver $driver, ProcedureDataFormatter $dataFormatter, $logger = null, $parameters = [])
    {
        $procedure = strtolower($procedure);
        if(!@class_exists($procedure))
            throw new \Exception('Внутренняя ошибка', 0, 'Не найден мета-файл процедуры ' . $procedure);

        $procedure = new $procedure($driver, $dataFormatter, $logger, $parameters);
        $procedureString = $procedure->getQuery();
        if(!isset(static::$_instances[$procedureString])){
            static::$_instances[$procedureString] = $procedure;
        }

        return static::$_instances[$procedureString];
    }
}
