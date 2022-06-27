<?php
/**
 * @author Sebastian Eiweleit <sebastian@eiweleit.de>
 * @package https://github.com/basteyy/medoo-orm
 * @license Attribution-NonCommercial-ShareAlike 4.0 International
 * @version 1.0.0
 */

declare(strict_types=1);

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
