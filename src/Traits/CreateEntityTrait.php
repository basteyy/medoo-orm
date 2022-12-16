<?php
declare(strict_types=1);
/**
 * This file is part of the Medoo-ORM Script.
 *
 * @version 1.0.0
 * @package https://github.com/basteyy/medoo-orm
 * @author Sebastian Eiweleit <sebastian@eiweleit.de>
 * @license Attribution-NonCommercial-ShareAlike 4.0 International
 */

namespace basteyy\MedooOrm\Traits;

use basteyy\MedooOrm\Exceptions\RelationsDeclarationMissingException;
use basteyy\MedooOrm\Helper\ReflectionFactory;
use basteyy\MedooOrm\Helper\Singleton;
use basteyy\MedooOrm\Interfaces\EntityInterface;
use basteyy\MedooOrm\Table;
use DateTime;
use DateTimeZone;
use Exception;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use const ENT_QUOTES;

trait CreateEntityTrait
{
    /** @var array $__ignored_property_names Storage for properties, which are allowed to be not initialized */
    protected array $__ignored_property_names = ['__new'];

    /**
     * Create an entity, where no specific entity-class is created
     *
     * @param array $entityData
     * @return void
     */
    private function createBasicEntity(array $entityData): void
    {
        foreach ($entityData as $property_name => $property_value) {
            $this->{$property_name} = $property_value;
            $this->__origData[$property_name] = $property_value;
        }
    }

    /**
     * Create a specific entity-class
     *
     * @throws ReflectionException
     * @throws Exception
     */
    private function createDefinedEntity(array $entityData): void
    {
        $reelection = ReflectionFactory::getReflection($this);

        // Get all public properties from the reflected class
        $properties = $reelection->getProperties(ReflectionProperty::IS_PUBLIC);

        // Get all default properties from the reflected class
        $default_properties = $reelection->getDefaultProperties();

        // Illiterate all properties
        /** @var ReflectionProperty $property */
        foreach ($properties as $property) {

            // All properties without a default value are required
            if (
                !isset($entityData[$property->getName()]) &&
                !isset($default_properties[$property->name]) &&
                !isset($this->__ignored_property_names[$property->getName()]) &&
                ($property->getName() == $this->id_column && !isset($entityData['__new']))
            ) {
                throw new Exception(sprintf('Unable to create entity %s, because property %s is missing.',
                    __CLASS__,
                    $property->getName()
                ));
            }

            // Try to cast the properties
            $property_casting = $property->hasType() ? $property->getType()->getName() : 'string';

            #/** Search for mutations of the property name from the class in the entityData array */
            #if (!isset($entityData[$property->getName()]) && is_string($property_casting) && class_exists($property_casting) ) {
            #    $this->{$property->getName()} = $this->joinTableVarNameMutation($property->getName(), $entityData) ?? null;
            #}

            if (isset($entityData[$property->getName()])) {

                $this->__origData[$property->getName()] = $entityData[$property->getName()];

                switch ($property_casting) {
                    case 'mixed' :
                        if (is_string($entityData[$property->getName()])) {
                            $this->{$property->getName()} = (string)htmlspecialchars($entityData[$property->getName()], ENT_QUOTES, 'UTF-8');
                        } else {
                            $this->{$property->getName()} = $entityData[$property->getName()];
                        }
                        break;

                    case 'string' :
                        $this->{$property->getName()} = (string)htmlspecialchars($entityData[$property->getName()], ENT_QUOTES, 'UTF-8');
                        //$this->{$property->getName()} = (string)$entityData[$property->getName()];
                        break;

                    case 'array' :
                        if (is_object($entityData[$property->getName()])) {
                            $this->{$property->getName()}[] = $entityData[$property->getName()];
                        } elseif (is_array($entityData[$property->getName()])) {
                            $this->{$property->getName()} = $entityData[$property->getName()];
                        } else {

                            if (strlen($entityData[$property->getName()]) === 0) {
                                // Empty array
                                $this->{$property->getName()} = [];
                            } else {
                                // Json?
                                try {
                                    //varDebug($entityData[$property->getName()], json_decode($entityData[$property->getName()], true));
                                    //varDebug(($entityData[$property->getName()]));
                                    $this->{$property->getName()} = unserialize($entityData[$property->getName()]);
                                } catch (Exception $exception) {
                                    if (str_contains($entityData[$property->getName()], "\n")) {
                                        // Explode \n from field
                                        foreach (explode("\n", $entityData[$property->getName()]) as $value) {
                                            if (strlen(trim($value)) > 0) {
                                                $this->{$property->getName()}[] = rtrim($value);
                                            }
                                        }
                                    } else {
                                        $this->{$property->getName()} = [$entityData[$property->getName()]];
                                    }
                                }
                            }
                        }

                        break;

                    case 'bool' :
                        $this->{$property->getName()} = (bool)$entityData[$property->getName()];
                        break;

                    case 'float' :
                        $this->{$property->getName()} = (float)$entityData[$property->getName()];
                        break;

                    case 'int' :
                        $this->{$property->getName()} = (int)$entityData[$property->getName()];
                        break;

                    case 'DateTime':
                        if (is_string($entityData[$property->getName()])) {
                            /** Build a new DateTime Object */
                            $this->{$property->getName()} = (new DateTime($entityData[$property->getName()]))
                                ->setTimezone(new DateTimeZone(date_default_timezone_get()));
                        } else {
                            /** Use the given DateTime Object */
                            $this->{$property->getName()} = $entityData[$property->getName()]
                                ->setTimezone(new DateTimeZone(date_default_timezone_get()));
                        }
                        break;

                    default:
                        if (is_string($property_casting) && class_exists($property_casting)) {
                            $this->{$property->getName()} = $entityData[$property->getName()];
                        } else {
                            throw new Exception(sprintf('Unable to cast property of %s as %s', __CLASS__, $property->getType()->getName()));
                        }
                }

            }


            /**
             * The auto joins are working only, when auto join is activated
             */
            if (isset($this->join) && !$this->noJoin) {

                if (!isset($entityData[$property->getName()]) && 'array' === $property_casting) {
                    if (str_ends_with($property->getName(), 'Entity')) {

                        /** Corresponding table class */
                        /** @var ReflectionClass $table_reflection */
                        $table_reflection = ReflectionFactory::getReflection($this->tableClass);

                        if ($table_reflection->hasProperty('relations')) {
                            $computed_table = $this->getTableName($table_reflection->getNamespaceName() . '\\' . $property->getName());

                            if (isset($table_reflection->getProperty('relations')->getDefaultValue()[$computed_table])) {

                                /** @var Table $table_model */
                                $table_model = (new ($computed_table)(Singleton::getMedoo()));

                                $relation_data = $table_reflection->getProperty('relations')->getDefaultValue()[$table_model::class];

                                $local_column = key($relation_data);
                                $remote_column = $relation_data[$local_column];
                                $this->{$property->getName()} = $table_model->getAllBy([$remote_column => $this->{$local_column}]) ?? [];

                            }
                        }
                    }
                }

                /** No data for this property. Maybe it's a custom object? */
                if (!isset($entityData[$property->getName()]) && class_exists($property_casting)) {

                    $property_casting_reflection = ReflectionFactory::getReflection($property_casting);

                    /** Yes, it's a custom property. Let's check, if it's an entity-class */
                    //if(str_ends_with($property_casting, 'Entity')) {
                    if (isset($property_casting_reflection->getInterfaces()[EntityInterface::class])) {

                        /** Yes, it's an entity class. So, lets try to join that little bastard */
                        /** For that, we will reflect the table class */

                        $table_reflection = ReflectionFactory::getReflection($this->tableClass);

                        if (!$table_reflection->hasProperty('relations')) {
                            throw new RelationsDeclarationMissingException(
                                sprintf('Relation must be declared before using automatic joins. Missed declaration for %s',
                                    $property_casting
                                ));
                        }

                        /** Create mutation of current entity class to find correct current table class */
                        $table = $this->getTableName($property_casting);

                        /** Make sure, that there is no loop while relation in relation ... */
                        if ((ReflectionFactory::getReflection($table))->hasProperty('relations') &&
                            isset((ReflectionFactory::getReflection($table))->getProperty('relations')->getDefaultValue()[$this->tableClass])) {
                            throw new Exception(
                                sprintf('The relation between %s and %s is endless.',
                                    $this->tableClass,
                                    $table
                                ));
                        }

                        /** @var Table $table_model */
                        $table_model = (new ($this->getTableName($property_casting))(Singleton::getMedoo()));

                        $relation_data = $table_reflection->getProperty('relations')->getDefaultValue()[$table_model::class];

                        $local_column = key($relation_data);
                        $remote_column = $relation_data[$local_column];


                        $this->{$property->getName()} = $table_model->getOnyBy([$remote_column => $this->{$local_column}]);

                    }

                }

            }
        }


    }


}