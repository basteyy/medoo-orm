<?php declare(strict_types=1);
/**
 * This file is part of the Medoo-ORM Script.
 *
 * @version 1.0.0
 * @package https://github.com/basteyy/medoo-orm
 * @author basteyy <sebastian@xzit.online>
 * @license Attribution-NonCommercial-ShareAlike 4.0 International
 */

namespace basteyy\MedooOrm\Traits;

use basteyy\MedooOrm\Helper\Singleton;
use basteyy\MedooOrm\Interfaces\TableInterface;

trait GetModelTrait
{
    private array $tables = [];

    /**
     * @throws \Exception
     */
    protected function getTable(string $class_name): TableInterface
    {
        if(!isset($this->tables[$class_name])) {
            $this->tables[$class_name] = new ($this->getTableName($class_name))(Singleton::getMedoo());
        }

        return $this->tables[$class_name];
    }
}
