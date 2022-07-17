<?php declare(strict_types=1);
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

class CreateTableWithEntityCommand extends Command
{

    /** Traits contains all the required startup logic */
    use StartupTrait;

    /** Trait contains all the writing logic */
    use ClassWriterTrait;

    /**
     * @var string
     */
    protected static $defaultName = 'model:full';

    /**
     * @var string $defaultDescription Description of this command
     */
    protected static $defaultDescription = 'Create Table-Class and Entity-Class by a table from your database';

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

        return Command::SUCCESS;
    }
}