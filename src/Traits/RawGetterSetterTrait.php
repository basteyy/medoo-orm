<?php declare(strict_types=1);
/**
 * This file is part of the Medoo-ORM Script.
 *
 * @version 1.0.0
 * @package https://github.com/basteyy/medoo-orm
 * @author basteyy <sebastian@xzit.online>
 * @license Attribution-NonCommercial-ShareAlike 4.0 International
 */

namespace basteyy\MedooOrm\Traits;

trait RawGetterSetterTrait
{
    /**
     * Return the raw value of `$key`. This is a non-secured output!
     * @param $key
     * @return mixed
     */
    public function getRaw($key) : mixed {
        return $this->__origData[$key] ?? false;
    }

    /**
     * Set the value of `$key` in a non-secured raw way.
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setRaw(string $key, mixed $value) : void {
        $this->{$key} = $value;
    }
}
