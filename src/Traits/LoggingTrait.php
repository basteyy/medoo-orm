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

trait LoggingTrait
{
    protected mixed $logger;

    private bool $shutdown_method_Registered = false;
    private string $_log_stream = '';

    /**
     * Save a log message or an array of log messages to the log stream and register the shutdown function
     * @param string|array $log_message
     * @param string $log_level
     * @return void
     */
    protected function _log(string|array $log_message, string $log_level = 'DEBUG'): void
    {
        if (is_array($log_message)) {
            foreach ($log_message as $message) {
                $this->_log($message);
            }
        }

        if (is_string($log_message)) {

            $this->_log_stream .= sprintf('[%1$s] [%2$s] %3$s',
                date('Y-m-d H:i:s' . substr((string)microtime(), 1, 8)),
                $log_level,
                $log_message . PHP_EOL
            );

            if (!$this->shutdown_method_Registered) {
                register_shutdown_function(array($this, '_writeLogToFile'));
                $this->shutdown_method_Registered = true;
            }

        }
    }

    /**
     * Shutdown function to write the log stream to the file
     * @return void
     */
    public function _writeLogToFile(): void
    {
        $log_location = defined('MedooLogPath') ? MedooLogPath : ((defined('ROOT') ? ROOT : dirname(__DIR__, 2)) . '/log/');

        if (!is_dir($log_location)) {
            mkdir($log_location, 0777, true);
        }

        $log_file = $log_location . '/' . date('Y-m-d') . '-medoo_orm.log';

        $content = $this->_log_stream . (file_exists($log_file) ? file_get_contents($log_file) : '');

        file_put_contents($log_file, $content);
    }

}