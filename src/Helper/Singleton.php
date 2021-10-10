<?php
declare(strict_types=1);

namespace basteyy\MedooOrm\Helper;

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
     * @param array $config
     * @return Medoo|null
     * @throws \Exception
     */
    public static function getMedoo(array $config) : Medoo {

        if(self::$medoo === null) {

            // Required parameter
            foreach(['database', 'host', 'username', 'password'] as $setting) {
                if(!isset($config[$setting])) {
                    throw new \Exception(sprintf('Make sure you pass a config array which contains the %s!', ));
                }
            }

            // Add default connection type
            if(!isset($config['type'])) {
                $config['type'] = 'mysql';
            }

            // Add default charset
            if(!isset($config['charset'])) {
                $config['charset'] = 'utf8mb4';
            }

            // Add default port
            if(!isset($config['port'])) {
                $config['port'] = 3306;
            }

            self::$medoo = new Medoo($config);
        }

        return self::$medoo;
    }
}