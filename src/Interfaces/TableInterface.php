<?php declare(strict_types=1);
/**
 * This file is part of the Medoo-ORM Script.
 *
 * @version 1.0.0
 * @package https://github.com/basteyy/medoo-orm
 * @author basteyy <sebastian@xzit.online>
 * @license Attribution-NonCommercial-ShareAlike 4.0 International
 */

namespace basteyy\MedooOrm\Interfaces;

use Exception;
use Medoo\Medoo;
use Psr\Container\ContainerInterface;

interface TableInterface
{
    /**
     * Construct the Table Class. Make sure you provide Medoo as an already initialized class, an array with the connection information or the Container Interface
     * @param ContainerInterface|Medoo|array $connection
     * @throws Exception
     */
    public function __construct(ContainerInterface|Medoo|array $connection);


}