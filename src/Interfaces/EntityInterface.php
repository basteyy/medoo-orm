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

namespace basteyy\MedooOrm\Interfaces;

interface EntityInterface
{
    /**
     * @param string $tableClass
     * @param array $entityData
     * @param string|null $id_column
     * @param array $joins
     * @param bool $auto_join
     * @param bool $use_relations
     */
    public function __construct(
        string  $tableClass,
        array   $entityData = [],
        ?string $id_column = null,
        array   $joins = [],
        bool    $auto_join = true,
        bool    $use_relations = true
    );
}