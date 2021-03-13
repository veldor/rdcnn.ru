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
    public static function convertToUTF($text)
    {
        return iconv('windows-1251', 'utf-8', $text);
    }

    public static function getFileName(string $answer)
    {
        return substr($answer, strripos($answer, '\\') + 1);
    }

    public static function clearText(string $content)
    {
        return preg_replace('/[^(\x20-\x7F)]*/','', $content);
    }

    /**
     * @param string $content
     * @return string|null
     */
    public static function findExecutionNumber(?string $content): ?string
    {
        $matches = null;
        if(preg_match('/LO([ A\d]+)0DA/', $content, $matches)){
            return trim($matches[1]);
        }
        return null;
    }

    /**
     * <b>Получение имени и отчества пациента</b>
     * @param $name
     * @return string|null
     */
    public static function handlePersonals($name)
    {
        if ($data = self::personalsToArray($name)) {
            if (is_array($data)) {
                return "{$data['name']} {$data['fname']}";
            }
            return $data;

        }
        return $name;
    }

    /**
     * @param $string
     * @return array|string
     */
    public static function personalsToArray($string)
    {
        // извлекаю имя и отчество из персональных данных
        $result = explode(' ', $string);
        if (count($result) === 3) {
            return ['lname' => $result[0], 'name' => $result[1], 'fname' => $result[2]];
        }
        return $string;
    }

    public static function startsWith( $haystack, $needle ): bool
    {
        $length = strlen( $needle );
        return strpos($haystack, $needle) === 0;
    }

    public static function endsWith( $haystack, $needle ): bool
    {
        $length = strlen( $needle );
        if( !$length ) {
            return true;
        }
        return substr( $haystack, -$length ) === $needle;
    }

    /**
     * @param $emailsString
     * @return array
     */
    public static function extractEmails($emailsString): array
    {
        $answer = [];
        $emailsArray = preg_split("/[,; ]/", $emailsString);
        foreach ($emailsArray as $item) {
            $emailItem = trim($item);
            if(!empty($emailItem) && filter_var($emailItem, FILTER_VALIDATE_EMAIL)){
                $answer[] = $emailItem;
            }
        }
        return $answer;
    }
}