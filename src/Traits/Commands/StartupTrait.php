<?php
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

namespace basteyy\MedooOrm\Traits\Commands;

use mysqli;
use mysqli_driver;
use Symfony\Component\Console\Question\Question;

trait StartupTrait {

    /** @var string $charset Charset of the connection */
    private string $charset;

    /** @var string $host Database Host (localhost, 127.0.0.1, db or rdbms for example */
    private string $host;

    /** @var int $port Port of the database host */
    private int $port;

    /** @var string $user Username for the database user */
    private string $user;

    /** @var string $password Passwort for username at the database */
    private string $password;

    /** @var string $namespace The namespace which is used in the entitiy file */
    private string $namespace;

    /** @var string $storage Path where the files are stored */
    private string $storage;

    /** @var string $connectionFile Filepath and filename of the file, where the connection will be saved */
    private string $connectionFile;

    /** @var mixed $selected_database The selected database */
    private string $selected_database;

    /** @var array $tableCache Caching Array for table names */
    private array $tableCache;

    /** @var array $databaseCache Caching Array for database names */
    private array $databaseCache;

    /** @var string $selected_table The current selected table */
    private string $selected_table;

    protected string $default_config_file_path;

    /**
     * Startup method to create a connection to the database
     * @return void
     */
    protected function startup() : void {

        $use_file = false;

        $this->default_config_file_path = sprintf('%s/database-config.json', ORM_ROOT);

        /* Config file via config-argument */
        if ($this->input->getArgument('config')) {

            if(file_exists($this->input->getArgument('config'))) {
                $use_file = true;
                $config_file = $this->input->getArgument('config');
            }

            if(!file_exists($this->input->getArgument('config'))) {
                $this->style->warning(sprintf('The provided config file was not found at %s. Fall back to interactive configuration.', $this->input->getArgument('config')));
            }
        }

        /* Config file at default location? */
        if (!$this->input->getArgument('config')) {
            if(file_exists($this->default_config_file_path)) {
                if($this->style->confirm(sprintf('Load config from %s?', $this->default_config_file_path))) {
                    $use_file = true;
                    $config_file = $this->default_config_file_path;
                }
            }
        }

        if($use_file && isset($config_file)) {
            $this->style->text(sprintf('Config will be loaded from %s', $config_file));
            $config = json_decode(file_get_contents($config_file), true);
        }

        $this->requestDatabaseConfig($config ?? []);

        if(!$use_file && $this->style->confirm('Do you like to store current database connection in a file?')) {

            $location = $this->style->askQuestion(new Question('Insert a location for the connection file', $this->default_config_file_path));

            if((file_exists($location) && $this->style->askQuestion(new Question(sprintf('At %s a config-file exists already? overwrite it?', $location), false)))
                || !file_exists($location)) {

                if (!isset($this->connection)) {
                    $this->createDatabaseConnection();
                }

                $this->writeConfigFile($location);
            }

        }

    }

    /**
     * Get the current database connection
     * @return mysqli
     */
    protected function getConnection() : mysqli {

        if (!isset($this->connection)) {
            $this->createDatabaseConnection();
        }

        return $this->connection;
    }

    /**
     * Establish the database connection
     * @return void
     */
    private function createDatabaseConnection() : void {

        if (!isset($this->connection)) {

            $driver = new mysqli_driver();
            $driver->report_mode = MYSQLI_REPORT_ALL & MYSQLI_REPORT_STRICT;

            /** @var mysqli connection */
            $this->connection = new mysqli(
                $this->host,
                $this->user,
                $this->password,
                $this->database ?? null,
                $this->port
            );
        }
    }

    /**
     * Request database connection information from the user
     * @param array|null $connection_data
     * @return void
     */
    protected function requestDatabaseConfig(?array $connection_data = []): void
    {
        if (isset($connection_data['charset'])) {
            $this->charset = $connection_data['charset'];
        } else {
            $this->charset = $this->style->askQuestion(new Question('Insert the database charset', 'utf8mb4'));
        }

        if (isset($connection_data['host'])) {
            $this->host = $connection_data['host'];
        } else {
            $this->host = $this->style->askQuestion(new Question('Insert the database host', 'db'));
        }

        if (isset($connection_data['port'])) {
            $this->port = $connection_data['port'];
        } else {
            $this->port = (int)$this->style->askQuestion(new Question(sprintf('Insert the database port for %s', $this->host), '3306'));
        }

        if (isset($connection_data['user'])) {
            $this->user = $connection_data['user'];
        } else {
            $this->user = $this->style->askQuestion(new Question(sprintf('Insert the username for %s:%s', $this->host, $this->port), 'db'));
        }

        if (isset($connection_data['password'])) {
            $this->password = $connection_data['password'];
        } else {
            $this->password = $this->style->askQuestion(new Question(sprintf('Password for the %s@%s:%s to the database', $this->user, $this->host, $this->port), 'db'));
        }

        if (isset($connection_data['namespace'])) {
            $this->namespace = $connection_data['namespace'];
        } else {
            $this->namespace = $this->style->askQuestion(new Question('Insert the Namespace for the entity'));
        }

        if (isset($connection_data['storage'])) {
            $this->storage = $connection_data['storage'];
        } else {
            $this->storage = $this->style->askQuestion(new Question('Where to you like to store the entity file?', dirname($this->connectionFile)));
        }

        if(isset($connection_data['selected_database'])) {
            $this->selectDatabase($connection_data['selected_database']);
        }
    }

    /**
     * Write the current config to $config_file_path or the default config file
     * @param string|null $config_file_path
     * @return void
     */
    protected function writeConfigFile(?string $config_file_path = null) : void {
        $path = $config_file_path ?? $this->default_config_file_path;

        file_put_contents($path,
            json_encode([
                'charset'           => $this->charset ?? null,
                'host'              => $this->host ?? null,
                'port'              => $this->port ?? null,
                'user'              => $this->user ?? null,
                'password'          => $this->password ?? null,
                'namespace'         => $this->namespace ?? null,
                'storage'           => $this->storage ?? null,
                'selected_database' => $this->selected_database ?? null,
            ]));

        $this->style->success(sprintf('Database Config is written to %s', $path));
    }

    /**
     * Logic to request a database from current connection and return it as string
     * @param bool|null $select_database_on_host
     * @return string
     */
    protected function chooseDatabase(?bool $select_database_on_host = true) : string {
        $database = $this->style ->choice('Select the database you want use.', $this->getDatabasesList());
        $this->style->info(sprintf('Database "%s" was chosen', $database));

        if($select_database_on_host) {
            $this->selectDatabase($database);
        }

        return $database;
    }

    /**
     * Select the database on the host
     * @param string $database
     * @return void
     */
    protected function selectDatabase(string $database) : void {
        $this->selected_database = $database;
        ($this->getConnection())->select_db($this->selected_database);
        $this->style->info(sprintf('Database "%s" is selected', $this->selected_database));
    }

    /**
     * Get all tables as an array
     * @return array
     */
    protected function getDatabasesList() : array {

        if(!isset($this->databaseCache)) {
            $d = $this->getConnection();
            $databases = $d->query('SHOW DATABASES;');
            while ($row = $databases->fetch_array(MYSQLI_NUM)) {
                $this->databaseCache[] = $row[0];
            }
        }

        return $this->databaseCache;
    }

    /**
     * Clears the database cache
     * @return void
     */
    protected function flushDatabaseList() : void {
        if(!isset($this->databaseCache)) {
            $this->databaseCache = [];
        }
    }

    /**
     * Logic to request a table from current connection and return it as string
     * @return string
     */
    protected function chooseTable() : string {

        if(!isset($this->selected_database)) {
            $this->chooseDatabase();
        }

        $table = $this->style->choice('Select the table you want to create the entity for', $this->getTablesList());
        $this->style->info(sprintf('Table "%s" is selected', $table));
        $this->selected_table = $table;
        return $table;
    }

    /**
     * Get all tables as an array
     * @return array
     */
    protected function getTablesList() : array {
        if(!isset($this->tableCache)) {
            $c = $this->getConnection();
            $tables = $c->query('SHOW TABLES;');
            while ($row = $tables->fetch_array(MYSQLI_NUM)) {
                $this->tableCache[] = $row[0];
            }
        }

        return $this->tableCache;
    }

    /**
     * Clears the table cache
     * @return void
     */
    protected function flushTableList() : void {
        if(!isset($this->tableCache)) {
            $this->tableCache = [];
        }
    }

}