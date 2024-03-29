<?php
declare(strict_types=1);
/**
 * This file is part of the Medoo-ORM Script.
 *
 * @version 1.0.0
 * @package https://github.com/basteyy/medoo-orm
 * @author basteyy <sebastian@xzit.online>
 * @license Attribution-NonCommercial-ShareAlike 4.0 International
 */

namespace basteyy\MedooOrm;

use basteyy\MedooOrm\Helper\ReflectionFactory;
use basteyy\MedooOrm\Helper\Singleton;
use basteyy\MedooOrm\Interfaces\EntityInterface;
use basteyy\MedooOrm\Interfaces\TableInterface;
use basteyy\MedooOrm\Traits\DefaultTableFinderMethodsTrait;
use basteyy\MedooOrm\Traits\FindClassNameTrait;
use basteyy\MedooOrm\Traits\GetModelTrait;
use basteyy\MedooOrm\Traits\LoggingTrait;
use basteyy\MedooOrm\Traits\RelationsLogicTrait;
use DateTime;
use DateTimeZone;
use Exception;
use Medoo\Medoo;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use ReflectionProperty;

class Table implements TableInterface
{
    /** @var bool $auto_join Join tables automatically by default? */
    public bool $auto_join = false;

    /** @var string The name of the database table */
    protected string $table_name;

    /** @var string The name of the id column */
    protected string $id_column;

    /** @var array|null Join table to other tables */
    protected ?array $join;

    /** @var string $entity_class_name If set, the classname will be tried to use for creating entities. If not set, it will be used for cache entity class name */
    protected string $entity_class_name;

    /** @var array|string[] List of keys which can hold the medoo connection (in the ContainerInterface) */
    private array $containerConnectionKeyList = [
        'database', 'db', 'DatabaseConnection', 'connection', Medoo::class
    ];

    /** @var Medoo|mixed|null Medoo Connection */
    private ?Medoo $medoo;
    /** @var string $current_class_name The Name of the current table class */
    private string $current_class_name;

    /** @var bool $noJoin Helper to skip a join on auto-joins */
    private bool $noJoin = false;

    /** @var ReflectionProperty[] */
    private array $entity_reflection;

    /** @var array|string[] Fields, which are in json format */
    public array $json_fields = [];

    use DefaultTableFinderMethodsTrait,
        FindClassNameTrait,
        GetModelTrait,
        LoggingTrait,
        RelationsLogicTrait;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function __construct(ContainerInterface|Medoo|array $connection)
    {
        if (!defined('DEBUG')) {
            define('DEBUG', false);
        }

        if (!isset($this->table_name)) {
            $this->logger->error(sprintf('Define $table_name in %s', get_called_class()));
            throw new Exception(sprintf('Define $table_name in %s', get_called_class()));
        }

        if (!isset($this->id_column)) {
            $this->logger->error(sprintf('Define $id_column in %s', get_called_class()));
            throw new Exception(sprintf('Define $id_column in %s', get_called_class()));
        }

        if (is_array($connection)) {
            $this->medoo = Singleton::getMedoo($connection);
        } elseif ($connection instanceof Medoo) {
            $this->medoo = $connection;
            Singleton::setMedoo($this->medoo);
        } elseif ($connection instanceof ContainerInterface) {

            foreach ($this->containerConnectionKeyList as $key) {
                if ($connection->has($key)) {
                    $this->medoo = $connection->get($key);
                }
            }

            if (!$this->medoo) {
                throw new Exception('Make sure that you define the Medoo-Connection in the Dependency Injector somewhere before calling MedooOrm.');
            }


            Singleton::setMedoo($this->medoo);

        } else {
            throw new Exception('Unable to locate/create the medoo instance.');
        }

        $this->current_class_name = get_class($this);
    }

    /**
     * Wrapper for get the Medoo object
     * @return Medoo
     */
    public function getMedoo(): Medoo
    {
        return $this->medoo;
    }

    /**
     * @param array $data
     * @return EntityInterface
     * @throws Exceptions\InvalidDefinitionException
     * @throws Exceptions\NotImplementedException
     * @throws ReflectionException
     */
    public function new(array $data = []): EntityInterface
    {

        if (!isset($data['__new'])) {
            $data['__new'] = true;
        }

        return $this->entity($data);
    }

    /**
     * @throws ReflectionException
     */
    public function patch(EntityInterface $entity, array $data): EntityInterface
    {
        return $this->save($entity, $data, true);
    }


    /**
     * Save an existing entity or create a new entity
     * @throws ReflectionException
     * @throws Exception
     */
    public function save(EntityInterface $entity, ?array $patching_date = null, ?bool $return_entity = null): bool|EntityInterface
    {
        if (isset($patching_date)) {
            foreach ($patching_date as $item => $value) {
                $entity->{$item} = $value;
            }
        }

        // Save the public parameter to database
        $entity_reflection = (ReflectionFactory::getReflection($entity))->getProperties(ReflectionProperty::IS_PUBLIC);
        $this->entity_reflection = $entity_reflection;
        $entity_saving_data = [];

        foreach ($entity_reflection as $property) {

            $value = $entity->{$property->getName()} ?? null;

            /**
             * Medoo isn't supporting datetime objects for now (https://github.com/catfan/Medoo/pull/1050).
             * So we change the object into string for saving
             */
            if ($value instanceof DateTime) {
                /** @var DateTime $value */
                $value = $value
                    ->setTimezone(new DateTimeZone('UTC'))
                    ->format('Y-m-d H:i:s');
                // Timezone and milliseconds (Y-m-d h:m:s.U e) are not supported by now by mysql/mariadb
                // (https://dev.mysql.com/doc/refman/8.0/en/datetime.html)
            }

            /**
             * Joined data not savable for now  ..
             */
            if (!is_object($value) && !$property->hasDefaultValue()) {

                /** Detect json types and encode the values */
                if (isset($this->json_fields) && in_array($property->getName(), $this->json_fields)) {
                    $value = json_encode($value);
                }
                $entity_saving_data[$property->getName()] = $value;
            }

            if ($property->getName() === $this->id_column) {
                $id_column_type = $property;
            }
        }


        if ($entity->isNew() && !isset($entity->{$this->id_column})) {
            unset($entity_saving_data[$this->id_column]);
            $this->medoo->insert($this->table_name, $entity_saving_data);
            #$last_id = $this->medoo->id();
        } else {
            $this->medoo->update($this->table_name, $entity_saving_data, [
                $this->id_column => $entity->{$this->id_column}
            ]);
            #$last_id = $entity->{$this->id_column};
        }

        if (!isset($id_column_type)) {
            throw new Exception(sprintf('Invalid dataset without id column in class %s (table %s)', $this->current_class_name, $this->getTableName()));
        }

        #if ($id_column_type->hasType() && $id_column_type->getType()->getName() === 'int') {
        #    $entity->{$this->id_column} = (int) $this->medoo->id();
        #} else {
        #    $entity->{$this->id_column} = $this->medoo->id();
        #}

        if (isset($return_entity) && $return_entity) {
            return $entity;
        }

        return true;
    }

    /**
     * Return the current table name
     * @return string
     */
    public function getTableName(): string
    {
        return $this->table_name;
    }

    public function delete(EntityInterface $entity): bool
    {
        return $this->deleteById($entity);
    }

    /**
     * Delete an entity/row by its id
     * @param EntityInterface $entity
     * @return bool
     */
    public function deleteById(EntityInterface $entity): bool
    {
        return $this->medoo->delete($this->table_name, [
                $this->id_column => $entity->{$this->id_column}
            ])->rowCount() > 0;
    }

    /**
     * Use that to skip automatic joins
     * @return $this
     */
    public function noJoin(): self
    {
        $this->noJoin = true;
        return $this;
    }

    /**
     * Delete all elements from the table
     * @return void
     */
    public function flush(): void
    {
        $this->medoo->exec('DELETE FROM ' . $this->medoo->tableQuote($this->getTableName()));
    }
}