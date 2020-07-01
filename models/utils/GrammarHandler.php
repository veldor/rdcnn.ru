<?php


namespace app\models\utils;


class GrammarHandler
{
    /**
     * @param $filename
     * @return bool
     */
    public static function isPdf($filename): bool
    {
        return (!empty($filename) && strlen($filename) > 4 && substr($filename, strlen($filename) - 3) === 'pdf');
    }

    public static function getBaseFileName($file_name)
    {
        $pattern = '/^([aа]?\d+)/iu';
        if (preg_match($pattern, $file_name, $matches)) {
            return $matches[1];
        }
        return null;
    }

    public static function toLatin($name)
    {
        $input = ['А'];
        $replace = ['A'];
        return str_replace($input, $replace, self::my_mb_ucfirst(trim($name)));
    }

    /**
     * Перевод первого символа строки в верхний регистр для UTF-8 строк
     * @param $str
     * @return string
     */
    private static function my_mb_ucfirst($str): string
    {
        $fc = mb_strtoupper(mb_substr($str, 0, 1));
        return $fc.mb_substr($str, 1);
    }
}