<?php declare(strict_types=1);
/**
 * This file is part of the Medoo-ORM Script.
 *
 * @version 1.0.0
 * @package https://github.com/basteyy/medoo-orm
 * @author Sebastian Eiweleit <sebastian@eiweleit.de>
 * @license Attribution-NonCommercial-ShareAlike 4.0 International
 */

namespace basteyy\MedooOrm\Interfaces;

interface EntityInterface
{
    /**
     * @param string $tableClassName Table Class which creates the entity
     * @param array $entityData Entitydata
     * @param string|null $id_column
     */
    public function __construct(
        string $tableClassName,
        array $entityData = [],
        ?string $id_column = null
    );
}