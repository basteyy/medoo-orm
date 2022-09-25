<?php
/**
 * This file is part of the package go-crazy-legacy.
 * The script is nonfree software.
 * The usage is allowed only under the conditions from the license.
 *
 * @author Sebastian Eiweleit <sebastian@eiweleit.de>
 * @website https://eiweleit.de
 * @website https://www.go-crazy.de
 * @website https://www.bikereisen.de
 * @website https://github.com/basteyy/go-crazy-legacy
 * @license https://eiweleit.de/private-software
 */
declare(strict_types=1);
/**
 * This file is part of the Medoo-ORM Script.
 *
 * @version 1.0.0
 * @package https://github.com/basteyy/medoo-orm
 * @author Sebastian Eiweleit <sebastian@eiweleit.de>
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

class CreateTableCommand extends Command
{

    /** Traits contains all the required startup logic */
    use StartupTrait;

    /** Trait contains all the writing logic */
    use ClassWriterTrait;

    /**
     * @var string
     */
    protected static $defaultName = 'model:table';

    /**
     * @var string $defaultDescription Description of this command
     */
    protected static $defaultDescription = 'Create Table-Class by a table from your database';

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
        if (!isset($this->input)) {
            $this->input = $input;
            $this->output = $output;
            $this->style = new SymfonyStyle($input, $output);
            $this->startup();
        }

        $this->buildTableTable($this->chooseTable());

        if ($this->style->confirm('Create another table?')) {
            return $this->execute($input, $output);
        }

        return Command::SUCCESS;
    }

    protected function buildTableTable(string $table): void
    {

        /** @var \mysqli $connection */
        $connection = $this->getConnection();

        /* Table Name */
        $table_name = ucfirst($table) . 'Table';

        /* Search for a column id */
        $col_prepared_stmt = $connection->prepare('SHOW INDEX FROM ' . $table . ' WHERE Key_name = "PRIMARY";');
        $col_prepared_stmt->execute();
        $columns = $col_prepared_stmt->get_result();
        $helper = [];
        while ($row = $columns->fetch_array(MYSQLI_NUM)) {
            $helper[] = $row[4];
        }

        $index = $this->style->choice('A primary id is required. Please select this from the following primary keys of the table', $helper);
        $this->style->info(sprintf('Primary Key "%s" is selected', $index));


        /* Get the type of the selected index */
        $stmt = $connection->prepare('SHOW FIELDS FROM ' . $table . ' FROM ' . $this->selected_database . ' WHERE Field = ?');
        $stmt->bind_param('s', $index);
        $stmt->execute();
        $index_type = $this->getPrimaryPropertyTranslatedType($stmt->get_result()->fetch_array(MYSQLI_NUM)[1]);


        $this->writeTableClass($this->storage . '/' . $table_name . '.php', [
            ':namespace:'        => $namespace ?? 'App',
            ':create_date_time:' => date('d.m.y H:i:s'),
            ':table:'            => $table,
            ':database:'         => $this->selected_database,
            ':class_name:'       => $table_name,
            ':id_column:'        => $index,
            ':id_column_type:'    => $index_type
        ]);
    }


    private function getPrimaryPropertyTranslatedType($property_type): string
    {
        if (
            str_starts_with($property_type, 'int(') ||
            str_starts_with($property_type, 'tinyint') ||
            str_starts_with($property_type, 'smallint')) {
            return 'int';
        }

        if (
            str_starts_with($property_type, 'float')
        ) {
            return 'float';
        }

        if (
            str_starts_with($property_type, 'varchar') ||
            str_starts_with($property_type, 'tinytext') ||
            str_starts_with($property_type, 'bigtext') ||
            str_starts_with($property_type, 'text')
        ) {
            return 'string';
        }


        return 'mixed';
    }
}