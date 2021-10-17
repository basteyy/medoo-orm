<?php

declare(strict_types=1);

namespace basteyy\MedooOrm;

use basteyy\MedooOrm\Helper\Singleton;
use basteyy\MinimalHashWrapper\MinimalHashWrapper;
use DateTime;
use DI\Definition\Exception\InvalidDefinition;
use Exception;
use Psr\Container\ContainerInterface;
use Medoo\Medoo;
use PDOStatement;

class Table implements TableInterface
{
    /** @var string The name of the database table */
    protected string $table_name;

    /** @var string The name of the id column */
    protected string $id_column;

    /**
     * @var bool $native_entities Turn this to true to enable the native support of the Class Entity in case there is no specific entity defined
     */
    protected bool $native_entities = true;

    /** @var array|string[] A list auf special commands for deconflict querys */
    private array $specialFieldList = ['GROUP_CONCAT', 'GROUP', 'ORDER', 'HAVING', 'LIMIT', 'LIKE', 'MATCH'];

    /** @var Medoo|mixed The Medoo Instance */
    private Medoo $db;

    /** @var bool Disable joins for the next query */
    private bool $noJoin = false;


    /** @inheritDoc */
    public function __construct(ContainerInterface|Medoo|array $connection)
    {
        if (!isset($this->table_name)) {
            throw new Exception(sprintf('Define $table_name in %s', get_called_class()));
        }

        if (!isset($this->id_column)) {
            throw new Exception(sprintf('Define $id_column in %s', get_called_class()));
        }

        // Recieved connection data as array
        if (is_array($connection)) {

            $this->db = Singleton::getMedoo($connection);

        } elseif ($connection instanceof Medoo) {
            $this->db = $container;
        } elseif ($connection instanceof ContainerInterface) {


            if ($connection->has('database')) {
                $this->db = $connection->get('database');
            } elseif ($connection->has('DatabaseConnection')) {
                $this->db = $connection->get('DatabaseConnection');
            } elseif ($connection->has('DB')) {
                $this->db = $connection->get('DB');
            } elseif ($connection->has(Medoo::class)) {
                try {
                    $this->db = $connection->get(Medoo::class);
                } catch (InvalidDefinition) {
                    throw new \Exception('Check your definition of the medoo instance!');
                }
            } else {
                throw new Exception('Make sure that you define the Medoo-Connection in the Dependecy Injector somewhere before calling MedooOrm');
            }

        } else {
            throw new \Exception('Unable to locate/create the medoo instance.');
        }
    }


    /** @inheritDoc */
    public function noJoin(): self
    {
        $this->noJoin = true;
        return $this;
    }


    /** @inheritDoc */
    public function deleteWhere(array $where = [], bool $delete_associations = false): int
    {
        if ($delete_associations) {
            throw new Exception('Deleting associations is not supported!');
        } else {
            $delete = $this->db->delete($this->table_name, $where);
            // @todo: implement logging $this->_log($this->db->log());
            return $delete->rowCount();
        }

    }


    /** @inheritDoc */
    public function getAll(array $where = []): array
    {

        if (isset($this->table_join) && !$this->noJoin) {


            $selectedColumns = $this->getColumns($this->table_name, true);


            foreach ($this->table_join as $table => $_options) {
                if (($end = strpos($table, ']'))) {
                    $table_name = substr($table, $end + 1);
                    $selectedColumns[$table_name] = $this->getColumns($table_name, false);
                }
            }

            $data = $this->db->select(
                $this->table_name,
                $this->table_join,
                count($selectedColumns) < 1 ? '*' : $selectedColumns,
                $where
            );


            // @todo: implement logging // @todo: implement logging $this->_log($this->db->log());


        } else {
            $data = $this->db->select($this->table_name, '*');
            // @todo: implement logging // @todo: implement logging $this->_log($this->db->log());
        }

        if ($this->noJoin) {
            $this->noJoin = false;
        }

        if (!isset($data[0])) {
            return [];
        }

        $entities = [];

        foreach ($data as $entity) {
            $entity[$this->id_column] = (int)$entity[$this->id_column];
            $entities[] = $this->createEntity($this->getEntityName(), $entity, $this->getIdColumn());
        }


        return $entities;
    }


    /** @inheritDoc */
    protected function getColumns(string $table_name, bool $skip_alias = false): array
    {

        if (!ctype_alnum(str_replace('_', '', $table_name))) {
            throw new Exception(sprintf('Sorry, but the tablename %s looks invalid/dangerous!', $table_name));
        }

        $columns = [];

        $cols = $this->db->query(sprintf('SHOW COLUMNS FROM %s', $table_name))->fetchAll();

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


        return $columns;
    }


    /** @inheritDoc */
    protected function createEntity(string $entityClassName, array $entityBaseData = [], string $entityIdColumn = null): mixed
    {
        if (!$entityIdColumn) {
            $entityIdColumn = $this->id_column;
        }

        if (!class_exists($entityClassName)) {
            $entityClassName = Entity::class;
        }

        return new $entityClassName($entityBaseData, $entityIdColumn);
    }


    /** @inheritDoc */
    private function getEntityName(string $classname = null): string
    {

        $class_name = $classname ?? get_called_class();


        if ('Table' === substr($class_name, -5)) {
            $class_name = substr($class_name, 0, -5);
        }

        $class_name = str_replace('\\Tables\\', '\\Entities\\', $class_name);


        if (class_exists($class_name)) {
            return $class_name;
        }

        if (class_exists($class_name . 'Entity')) {
            return $class_name . 'Entity';
        }

        if ('s' === substr($class_name, -1)) {
            return $this->getEntityName(substr($class_name, 0, -1));
        }

        if ($this->native_entities && $classname && class_exists(substr($classname, 0, strrpos($classname, '\\')) . '\Entity')) {
            return substr($classname, 0, strrpos($classname, '\\')) . '\Entity';
        }

        if ($this->native_entities && class_exists(substr($class_name, 0, strrpos($class_name, '\\')) . '\Entity')) {
            return substr($class_name, 0, strrpos($class_name, '\\')) . '\Entity';
        }

        if($this->native_entities) {
            return Entity::class;
        }

        throw new Exception(sprintf('Unable to locate entity class %s(s)', $classname));
    }


    /** @inheritDoc */
    public function getIdColumn(): string
    {
        return $this->id_column;
    }


    /** @inheritDoc */
    public function noLog(): self
    {
        $this->log = false;
        return $this;
    }


    /** @inheritDoc */
    public function Log(): self
    {
        $this->log = true;
        return $this;
    }

    /** @inheritDoc */
    public function getById(int $id): Entity|false
    {
        if (!isset($this->id_column)) {
            throw new Exception(sprintf('The ID_COLUMN is not defined in Table Model %s', get_called_class()));
        }

        return $this->getBySingleColumn($this->id_column, $id);
    }

    /** @inheritDoc */
    public function getBySingleColumn(string $column, int|string $value, array $selectedColumns = []): Entity|false
    {

        if (isset($this->table_join) && !$this->noJoin) {

            if (count($selectedColumns) < 1) {
                $selectedColumns = $this->getColumns($this->table_name, true);
            }

            foreach ($this->table_join as $table => $_options) {
                if (($end = strpos($table, ']'))) {

                    $table_name = substr($table, $end + 1);

                    #$selectedColumns[$table_name . '('.$table_name.')'] = $this->getColumns($table_name);
                    #$selectedColumns[$table_name] = $this->getColumns($table_name);
                    $selectedColumns[$table_name] = $this->getColumns($table_name, false);
                }
            }

            if (!str_contains($column, '.')) {
                $column = $this->table_name . '.' . $column;
            }

            $data = $this->db->select(
                $this->table_name,
                $this->table_join,
                count($selectedColumns) < 1 ? '*' : $selectedColumns,
                [
                    $column => $value
                ]
            );
        } else {
            $data = $this->db->select(
                $this->table_name,
                count($selectedColumns) < 1 ? '*' : $selectedColumns,
                [
                    $column => $value
                ]
            );
        }

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

    /** @inheritDoc */
    public function where(array $where, array $selectedColumns = [])
    {
        if (isset($this->table_join) && !$this->noJoin) {

            if (isset($selectedColumns['GROUP_CONCAT'])) {
                if (count($selectedColumns) == 1) {
                    // All and group concat
                    $group_concat = $selectedColumns['GROUP_CONCAT'];
                    unset($selectedColumns['GROUP_CONCAT']);
                } else {
                    // simple grpu cpncat to the end
                }
            }

            if (count($selectedColumns) < 1) {
                $selectedColumns = $this->getColumns($this->table_name, true);
            } else {
                $selectedColumns = $this->_addTableNameToField($selectedColumns);
            }

            foreach ($this->table_join as $table => $_options) {
                if (($end = strpos($table, ']'))) {
                    $table_name = substr($table, $end + 1);
                    #$selectedColumns[$table_name . '('.$table_name.')'] = $this->getColumns($table_name);
                    $selectedColumns[$table_name] = $this->getColumns($table_name);
                }
            }

            if (isset($group_concat)) {
                $selectedColumns[$group_concat[0]] = Medoo::raw(sprintf('GROUP_CONCAT(%s)', $group_concat[1]));
            }

            $data = $this->db->select(
                $this->table_name,
                $this->table_join,
                count($selectedColumns) < 1 ? '*' : $selectedColumns,
                $this->_addTableNameToField($where)
            );

            // @todo: implement logging $this->_log($this->db->log());

        } else {
            $data = $this->db->select(
                $this->table_name,
                count($selectedColumns) < 1 ? '*' : $selectedColumns,
                $where
            );
            // @todo: implement logging $this->_log($this->db->log());
        }

        if ($this->noJoin) {
            $this->noJoin = false;
        }

        if (!isset($data[0])) {
            return [];
        }

        $entities = [];

        foreach ($data as $entity) {
            $entity[$this->id_column] = (int)$entity[$this->id_column];
            $entities[] = $this->createEntity($this->getEntityName(), $entity, $this->getIdColumn());
        }

        return $entities;

    }

    private function _addTableNameToField(string|array $fields)
    {

        if (is_string($fields)) {
            if (!str_contains($fields, '.') && !in_array($fields, $this->specialFieldList)) {
                return $this->table_name . '.' . $fields;
            }
            return $fields;
        }


        foreach ($fields as $key => $value) {
            if (is_string($key) && !str_contains($key, '.') && !in_array($key, $this->specialFieldList)) {
                $fields[$this->table_name . '.' . $key] = $value;
                unset($fields[$key]);
            } elseif (is_int($key) && !str_contains($value, '.') && !in_array($value, $this->specialFieldList)) {
                $fields[$key] = $this->table_name . '.' . $value;
                #unset($fields[$key]);
            }
        }

        return $fields;
    }

    /** @inheritDoc */
    public function new(array $data = []): Entity
    {
        if (!isset($data['__new'])) {
            $data['__new'] = true;
        }

        return $this->createEntity($this->getEntityName(), $data, $this->getIdColumn());
    }

    /** @inheritDoc */
    public function patch(Entity $entity, array $data = []): Entity
    {
        foreach ($data as $key => $value) {
            $entity->{$key} = $value;
        }

        return $entity;
    }

    /** @inheritDoc */
    public function delete(Entity $entity, bool $delete_associations = false): bool|PDOStatement
    {
        if ($delete_associations) {
            throw new Exception('Deleting associated elements are not supported for now');
        }

        if (!isset($entity->__new)) {

            if (!$this->db->delete($this->table_name, [
                $entity->getPrimaryIdColumnName() => $entity->getPrimaryId()
            ])) {
                throw new Exception(__('Unable to delete data set inside %s with the following data %s', $this->table_name, [
                    $entity->getPrimaryIdColumnName() => $entity->getPrimaryId()
                ]));
            }

        }

        return true;
    }

    /** @inheritDoc */
    public function save(Entity $entity, bool $save_associations = false): bool|PDOStatement
    {
        $databaseColumns = $this->getColumns($this->table_name, true);
        $table_name_length = strlen($this->table_name . '.');

        foreach ($databaseColumns as $column) {
            $column = substr($column, $table_name_length);

            if (isset($entity->{$column})) {
                if (
                    !isset($entity->__orig[$column])
                    || (isset($entity->__new) && $entity->__orig[$column] === $entity->{$column})
                    || ($entity->__orig[$column] !== $entity->{$column})
                ) {
                    if ($entity->{$column} instanceof DateTime) {
                        /** @var DateTime $entity- >{$column} */
                        $data[$column] = ($entity->{$column})->format('Y-m-d H:i:s');
                    } else {
                        $data[$column] = $entity->{$column};
                    }

                }
            }
            #varDebug($this->table_name,$column);
        }

        #foreach ($entity->getColumns() as $column) {
        #    $data[$column] = $entity->{$column};
        #}


        if (isset($data['password']) && !isset($entity->password_plain)) {

            if (!defined('SALT')) {
                throw new Exception('Please define a salt somewhere in your code!');
            }

            $data['password'] = MinimalHashWrapper::getHash(SALT . $data['password']);
        }

        if (isset($entity->__new)) {
            // New Entry
            #unset($data['__new']);
            if (!$this->db->insert($this->table_name, $data)) {
                throw new Exception($this->__('Unable to create a new data set inside %s with the following data %s', $this->table_name, $data));
            }

            $entity->id = (int)$this->db->id();
            // @todo: implement logging $this->_log($this->db->log());

            return true;
        } else {

            $_finalData = [];

            if (!isset($data) || count($data) < 1) {
                // No data to save!
                return true;
            }

            foreach ($data as $element => $value) {
                if ($value instanceof Entity) {
                    if ($save_associations) {
                        throw new Exception('Associations are not supported in saving');
                    } else {
                        unset($data[$element]);
                    }
                }
            }

            $res = $this->db->update($this->table_name, $data, [
                $this->id_column => $entity->getPrimaryId()
            ]);

            // @todo: implement logging $this->_log($this->db->log());

            return $res;
        }

    }

    /** @inheritDoc */
    public function insert(array $data): ?PDOStatement
    {
        return $this->db->insert($this->getTableName(), $data);
    }

    /** @inheritDoc */
    public function getTableName(): string
    {
        return $this->table_name;
    }

}