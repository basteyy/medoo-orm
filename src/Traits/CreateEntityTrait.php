<?php
/**
 * @version 1.0.0
 * @package https://github.com/basteyy/medoo-orm
 * @author Sebastian Eiweleit <sebastian@eiweleit.de>
 * @license Attribution-NonCommercial-ShareAlike 4.0 International
 */

declare(strict_types=1);

namespace basteyy\MedooOrm\Traits;

use basteyy\MedooOrm\Helper\ReflectionFactory;
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

            if (isset($entityData[$property->getName()])) {

                switch ($property_casting) {
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
                        throw new Exception(sprintf('Unable to cast property of %s as %s', __CLASS__, $property->getType()->getName()));
                }

            }
        }
    }
}