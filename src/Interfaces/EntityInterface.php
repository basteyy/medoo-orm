<?php
/**
 * @version 1.0.0
 * @package https://github.com/basteyy/medoo-orm
 * @author Sebastian Eiweleit <sebastian@eiweleit.de>
 * @license Attribution-NonCommercial-ShareAlike 4.0 International
 */

declare(strict_types=1);

namespace basteyy\MedooOrm\Interfaces;

interface EntityInterface
{
    public function __construct(array $entityData = [], ?string $id_column = null);
}