<?php declare(strict_types=1);
/**
 * TableClass created on :create_date_time: for table :table: on database :database:.
 * TableClass was created by the ORM-Script. Make sure the output fit your needs.
 *
 * @see https://github.com/basteyy/medoo-orm/wiki/ORM-(Console)#created-files
 */

namespace :namespace:;

use basteyy\MedooOrm\Interfaces\TableInterface;
use basteyy\MedooOrm\Table;

class :class_name: extends Table implements TableInterface
{
    /** @var string $table_name Name of the table in the database */
    public string $table_name = ':table:';

    /** @var string $id_column The ID Primary ID Column in the table */
    public string $id_column = ':id_column:';
}