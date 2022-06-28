<?php
/**
 * @version 1.0.0
 * @package https://github.com/basteyy/medoo-orm
 * @author Sebastian Eiweleit <sebastian@eiweleit.de>
 * @license Attribution-NonCommercial-ShareAlike 4.0 International
 */

declare(strict_types=1);

namespace basteyy\MedooOrm;

use basteyy\MedooOrm\Helper\ReflectionFactory;
use basteyy\MedooOrm\Helper\Singleton;
use basteyy\MedooOrm\Interfaces\EntityInterface;
use basteyy\MedooOrm\Interfaces\TableInterface;
use basteyy\MedooOrm\Traits\DefaultTableFinderMethodsTrait;
use basteyy\MedooOrm\Traits\FindClassNameTrait;
use basteyy\MedooOrm\Traits\GetModelTrait;
use DateTime;
use DateTimeZone;
use Exception;
use Medoo\Medoo;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class Table implements TableInterface
{
    public bool $auto_join = false;
    /** @var string The name of the database table */
    protected string $table_name;
    /** @var string The name of the id column */
    protected string $id_column;
    /** @var array|string[] List of keys which can hold the medoo connection (in the ContainerInterface) */
    private array $containerConnectionKeyList = [
        'database', 'db', 'DatabaseConnection', 'connection', Medoo::class
    ];
    /** @var Medoo|mixed|null Medoo Connection */
    private ?Medoo $medoo;
    /** @var array|null Join table to other tables */
    private ?array $join;
    private string $current_class_name;

    use DefaultTableFinderMethodsTrait, FindClassNameTrait, GetModelTrait;

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

        /** APCu Caching? */
        if (!defined('APCU')) {
            define('APCU', function_exists('apcu_enabled') && apcu_enabled());
        }

        if (!defined('APCU_REQ_TTL')) {
            define('APCU_REQ_TTL', DEBUG ? 0 : 5);
        }

        if (!defined('APCU_SHORT_TTL')) {
            define('APCU_SHORT_TTL', DEBUG ? 0 : 60 * 5);
        }

        if (!defined('APCU_MEDIUM_TTL')) {
            define('APCU_MEDIUM_TTL', DEBUG ? 0 : 60 * 10);
        }

        if (!defined('APCU_LONG_TTL')) {
            define('APCU_LONG_TTL', DEBUG ? 0 : 60 * 60);
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


    /** @inheritDoc */
    public function getTableName(): string
    {
        return $this->table_name;
    }

    /**
     * Create a new entity
     * @param array $data
     * @return EntityInterface
     */
    public function new(array $data = []): EntityInterface
    {

        if (!isset($data['__new'])) {
            $data['__new'] = true;
        }

        return $this->entity($data);
    }


    /**
     * @throws \ReflectionException
     */
    public function save(EntityInterface $entity): bool
    {
        // Save the public parameter to database
        $entity_reflection = (ReflectionFactory::getReflection($entity))->getProperties(\ReflectionProperty::IS_PUBLIC);

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
                    ->format('Y-m-d H:m:s');
                // Timezone and milliseconds (Y-m-d h:m:s.U e) are not supported by now by mysql/mariadb
                // (https://dev.mysql.com/doc/refman/8.0/en/datetime.html)
            }

            $entity_saving_data[$property->getName()] = $value;

            if ($property->getName() === $this->id_column) {
                $id_column_type = $property;
            }

        }

        if ($entity->isNew() && !isset($entity->{$this->id_column})) {
            unset($entity_saving_data[$this->id_column]);
            $this->medoo->insert($this->table_name, $entity_saving_data);
        } else {
            $this->medoo->update($this->table_name, $entity_saving_data, [
                $this->id_column => $entity->{$this->id_column}
            ]);
        }

        if ($id_column_type->hasType() && $id_column_type->getType()->getName() === 'int') {
            $entity->{$this->id_column} = (int)$this->medoo->id();
        } else {

            $entity->{$this->id_column} = $this->medoo->id();
        }

        return true;
    }

    public function deleteById(EntityInterface $entity) : bool {
        return $this->medoo->delete($this->table_name, [
            $this->id_column => $entity->{$this->id_column}
        ])->rowCount() > 0;
    }


}