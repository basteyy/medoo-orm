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

trait RawGetterSetterTrait
{

    public function getRaw($key) : mixed {
        return $this->__orig[$key] ?? false;
    }

    public function setRaw(string $key, mixed $value) {
        $this->{$key} = $value;
    }
}
