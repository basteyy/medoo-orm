<?php declare(strict_types=1);
/**
 * This file is part of the Medoo-ORM Script.
 *
 * @version 1.0.0
 * @package https://github.com/basteyy/medoo-orm
 * @author basteyy <sebastian@xzit.online>
 * @license Attribution-NonCommercial-ShareAlike 4.0 International
 */

namespace basteyy\MedooOrm\Helper;

use Exception;
use Medoo\Medoo;

/**
 * This is just a helper singleton implementation in case, you nether user a custom medoo-connection or dpendecy injection
 */
final class Singleton
{
    /** @var Medoo|null The medoo instance */
    private static ?Medoo $medoo;

    /**
     * Get the medoo instance (or create it, if missing)
     * @param array|null $config
     * @return Medoo
     * @throws Exception
     */
    public static function getMedoo(?array $config = []): Medoo
    {

        if (self::$medoo === null) {

            if (!$config) {
                throw new Exception('No config data provided.');
            }

            // Required parameter
            foreach (['database', 'host', 'username', 'password'] as $setting) {
                if (!isset($config[$setting])) {
                    throw new Exception(sprintf('Make sure you pass a config array which contains the %s!', $setting));
                }
            }

            // Add default connection type
            if (!isset($config['type'])) {
                $config['type'] = 'mysql';
            }

            // Add default charset
            if (!isset($config['charset'])) {
                $config['charset'] = 'utf8mb4';
            }

            // Add default port
            if (!isset($config['port'])) {
                $config['port'] = 3306;
            }

            self::setMedoo(new Medoo($config));
        }

        return self::$medoo;
    }

    public static function setMedoo(Medoo $medoo): void
    {
        if(!isset(self::$medoo)) {
            self::$medoo = $medoo;
        }
    }
}