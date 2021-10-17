<?php

declare(strict_types=1);

namespace basteyy\MedooOrm;

class Entity
{
    const TYPE_CAST_INT = 'int';
    const TYPE_CAST_BOOL = 'bool';
    const TYPE_CAST_STRING = 'string';
    const TYPE_CAST_SLUG = 'slug';
    const TYPE_CAST_DATETIME = 'datetime';
    const TYPE_CAST_DANGEROUS_RAW = 'raw';
    const TYPE_CAST_JSON = 'json';

    protected string $id_column;
    private array $columns;
    public array $__orig;

    protected array $typeCast = [];
    protected array $table_result_aliases = [];

    public function __construct(array $data, string $primaryIdColumn = null)
    {
        foreach($data as $key => $value) {
            if(is_array($value)) {
                $_key_length = strlen($key . '_');
                foreach ($value as $_col => $_val) {
                    $_value[substr($_col, $_key_length)] = $_val;
                }

                if(isset($this->table_result_aliases, $this->table_result_aliases[$key])) {
                    $key = $this->table_result_aliases[$key];
                }

                $this->{$key} = new ($this->_entityName($key))($_value);
            } else {
                $this->columns[] = $key;
                $this->{$key} = $this->__typeCast($value, $key);
                $this->__orig[$key] = $value;
            }
        }

        if($primaryIdColumn !== null ) {
            $this->id_column = $primaryIdColumn;

            if(!isset($this->{$this->id_column}) && !isset($this->__new) ) {
                throw new \Exception(sprintf('Incomplete dataset. Primary ID %s is missing', $this->id_column));
            }
        }

        //$this->data = $data;
    }


    private function _entityName($key) {

        // Sarch for add
        $class_name = ucfirst($key);

        if(class_exists(__NAMESPACE__ . '\\' . $class_name)) {
            return __NAMESPACE__ . '\\' . $class_name;
        }

        if(class_exists(__NAMESPACE__ . '\\' . $class_name .'s')) {
            return __NAMESPACE__ . '\\' . $class_name;
        }

        if(class_exists(__NAMESPACE__ . '\\' . $class_name . 'Entity')) {
            return __NAMESPACE__ . '\\' . $class_name . 'Entity';
        }

        if(class_exists(__NAMESPACE__ . '\\' . $class_name . 'sEntity')) {
            return __NAMESPACE__ . '\\' . $class_name . 'sEntity';
        }

        #varDebug(__NAMESPACE__ . '\\Entity', class_exists( (string)__NAMESPACE__ . '\\Entity'));

        return __NAMESPACE__ . '\\Entity';
    }


    public function getRawVar($key) {
        return $this->__orig[$key] ?? false;
    }

    public function setRawVar($key, $value) {
        $this->{$key} = $value;
    }

    /**
     * @throws \Exception
     */
    private function __typeCast($value, $key): mixed
    {
        return isset($this->typeCast[$key]) ? match ($this->typeCast[$key]) {
            self::TYPE_CAST_INT => (int) $value,
            self::TYPE_CAST_BOOL => (bool) $value,
            self::TYPE_CAST_STRING => (string) $value,
            self::TYPE_CAST_SLUG => $this->_slugify($value),
            self::TYPE_CAST_DATETIME => $value instanceof \DateTime ? $value : ($value === null ? null : new \DateTime($value)),
            self::TYPE_CAST_DANGEROUS_RAW => $value,
            self::TYPE_CAST_JSON => json_encode($value),
            default => $this->__sanitize($value)
        } : $this->__sanitize($value);
    }

    private function __sanitize (mixed $value) : mixed {

        if (is_array($value)) {
            foreach($value as $_key => $_value) {
                $value[$_key] = $this->__sanitize($_value);
            }
        }

        if(is_string($value)) {
            return filter_var($value, FILTER_SANITIZE_STRING, FILTER_SANITIZE_STRING);
            #return htmlspecialchars($value);
            #return htmlentities($value, ENT_QUOTES, 'UTF-8');
        }

        return $value;
    }

    /**
     * @param string $value
     * @return string
     * @see https://stackoverflow.com/a/38066136/10232729
     */
    private function _slugify(string $value) : string {
        $replace = ['<' => '', '>' => '', '-' => ' ', '&' => '', '"' => '', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A',             'Ä' => 'A', 'Å' => 'A', 'Ā' => 'A', 'Ą' => 'A', 'Ă' => 'A', 'Æ' => 'Ae', 'Ç' => 'C', 'Ć' => 'C', 'Č' => 'C', 'Ĉ' => 'C', 'Ċ' => 'C', 'Ď' => 'D', 'Đ' => 'D', 'Ð' => 'D', 'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ē' => 'E', 'Ę' => 'E', 'Ě' => 'E', 'Ĕ' => 'E', 'Ė' => 'E', 'Ĝ' => 'G', 'Ğ' => 'G', 'Ġ' => 'G', 'Ģ' => 'G', 'Ĥ' => 'H', 'Ħ' => 'H', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ī' => 'I', 'Ĩ' => 'I', 'Ĭ' => 'I', 'Į' => 'I', 'İ' => 'I', 'Ĳ' => 'IJ', 'Ĵ' => 'J', 'Ķ' => 'K', 'Ł' => 'K', 'Ľ' => 'K', 'Ĺ' => 'K', 'Ļ' => 'K', 'Ŀ' => 'K', 'Ñ' => 'N', 'Ń' => 'N', 'Ň' => 'N', 'Ņ' => 'N', 'Ŋ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'Oe', 'Ø' => 'O', 'Ō' => 'O', 'Ő' => 'O', 'Ŏ' => 'O', 'Œ' => 'OE', 'Ŕ' => 'R', 'Ř' => 'R', 'Ŗ' => 'R', 'Ś' => 'S', 'Š' => 'S', 'Ş' => 'S', 'Ŝ' => 'S', 'Ș' => 'S', 'Ť' => 'T', 'Ţ' => 'T', 'Ŧ' => 'T', 'Ț' => 'T', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'Ue', 'Ū' => 'U', 'Ů' => 'U', 'Ű' => 'U', 'Ŭ' => 'U', 'Ũ' => 'U', 'Ų' => 'U', 'Ŵ' => 'W', 'Ý' => 'Y', 'Ŷ' => 'Y', 'Ÿ' => 'Y', 'Ź' => 'Z', 'Ž' => 'Z', 'Ż' => 'Z', 'Þ' => 'T', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'ae', 'å' => 'a', 'ā' => 'a', 'ą' => 'a', 'ă' => 'a', 'æ' => 'ae', 'ç' => 'c', 'ć' => 'c', 'č' => 'c', 'ĉ' => 'c', 'ċ' => 'c', 'ď' => 'd', 'đ' => 'd', 'ð' => 'd', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ē' => 'e', 'ę' => 'e', 'ě' => 'e', 'ĕ' => 'e', 'ė' => 'e', 'ƒ' => 'f', 'ĝ' => 'g', 'ğ' => 'g', 'ġ' => 'g', 'ģ' => 'g', 'ĥ' => 'h', 'ħ' => 'h', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ī' => 'i', 'ĩ' => 'i', 'ĭ' => 'i', 'į' => 'i', 'ı' => 'i', 'ĳ' => 'ij', 'ĵ' => 'j', 'ķ' => 'k', 'ĸ' => 'k', 'ł' => 'l', 'ľ' => 'l', 'ĺ' => 'l', 'ļ' => 'l', 'ŀ' => 'l', 'ñ' => 'n', 'ń' => 'n', 'ň' => 'n', 'ņ' => 'n', 'ŉ' => 'n', 'ŋ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'oe', 'ø' => 'o', 'ō' => 'o', 'ő' => 'o', 'ŏ' => 'o', 'œ' => 'oe', 'ŕ' => 'r', 'ř' => 'r', 'ŗ' => 'r', 'š' => 's', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'ue', 'ū' => 'u', 'ů' => 'u', 'ű' => 'u', 'ŭ' => 'u', 'ũ' => 'u', 'ų' => 'u', 'ŵ' => 'w', 'ý' => 'y', 'ÿ' => 'y', 'ŷ' => 'y', 'ž' => 'z', 'ż' => 'z', 'ź' => 'z', 'þ' => 't', 'ß' => 'ss', 'ſ' => 'ss', 'ый' => 'iy', 'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 'Ё' => 'YO', 'Ж' => 'ZH', 'З' => 'Z', 'И' => 'I', 'Й' => 'Y', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O', 'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C', 'Ч' => 'CH', 'Ш' => 'SH', 'Щ' => 'SCH', 'Ъ' => '', 'Ы' => 'Y', 'Ь' => '', 'Э' => 'E', 'Ю' => 'YU', 'Я' => 'YA', 'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo', 'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya'];

        $value = $this->__sanitize($value);

        // make a human-readable string
        $value = strtr($value, $replace);

        // replace non letter or digits by -
        $value = preg_replace('~[^\pL\d.]+~u', '-', $value);

        // trim
        $value = trim($value, '-');

        // remove unwanted characters
        $value = preg_replace('~[^-\w.]+~', '', $value);

        return strtolower($value);
    }

    public function __set(string $name, $value): void
    {

        if (!in_array($name, $this->columns)) {
            $this->columns[] = $name;
        }
        $this->{$name} = $this->__typeCast($value, $name);
    }

    public function getPrimaryIdColumnName(): string
    {
        if(!isset($this->id_column)) {
            throw new \Exception('Primary ID is not configured inside the Entity Model. Change the Model or try to get it from the Table Model.');
        }
        return $this->id_column;
    }

    public function getPrimaryId() {
        if(!isset($this->id_column)) {
            throw new \Exception('Primary ID is not configured inside the Entity Model. Change the Model or try to get it from the Table Model.');
        }
        return $this->{$this->id_column};
    }

    public function getColumns() : array {
        return $this->columns;
    }

}
