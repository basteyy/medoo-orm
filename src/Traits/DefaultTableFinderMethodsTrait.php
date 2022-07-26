<?php declare(strict_types=1);
/**
 * This file is part of the Medoo-ORM Script.
 *
 * @version 1.0.0
 * @package https://github.com/basteyy/medoo-orm
 * @author Sebastian Eiweleit <sebastian@eiweleit.de>
 * @license Attribution-NonCommercial-ShareAlike 4.0 International
 */

namespace basteyy\MedooOrm\Traits;

use basteyy\MedooOrm\Entity;
use basteyy\MedooOrm\Exceptions\InvalidDefinitionException;
use basteyy\MedooOrm\Exceptions\NotImplementedException;
use basteyy\MedooOrm\Helper\ReflectionFactory;
use basteyy\MedooOrm\Helper\Singleton;
use basteyy\MedooOrm\Interfaces\EntityInterface;
use Exception;
use JetBrains\PhpStorm\ArrayShape;
use ReflectionException;
use ReflectionProperty;

trait DefaultTableFinderMethodsTrait
{
    /**
     * @param array|null $where
     * @param array|null $selectedColumns
     * @param array|null $join
     * @return array
     * @throws Exception
     */
    #[ArrayShape(['join' => "array|null", 'column' => "array|null|string", 'where' => "array|null"])]
    private function buildJoinWhereQueryArgument(
        ?array $where = null,
        ?array $selectedColumns = null,
        ?array $join = null
    ): array
    {
        if (!isset($selectedColumns) || count($selectedColumns) === 0) {
            $selectedColumns = $this->getColumns($this->table_name, true);
        }

        foreach ($where as $field => $value) {
            if (!str_contains($field, '.')) {
                $where[$this->table_name . '.' . $field] = $value;
                unset($where[$field]);
            }
        }

        if (isset($join) || $this->auto_join && isset($this->join)) {
            // Join Query

            $join_command = $join ?? $this->join;

            foreach ($join_command as $table => $_options) {
                $raw_table_argument = $table;
                if (($end = strpos($table, ']'))) {

                    $table_name = substr($table, $end + 1);

                    #$selectedColumns[$table_name . '('.$table_name.')'] = $this->getColumns($table_name);
                    #$selectedColumns[$table_name] = $this->getColumns($table_name);
                    $selectedColumns[$table_name] = $this->getColumns($table_name, false);

                    if (isset($_options['WHERE'])) {
                        foreach ($_options['WHERE'] as $where_column => $where_value) {
                            $where[$table_name . '.' . $where_column] = $where_value;
                        }

                        unset($join_command[$raw_table_argument]['WHERE']);
                    }

                }
            }
        }

        return [
            'column' => !isset($selectedColumns) || count($selectedColumns) < 1 ? '*' : $selectedColumns,
            'where'  => $where ?? null,
            'join'   => $join_command ?? null,
        ];

    }

    /**
     * Return all entries based on select statement
     * @param string $column
     * @param int|string $value
     * @param array|null $selectedColumns
     * @param array|null $join
     * @return array|null
     * @throws Exception
     */
    public function getAllBySingleColumn(
        string     $column,
        int|string $value,
        ?array     $selectedColumns = null,
        ?array     $join = null
    ): array|null
    {
        return $this->getAllBy([$column => $value], $selectedColumns, $join);
    }

    /**
     * Return one (the first) entry based on one column selection
     * @param string $column
     * @param int|string $value
     * @param array|null $selectedColumns
     * @param array|null $join
     * @return EntityInterface|null
     * @throws Exception
     */
    public function getOneBySingleColumn(
        string     $column,
        int|string $value,
        ?array     $selectedColumns = null,
        ?array     $join = null
    ): EntityInterface|null
    {
        return $this->getOnyBy([$column => $value], $selectedColumns, $join);
    }

    /**
     * @param array $where
     * @param array|null $selectedColumns
     * @param array|null $join
     * @return EntityInterface|null
     * @throws Exception
     */
    public function getOnyBy(
        array $where,
        ?array     $selectedColumns = null,
        ?array     $join = null
    ): EntityInterface|null
    {
        $build = $this->buildJoinWhereQueryArgument($where, $selectedColumns, $join);

        if ($build['join'] && $build['where']) {
            $result = $this->medoo->get(
                $this->table_name,
                $build['join'],
                $build['column'],
                $build['where']
            );
        } elseif ($build['where'] && !$build['join']) {
            $result = $this->medoo->get(
                $this->table_name,
                $build['column'],
                $build['where']
            );
        } elseif (!$build['where'] && $build['join']) {
            $result = $this->medoo->get(
                $this->table_name,
                $build['join'],
                $build['column']
            );
        }

        $this->_log($this->medoo->log());

        return $result ? $this->entity($result) : null;
    }

    /**
     * Return all elements based on a complex where array
     * @param array $where
     * @param array|null $selectedColumns
     * @param array|null $join
     * @return EntityInterface|null
     * @throws Exception
     */
    public function getAllBy(
        array  $where,
        ?array $selectedColumns = null,
        ?array $join = null
    ): array|null
    {
        $build = $this->buildJoinWhereQueryArgument($where, $selectedColumns, $join);

        if ($build['join'] && $build['where']) {
            $result = $this->medoo->select(
                $this->table_name,
                $build['join'],
                $build['column'],
                $build['where']
            );
        } elseif ($build['where'] && !$build['join']) {
            $result = $this->medoo->select(
                $this->table_name,
                $build['column'],
                $build['where']
            );
        } elseif (!$build['where'] && $build['join']) {
            $result = $this->medoo->select(
                $this->table_name,
                $build['join'],
                $build['column']
            );
        }

        $this->_log($this->medoo->log());

        if (!$result) {
            return null;
        }

        $helper = [];

        foreach ($result as $entity) {
            $helper[] = $this->entity($entity);
        }

        return $helper;
    }

    /**
     * Get one entry by its id column
     * @param int|string $id
     * @param array|null $selectedColumns
     * @param array|null $join
     * @return EntityInterface|null
     * @throws Exception
     */
    public function getOneById(
        int|string $id,
        ?array     $selectedColumns = null,
        ?array     $join = null
    ): EntityInterface|null
    {
        return $this->getOneBySingleColumn(
            $this->id_column,
            $id,
            $selectedColumns,
            $join
        );
    }

    /**
     * Create an entity object based on the entityData
     * @throws NotImplementedException|InvalidDefinitionException|ReflectionException
     */
    private function entity(array $entityData = []): EntityInterface|array
    {
        /** Join the current entry to a table? */
        if(isset($this->table_join)) {
            foreach($this->table_join as $table => $conditions) {
                // $table is a string of a fqn of an entity

                if(class_exists($table)) {
                    // EntityClass

                    $table_basename = basename(str_replace('\\', DIRECTORY_SEPARATOR, $table));

                    if(str_ends_with($table_basename, 'Entity')) {
                        throw new InvalidDefinitionException(sprintf('You cant join a entity on a table. Change %s against the table-class', $table));
                    }

                    if(str_ends_with($table_basename, 'Table')) {
                        $table_basename = substr($table_basename, 0, -5);
                    }

                    // Data exists?
                    if(!isset($entityData[array_key_first($conditions)])) {
                        throw new Exception(
                            sprintf('Cant find data for the join condition on %s-Table "%s"',
                                $this->current_class_name,
                                isset($conditions[0]) ? $conditions[0] : array_key_first($conditions)
                            )
                        );
                    }

                    $column = array_key_last($conditions);                  // Local Table!
                    $value = $entityData[array_key_first($conditions)];     // Local Table Value!
                    $joined_table_column = $conditions[$column];

                    /** Search for the argument of the entity class, which is the join var */
                    /** @var \ReflectionClass $reelection */
                    $reelection = ReflectionFactory::getReflection($this->getEntityName($this->current_class_name));

                    if(!$reelection->hasProperty($table_basename)) {
                        $table_basename = $this->propertyNameMutation($table_basename, $reelection);
                    }

                    $entityData[$table_basename] = (new $table(Singleton::getMedoo()))->getOneBySingleColumn($joined_table_column, $value);


                } elseif (is_string($table)) {
                    // String means table from database
                    throw new NotImplementedException(sprintf('Classless-auto-joins are not supported. Join "%s" manually', $table));
                }


            }
        }

        return new ((string)$this->getEntityName($this->current_class_name))($entityData, $this->id_column);
    }


    /**
     * Method is search for the correct name of an argument. Trys a few mutations and calls itself.
     * @param $basename
     * @param \ReflectionClass $reflectionClass
     * @param bool $second_mutation
     * @return string|false
     */
    private function propertyNameMutation($basename, \ReflectionClass $reflectionClass, bool $second_mutation = false) : string|false {

        if($reflectionClass->hasProperty(lcfirst($basename))) {
            return lcfirst($basename);
        }

        if($reflectionClass->hasProperty(ucfirst($basename))) {
            return ucfirst($basename);
        }

        if($reflectionClass->hasProperty(strtolower($basename))) {
            return strtolower($basename);
        }

        if($reflectionClass->hasProperty(strtoupper($basename))) {
            return strtoupper($basename);
        }

        if($second_mutation) {
            return false;
        }

        if(str_ends_with($basename, 's')) {

            $mutation = $this->propertyNameMutation(substr($basename, 0, -1), $reflectionClass, true);;

            if(is_string($mutation)) {
                return $mutation;
            }
        }

        if(!str_ends_with($basename, 's')) {

            $mutation = $this->propertyNameMutation($basename . 's', $reflectionClass, true);

            if(is_string($mutation)) {
                return $mutation;
            }
        }

        throw new \InvalidArgumentException(sprintf('Property %s not found', $basename));

    }


    /**
     * @throws Exception
     * @deprecated Use getOneById
     */
    public function getById(int|string $id, ?array $join = null): Entity|null
    {
        if (!isset($this->id_column)) {
            throw new Exception(sprintf('The ID_COLUMN is not defined in Table Model %s', get_called_class()));
        }

        return $this->getBySingleColumn($this->id_column, $id, [], $join ?? null);
    }


    /**
     * Get all entries by the table by one column and value pair
     * @throws ReflectionException|Exception
     * @deprecated Use getOneBySingleColumn or getAllBySingleColumn
     */
    public function getBySingleColumn(
        string     $column,
        int|string $value,
        ?array     $selectedColumns = null,
        ?array     $join = null
    ): Entity|null|\stdClass
    {

        $where = [
            $column => $value
        ];

        if (isset($join) || $this->auto_join && isset($this->join)) {
            // Join Query

            $join_command = $join ?? $this->join;

            if (!isset($selectedColumns) || count($selectedColumns) === 0) {
                $selectedColumns = $this->getColumns($this->table_name, true);
            }

            foreach ($join_command as $table => $_options) {
                $raw_table_argument = $table;
                if (($end = strpos($table, ']'))) {

                    $table_name = substr($table, $end + 1);

                    #$selectedColumns[$table_name . '('.$table_name.')'] = $this->getColumns($table_name);
                    #$selectedColumns[$table_name] = $this->getColumns($table_name);
                    $selectedColumns[$table_name] = $this->getColumns($table_name, false);

                    if (isset($_options['WHERE'])) {
                        foreach ($_options['WHERE'] as $where_column => $where_value) {
                            $where[$table_name . '.' . $where_column] = $where_value;
                        }

                        unset($join_command[$raw_table_argument]['WHERE']);
                    }

                }
            }

            if (!str_contains($column, '.')) {
                $column = $this->table_name . '.' . $column;
            }

            $data = $this->medoo->select(
                $this->table_name,
                $join_command,
                count($selectedColumns) < 1 ? '*' : $selectedColumns,
                $where
            );


        } else {
            $data = $this->medoo->select($this->table_name, count($selectedColumns) < 1 ? '*' : $selectedColumns, $where);
        }

        $this->_log($this->medoo->log());

        if (!$data) {
            return null;
        }

        if (count($data) === 1) {
            return $this->entity($data[0]);
        }

        return (object)$data;
    }



    /**
     * Get all columns from a table. Function is using apcu cache if enabled
     * @param string $table_name
     * @param bool $skip_alias
     * @return array
     * @throws Exception
     */
    protected function getColumns(string $table_name, bool $skip_alias = false): array
    {
        if (!ctype_alnum(str_replace('_', '', $table_name))) {
            throw new Exception(sprintf('Sorry, but the table name %s looks invalid/dangerous!', $table_name));
        }

        $apcu = false;

        if(!defined('DEBUG')) {
            if(function_exists('apcu_enabled') && apcu_enabled()) {
                if(apcu_exists('cols' . $table_name)) {
                    return apcu_fetch('cols' . $table_name);
                }

                $apcu = true;
            }
        }

        $columns = [];

        $cols = $this->medoo->query(sprintf('SHOW COLUMNS FROM %s', $table_name))->fetchAll();

        if ($skip_alias) {
            foreach ($cols as $col) {
                $columns[] = $table_name . '.' . $col['Field'];
            }
        } else {
            foreach ($cols as $col) {
                #$columns[] = $table_name . '.' . $col['Field'] . ' AS ' . $table_name . '_' . $col['Field'];
                $columns[] = $table_name . '.' . $col['Field'] . '(' . $table_name . '_' . $col['Field'] . ')';
            }
        }

        if ($apcu) {
            apcu_add('cols' . $table_name, $columns, (60*10));
        }

        return $columns;
    }


    /**
     * Return all rows of the current table by id `$ids` as objects in an array
     * @param string|array $ids
     * @return array
     */
    public function allById(string|array $ids): array
    {
        $results = $this->medoo->select($this->table_name, '*', [
            $this->id_column => $ids
        ]);

        $set = [];

        foreach ($results as $result) {
            $set[] = $this->entity($result);
        }

        return $set;

    }

    /**
     * Return all rows if the current table
     * @return array
     */
    public function getAll(array $where = []): array
    {
        if(count($where) > 0) {
            $results = $this->medoo->select($this->table_name, '*', $where);
        } else {
            $results = $this->medoo->select($this->table_name, '*');
        }
        $set = [];

        foreach ($results as $result) {
            $set[] = $this->entity($result);
        }

        return $set;
    }

}
