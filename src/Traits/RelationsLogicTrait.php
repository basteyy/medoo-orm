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

namespace basteyy\MedooOrm\Traits;

use basteyy\MedooOrm\Table;

trait RelationsLogicTrait {

    /** @var bool $use_relations State for using the relations fpr the next request */
    private bool $use_relations = true;

    /** @var bool $default_relations_state THe state for using relations by default */
    private bool $default_relations_state = true;

    /**
     * Turn off the relations for the next operation
     * @return RelationsLogicTrait|Table
     */
    public function relationsOff() : self {
        $this->use_relations = false;
        return $this;
    }

    /**
     * Turn on the relations for the next operation
     * @return RelationsLogicTrait|Table
     */
    public function relationsOn() : self {
        $this->use_relations = true;
        return $this;
    }

    /**
     * Set a new state for using relations by default and also for the next operation
     * @param bool $state
     * @return RelationsLogicTrait|Table
     */
    public function setRelations(bool $state) : self {
        $this->default_relations_state = $state;
        $this->use_relations = $state;
        return $this;
    }
}
