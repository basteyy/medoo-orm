<?php

/**
 * @author Sebastian Eiweleit <sebastian@eiweleit.de>
 * @license Attribution-NonCommercial-ShareAlike 4.0 International
 */

declare(strict_types=1);

namespace basteyy\MedooOrm;

use basteyy\MedooOrm\Helper\Singleton;
use basteyy\MinimalHashWrapper\MinimalHashWrapper;
use DI\Definition\Exception\InvalidDefinition;
use Medoo\Medoo;
use Psr\Container\ContainerInterface;
use PDOStatement;

interface TableInterface
{

    /**
     * Construct the Table Class. Make sure you provide Medoo as a already initalized class, an array with the connection information or the the Container Interface
     * @param ContainerInterface $container
     * @throws Exception
     */
    public function __construct(ContainerInterface|Medoo|array $connection);

    /**
     * Disable the join for the next query
     * @return $this
     */
    public function noJoin();

    /**
     * @param array $where
     * @param bool $delete_associations
     * @return int
     * @throws Exception
     */
    public function deleteWhere(array $where = [], bool $delete_associations = false);

    /**
     * Return all entries from the database. If you pass a where condition, the function return only the matching entries.
     * @param array $where
     * @return array
     * @throws Exception
     */
    public function getAll(array $where = []);

    /**
     * @param string $table_name
     * @return array
     * @throws Exception
     * @todo Implement caching
     */
    #protected function getColumns(string $table_name, bool $skip_alias = false);

    /**
     * Create the entity
     * @param string $entityClassName
     * @param array $entityBaseData
     * @param string|null $entityIdColumn
     * @return mixed|
     */
    #public function createEntity(string $entityClassName, array $entityBaseData = [], string $entityIdColumn = null);

    /**
     * Create the entity classname, based on the table name.
     * @param string|null $classname
     * @return string
     * @throws Exception
     */
    #private function getEntityName(string $classname = null);

    /**
     * Return the id of the table
     * @return string
     */
    public function getIdColumn();

    /**
     * Disable logging for the next Query only
     * @return $this
     */
    public function noLog();

    /**
     * Enable logging for the next query only
     * @return $this
     */
    public function Log();

    /**
     * Get entity by id
     * @param int $id
     * @return Entity|false
     * @throws Exception
     */
    public function getById(int $id): Entity|false;

    /**
     * @param string $column
     * @param int|string $value
     * @param array $selectedColumns
     * @return Entity|false
     */
    public function getBySingleColumn(string $column, int|string $value, array $selectedColumns = []): Entity|false;

    public function where(array $where, array $selectedColumns = []);

    public function new(array $data = []): Entity;

    public function patch(Entity $entity, array $data = []): Entity;

    public function delete(Entity $entity, bool $delete_associations = false): bool|\PDOStatement;

    /**
     * @throws Exception
     */
    public function save(Entity $entity, bool $save_associations = false): bool|\PDOStatement;

    /**
     * @param array $data
     * @return PDOStatement|null
     */
    public function insert(array $data): ?PDOStatement;

    /**
     * Return the Name of the table
     * @return string
     */
    public function getTableName(): string;
}