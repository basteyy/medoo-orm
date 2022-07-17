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

trait ClassWriterTrait
{
    protected function writeEntityClass(string $target_path, array $data): void
    {
        if (!file_exists($target_path) || $this->style->confirm(sprintf('EntityClass exists already. Overwrite %s?', $target_path))) {

            $template = ORM_ROOT . '/src/Templates/EntityClass.php.tpl';

            if (!file_exists($template)) {
                throw new \Exception(sprintf('Template for entity not found at "%s"', $template));
            }

            $content = file_get_contents($template);

            foreach ($data as $replace => $value) {
                $content = str_replace($replace, $value, $content);
            }

            file_put_contents($target_path, $content);

            $this->style->success('Entity is written to ' . $target_path . '.');
        }
    }

    protected function writeTableClass(string $target_path, array $data): void
    {
        if (!file_exists($target_path) || $this->style->confirm(sprintf('TableClass exists already. Overwrite %s?', $target_path))) {

            $template = ORM_ROOT . '/src/Templates/TableClass.php.tpl';

            if (!file_exists($template)) {
                throw new \Exception(sprintf('Template for entity not found at "%s"', $template));
            }

            $content = file_get_contents($template);

            foreach ($data as $replace => $value) {
                $content = str_replace($replace, $value, $content);
            }

            file_put_contents($target_path, $content);

            $this->style->success('Entity is written to ' . $target_path . '.');
        }
    }
}