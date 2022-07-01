<?php
/**
 * @version 1.0.0
 * @package https://github.com/basteyy/medoo-orm
 * @author Sebastian Eiweleit <sebastian@eiweleit.de>
 * @license Attribution-NonCommercial-ShareAlike 4.0 International
 */

declare(strict_types=1);

namespace basteyy\MedooOrm;

use basteyy\MedooOrm\Helper\ReflectionFactory;
use basteyy\MedooOrm\Interfaces\EntityInterface;
use basteyy\MedooOrm\Traits\CreateEntityTrait;
use basteyy\MedooOrm\Traits\FindClassNameTrait;
use basteyy\MedooOrm\Traits\GetModelTrait;
use basteyy\MedooOrm\Traits\LoggingTrait;
use basteyy\MedooOrm\Traits\RawGetterSetterTrait;
use basteyy\MedooOrm\Traits\StringMethodsTrait;
use Exception;
use Medoo\Medoo;
use ReflectionException;

class Entity implements EntityInterface
{
    use FindClassNameTrait, CreateEntityTrait, RawGetterSetterTrait, StringMethodsTrait, GetModelTrait;

    use LoggingTrait;

    private string $id_column;

    protected bool $__new = false;

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    public function __construct(array $entityData = [], ?string $id_column = null)
    {
        $reflection = ReflectionFactory::getReflection($this);

        $this->id_column = $id_column ?? 'id';

        // Is the created class the base entity-class?
        if('basteyy\MedooOrm\Entity' === $reflection->getName()) {
            $this->createBasicEntity($entityData);
        } else {
            $this->createDefinedEntity($entityData);
        }

        // New Dataset?
        if(isset($entityData['__new'])) {
            $this->__new = true;
        }

    }

    public function isNew() : bool {
        return $this->__new;
    }

    public function getPrimaryIdColumnName(): string
    {
        if(!isset($this->id_column)) {
            throw new \Exception('Primary ID is not configured inside the Entity Model. Change the Model or try to get it from the Table Model.');
        }
        return $this->id_column;
    }

    public function getPrimaryId() {
        if(!isset($this->id_column)) {
            throw new \Exception('Primary ID is not configured inside the Entity Model. Change the Model or try to get it from the Table Model.');
        }
        return $this->{$this->id_column};
    }

    public function getColumns() : array {
        return $this->columns;
    }
    private function __sanitize (mixed $value) : mixed {

        if (is_array($value)) {
            foreach($value as $_key => $_value) {
                $value[$_key] = $this->__sanitize($_value);
            }
        }

        if(is_string($value)) {
            return filter_var($value, FILTER_SANITIZE_STRING, FILTER_SANITIZE_STRING);
            #return htmlspecialchars($value);
            #return htmlentities($value, ENT_QUOTES, 'UTF-8');
        }

        return $value;
    }


}