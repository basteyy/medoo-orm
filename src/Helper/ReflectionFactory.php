<?php declare(strict_types=1);
/**
 * This file is part of the Medoo-ORM Script.
 *
 * @version 1.0.0
 * @package https://github.com/basteyy/medoo-orm
 * @author basteyy <sebastian@xzit.online>
 * @license Attribution-NonCommercial-ShareAlike 4.0 International
 */

namespace basteyy\MedooOrm\Helper;

use ReflectionClass;
use ReflectionException;

final class ReflectionFactory
{
    private static array $reflections = [];

    /**
     * @throws ReflectionException
     */
    public static function getReflection($class): ReflectionClass
    {
        $class_name = is_string($class) ? $class : get_class($class);

        if (!isset(self::$reflections[$class_name])) {
            self::$reflections[$class_name] = new ReflectionClass($class);
        }

        return self::$reflections[$class_name];
    }
}