<?php
/**
 * This file is part of the package go-crazy-legacy.
 * The script is nonfree software.
 * The usage is allowed only under the conditions from the license.
 *
 * @author basteyy <sebastian@xzit.online>
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
 * @author basteyy <sebastian@xzit.online>
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