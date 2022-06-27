<?php
/**
 * @version 1.0.0
 * @package https://github.com/basteyy/medoo-orm
 * @author Sebastian Eiweleit <sebastian@eiweleit.de>
 * @license Attribution-NonCommercial-ShareAlike 4.0 International
 */

declare(strict_types=1);

namespace basteyy\MedooOrm\Interfaces;

use Exception;
use Medoo\Medoo;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

interface TableInterface
{
    /**
     * Construct the Table Class. Make sure you provide Medoo as an already initialized class, an array with the connection information or the Container Interface
     * @param ContainerInterface|Medoo|array $connection
     * @throws Exception
     */
    public function __construct(ContainerInterface|Medoo|array $connection);


}