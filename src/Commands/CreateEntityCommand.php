<?php declare(strict_types=1);
/**
 * This file is part of the Medoo-ORM Script.
 *
 * @version 1.0.0
 * @package https://github.com/basteyy/medoo-orm
 * @author basteyy <sebastian@xzit.online>
 * @license Attribution-NonCommercial-ShareAlike 4.0 International
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace basteyy\MedooOrm\Commands;

use basteyy\MedooOrm\Traits\Commands\ClassWriterTrait;
use basteyy\MedooOrm\Traits\Commands\StartupTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CreateEntityCommand extends Command
{

    /** Traits contains all the required startup logic */
    use StartupTrait;

    /** Trait contains all the writing logic */
    use ClassWriterTrait;

    /**
     * @var string
     */
    protected static $defaultName = 'model:entity';

    /**
     * @var string $defaultDescription Description of this command
     */
    protected static $defaultDescription = 'Create Entity-Class by a table from your database';

    protected StyleInterface $style;
    private InputInterface $input;
    private OutputInterface $output;

    protected function configure(): void
    {
        $this->connectionFile = ORM_ROOT . '/medoo-orm.conf';
        $this->addArgument('config', InputArgument::OPTIONAL, 'File where the connection information are stored.');
    }

    /**
     * Execute the command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int 0 if everything went fine, or an exit code.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if(!isset($this->input)) {
            $this->input = $input;
            $this->output = $output;
            $this->style = new SymfonyStyle($input, $output);
            $this->startup();
        }

        $this->buildTableEntity($this->chooseTable(true));

        if ($this->style->confirm('Create another entity?')) {
            return $this->execute($input, $output);
        }

        return Command::SUCCESS;
    }

    private function buildTableEntity(string $table) : void {

        /* Table Name */
        $table_name = ucfirst($table) . 'Entity';

        $connection = $this->getConnection();

        /* Inspect the columns and get name and types */
        $col_prepared_stmt = $connection->prepare('SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?;');
        $col_prepared_stmt->bind_param('ss', $this->selected_database, $table);
        $col_prepared_stmt->execute();
        $columns = $col_prepared_stmt->get_result();

        $class_properties = '';

        while ($row = $columns->fetch_array(MYSQLI_NUM)) {
            #$this->style ->info(sprintf('Found column %1$s with type %2$s', $row[0], $row[1]));
            $class_properties .= PHP_EOL;
            $class_properties .= chr(9) . sprintf('/** @var %2$s $%1$s Column name from table */', $row[0], $this->getPropertyTranslatedType($row[1]));
            $class_properties .= PHP_EOL;
            $class_properties .= chr(9) . sprintf('public %2$s $%1$s;', $row[0], $this->getPropertyTranslatedType($row[1]));
            $class_properties .= PHP_EOL;
        }

        $this->writeEntityClass($this->storage . '/' . $table_name . '.php', [
            ':namespace:'        => $namespace ?? 'App',
            ':create_date_time:' => date('d.m.y H:i:s'),
            ':table:'            => $table,
            ':database:'         => $this->selected_database,
            ':class_name:'       => $table_name,
            ':properties:'       => $class_properties
        ]);

    }

    private function getPropertyTranslatedType($property_type): string
    {
        return match ($property_type) {
            'int', 'tinyint', 'smallint' => 'int',
            'float' => 'float',
            'varchar', 'tinytext', 'bigtext' => 'string',
            default => 'mixed',
        };
    }
}