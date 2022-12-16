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
use basteyy\MedooOrm\Exceptions\InvalidClassPropertyNameException;
use Exception;

trait FindClassNameTrait
{
    private static array $registry = [];

    private static array $entityTableRelations = [];

    /**
     */
    private function getEntityName(string $table_class_name): string
    {
        if (isset(self::$registry[$table_class_name])) {
            return self::$registry[$table_class_name];
        }

        if(isset($this->entity_class_name) && class_exists($this->entity_class_name)) {
            return $this->entity_class_name;
        }

        $entity_computed_name = str_replace('\\Tables\\', '\\Entities\\', $table_class_name);

        $class_name_list = [];

        if (!str_starts_with($entity_computed_name, '\\')) {
            $entity_computed_name = '\\' . $entity_computed_name;
        }

        if (str_ends_with($entity_computed_name, 'Table')) {
            $entity_computed_name = substr($entity_computed_name, 0, -5);
        }

        $class_name_list[] = $entity_computed_name;
        $class_name_list[] = $entity_computed_name . 'Entity';

        if (str_ends_with($entity_computed_name, 's')) {
            $class_name_list[] = substr($entity_computed_name, 0, -1);
            $class_name_list[] = substr($entity_computed_name, 0, -1) . 'Entity';
        } else {
            $class_name_list[] = $entity_computed_name . 's';
            $class_name_list[] = $entity_computed_name . 'sEntity';
        }

        foreach ($class_name_list as $name) {
            if (class_exists($name)) {
                self::$registry[$table_class_name] = $name;
            }
        }

        if (isset(self::$registry[$table_class_name])) {
            $this->entity_class_name = self::$registry[$table_class_name];
            self::$registry[$this->entity_class_name] = $table_class_name;
            return self::$registry[$table_class_name];
        }


        return Entity::class;

        #throw new Exception(__('Entity for table %s not found. Tried classes: %s', $class_name, implode("\n", $class_name_list)));
    }

    /**
     * @throws Exception
     */
    private function getTableName(string $entity_class_name): string
    {
        if (isset(self::$registry[$entity_class_name])) {
            return self::$registry[$entity_class_name];
        }

        $entity_computed_name = str_replace('\\Entities\\', '\\Tables\\', $entity_class_name);

        $class_name = basename(str_replace('\\', '/', $entity_class_name));
        $class_name_first_letter = substr($class_name, 0, 1);

        if($class_name_first_letter === lcfirst($class_name_first_letter)) {
            if(defined('DEBUG') && DEBUG) {
                throw new InvalidClassPropertyNameException(sprintf('The property name %s has to start with a capital letter to match table classes', $class_name));
            }
        }

        $suggestions = [
            $entity_computed_name
        ];

        if(str_ends_with($entity_computed_name, 'Entity')) {
            $suggestions[] = substr($entity_computed_name, 0, -6) . 'Table';
            $suggestions[] = ucfirst(substr($entity_computed_name, 0, -6) . 'Table');
        }

        if(str_ends_with($entity_computed_name, 'sTable')) {
            $suggestions[] = substr($entity_computed_name, 0, -6) . 'Table';
        }

        foreach($suggestions as $suggestion) {
            if(class_exists($suggestion)) {
                self::$registry[$entity_class_name] = $suggestion;
            }
        }

        if (!isset(self::$registry[$entity_class_name])) {
            throw new Exception(sprintf('Cannot find table class %s', $entity_class_name));
        }

        return self::$registry[$entity_class_name];

    }
}