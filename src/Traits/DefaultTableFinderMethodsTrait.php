<?php
/**
 * @version 1.0.0
 * @package https://github.com/basteyy/medoo-orm
 * @author Sebastian Eiweleit <sebastian@eiweleit.de>
 * @license Attribution-NonCommercial-ShareAlike 4.0 International
 */

declare(strict_types=1);

namespace basteyy\MedooOrm\Traits;

use basteyy\MedooOrm\Entity;
use basteyy\MedooOrm\Interfaces\EntityInterface;
use Exception;
use JetBrains\PhpStorm\ArrayShape;
use ReflectionException;

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

        foreach($where as $field => $value) {
            if (!str_contains($field, '.' )) {
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

        $build = $this->buildJoinWhereQueryArgument([$column => $value], $selectedColumns, $join);

        if($build['join'] && $build['where']) {
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
        } elseif(!$build['where'] && $build['join']) {
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
        $build = $this->buildJoinWhereQueryArgument([$column => $value], $selectedColumns, $join);

        if($build['join'] && $build['where']) {
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
        } elseif(!$build['where'] && $build['join']) {
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
     */
    private function entity(array $entityData = []): EntityInterface|array
    {
        return new ((string)$this->getEntityName($this->current_class_name))($entityData, $this->id_column);
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

        if ($this->noJoin) {
            $this->noJoin = false;
        }

        // @todo: implement logging $this->_log($this->db->log());

        if (!isset($data[0])) {
            return false;
        }

        // @todo: implement logging $this->_log(json_encode($data[0]));


        $data[0][$this->id_column] = (int)$data[0][$this->id_column];


        return $this->createEntity($this->getEntityName(), $data[0], $this->getIdColumn()); // new ($this->getEntityName())($data[0], $this->getIdColumn());

    }


    /**
     * Debug Wrapper
     * @param $data
     * @return void
     */
    protected function _log($data)
    {
        if (defined('DEBUG') && true === DEBUG) {
            $log_folder = ROOT . '/v2/logs/database/';
            $log_file = $log_folder . date('d.m.y') . '.log';

            if (!is_dir($log_folder)) {
                mkdir($log_folder, 0777, true);
            }

            $content = '';

            if (is_array($data)) {
                foreach ($data as $d) {
                    $content = date('Y-m-d H:i:s' . substr((string)microtime(), 1, 8) . '') . "\t" . $d . "\n" . $content;
                }
            } else {
                $content = date('Y-m-d H:i:s' . substr((string)microtime(), 1, 8) . '') . "\t" . $data . "\n";
            }

            if (file_exists($log_file)) {
                $old_content = file_get_contents($log_file);
            }

            file_put_contents($log_file, $content . ($old_content ?? ''));
        }
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

        // Use Cache
        if(APCU && apcu_exists('cols' . $table_name ) ) {
            return apcu_fetch('cols' . $table_name);
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

        if(APCU) {
            apcu_add('cols' . $table_name, $columns, APCU_MEDIUM_TTL);
        }

        return $columns;
    }


    public function allById(string|array $ids)
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

    public function getAll(): array
    {
        $results = $this->medoo->select($this->table_name, '*');
        $set = [];

        foreach ($results as $result) {
            $set[] = $this->entity($result);
        }

        return $set;
    }

}
