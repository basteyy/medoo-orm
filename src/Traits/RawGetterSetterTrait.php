<?php
/**
 * @author Sebastian Eiweleit <sebastian@eiweleit.de>
 * @package https://github.com/basteyy/medoo-orm
 * @license Attribution-NonCommercial-ShareAlike 4.0 International
 * @version 1.0.0
 */

declare(strict_types=1);

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
