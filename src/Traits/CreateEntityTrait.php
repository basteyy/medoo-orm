<?php
/**
 * @version 1.0.0
 * @package https://github.com/basteyy/medoo-orm
 * @author Sebastian Eiweleit <sebastian@eiweleit.de>
 * @license Attribution-NonCommercial-ShareAlike 4.0 International
 */

declare(strict_types=1);

namespace basteyy\MedooOrm\Traits;

use basteyy\MedooOrm\Exceptions\InvalidDefinitionException;
use basteyy\MedooOrm\Helper\ReflectionFactory;
use basteyy\MedooOrm\Interfaces\EntityInterface;
use DateTime;
use Exception;
use ReflectionException;
use ReflectionProperty;

trait CreateEntityTrait
{
    /** @var array $__ignored_property_names Storage for properties, which are allowed to be not initialized */
    protected array $__ignored_property_names = ['__new'];

    /** @var array $__origData Storage of all original Data */
    protected array $__origData = [];

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
            $this->__orig[$property_name] = $property_value;
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

            if (!isset($entityData[$property->getName()]) && is_string($property_casting) && class_exists($property_casting) ) {
                $this->{$property->getName()} = $this->joinTableVarNameMutation($property->getName(), $entityData);
            }

            if (isset($entityData[$property->getName()])) {

                switch ($property_casting) {
                    case 'mixed' :
                        $this->{$property->getName()} = $entityData[$property->getName()];
                        break;

                    case 'string' :
                        $this->{$property->getName()} = (string)$entityData[$property->getName()];
                        break;

                    case 'array' :
                        if (is_array($entityData[$property->getName()])) {
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
                        $this->{$property->getName()} = (new DateTime($entityData[$property->getName()]))->setTimezone(new \DateTimeZone(date_default_timezone_get()));
                        break;

                    default:
                        if(is_string($property_casting) && class_exists($property_casting) ) {
                            $this->{$property->getName()} = $entityData[$property->getName()];
                        } else {
                            throw new Exception(sprintf('Unable to cast property of %s as %s', __CLASS__, $property->getType()->getName()));
                        }
                }

            }
        }
    }


    /**
     * Custom property name mutation helper
     * @param string $table_basename
     * @param array $entityData
     * @param $l
     * @return string
     */
    private function joinTableVarNameMutation(string $table_basename, array $entityData, $l = true): EntityInterface
    {

        if (isset($entityData[lcfirst($table_basename)])) {
            return $entityData[lcfirst($table_basename)];
        }

        if (isset($entityData[ucfirst($table_basename)])) {
            return $entityData[ucfirst($table_basename)];
        }

        if (isset($entityData[strtolower($table_basename)])) {
            return $entityData[strtolower($table_basename)];
        }

        if (isset($entityData[strtoupper($table_basename)])) {
            return $entityData[strtoupper($table_basename)];
        }

        if($l && !str_ends_with($table_basename, 's')) {
            return $this->joinTableVarNameMutation($table_basename . 's', $entityData, false);
        }

        if($l && str_ends_with($table_basename, 's')) {
            return $this->joinTableVarNameMutation(substr($table_basename, 0, -1), $entityData, false);
        }

        #if($l && !isset($entityData[$table_basename])) {
            throw new InvalidDefinitionException(sprintf('Joining failed because the %s is not defined in the data set for the entity', $table_basename));
        #}

       # return $table_basename;
    }


}