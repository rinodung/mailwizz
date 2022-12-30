<?php declare(strict_types=1);
if (!defined('MW_PATH')) {
    exit('No direct script access allowed');
}

/**
 * StringHelper
 *
 * @package MailWizz EMA
 * @author MailWizz Development Team <support@mailwizz.com>
 * @link https://www.mailwizz.com/
 * @copyright MailWizz EMA (https://www.mailwizz.com)
 * @license https://www.mailwizz.com/license/
 * @since 1.0
 */

class StringHelper
{
    /**
     * @param string $str
     * @param bool $startUppercase
     *
     * @return string
     */
    public static function simpleCamelCase(string $str, bool $startUppercase = true): string
    {
        $str = (string)str_replace(['_', '-'], ' ', $str);
        $str = ucwords($str);
        $str = (string)str_replace(' ', '', $str);
        $str[0] = $startUppercase ? strtoupper((string)$str[0]) : strtolower((string)$str[0]);

        return $str;
    }

    /**
     * @param string $compare
     * @param string $against
     *
     * @return bool
     */
    public static function isEqual(string $compare, string $against): bool
    {
        if ($compare !== $against) {
            return false;
        }

        $lengthFunction = CommonHelper::functionExists('mb_strlen') ? 'mb_strlen' : 'strlen';

        if ($lengthFunction($compare) !== $lengthFunction($against)) {
            return false;
        }

        $result = 0;

        for ($i = 0; $i < $lengthFunction($compare); $i++) {
            $result |= ord($compare[$i]) ^ ord($against[$i]);
        }

        return $result == 0;
    }

    /**
     * @param int $length
     * @param bool $lowerCaseOnly
     * @param bool $lettersOnly
     * @param bool $numbersOnly
     *
     * @return string
     */
    public static function random(int $length = 13, bool $lowerCaseOnly = false, bool $lettersOnly = false, bool $numbersOnly = false): string
    {
        $pool = '';

        if (!$lettersOnly || $numbersOnly) {
            $pool .= '0123456789';
        }

        if (!$numbersOnly) {
            $pool .= 'abcdefghjklmnopqrstvwxyz';
        }

        if (!$lowerCaseOnly && !$numbersOnly) {
            $pool .= 'ABCDEFGHJKLMNOPQRSTVWXYZ';
        }

        if (empty($pool)) {
            $pool = '0123456789abcdefghjklmnopqrstvwxyzABCDEFGHJKLMNOPQRSTVWXYZ';
        }

        $str = '';
        for ($i=0; $i < $length; $i++) {
            $str .= substr($pool, rand(0, strlen($pool) -1), 1);
        }
        return $str;
    }

    /**
     * @return string
     */
    public static function randomSha1(): string
    {
        try {
            $str = random_bytes(20);
        } catch (Exception $e) {
            $str = self::random(20, true);
        }
        return sha1($str);
    }

    /**
     * @param string $string
     * @param int $length
     * @param string $elipse
     *
     * @return string
     */
    public static function truncateLength(string $string, int $length = 100, string $elipse = '...'): string
    {
        $length = (int)$length;
        $string = strip_tags(html_decode($string));
        $strlen = CommonHelper::functionExists('mb_strlen') ? 'mb_strlen' : 'strlen';
        $substr = CommonHelper::functionExists('mb_substr') ? 'mb_substr' : 'substr';
        if ($strlen($string) - $strlen($elipse) >= $length) {
            return $substr($string, 0, $length) . $elipse;
        }
        return $string;
    }

    /**
     * @param string $string
     *
     * @return string
     */
    public static function getTagFromString(string $string): string
    {
        $substr = CommonHelper::functionExists('mb_substr') ? 'mb_substr' : 'substr';
        $tagName = (string)preg_replace('/[^a-z0-9\s_]/six', '', $string);
        $tagName = (string)preg_replace('/\s{2,}/', ' ', $tagName);
        $tagName = (string)preg_replace('/_{2,}/', ' ', $tagName);
        $tagName = strtoupper((string)$tagName);
        $tagName = (string)str_replace('  ', ' ', $tagName);
        $tagName = (string)str_replace(' ', '_', $tagName);

        return trim((string)$tagName, ' _');
    }

    /**
     * @param string $tag
     *
     * @return array
     */
    public static function getTagParams(string $tag): array
    {
        $params = [];
        $tag = trim(html_decode($tag));
        if (preg_match_all('/([a-z0-9]+)=(\'|")([a-z0-9\s:\-_\/\\\]+)(\'|")/i', $tag, $matches)) {
            if (isset($matches[1], $matches[3]) && (is_countable($matches[1]) ? count($matches[1]) : 0) === (is_countable($matches[3]) ? count($matches[3]) : 0)) {
                for ($i = 0; $i < (is_countable($matches[1]) ? count($matches[1]) : 0); $i++) {
                    $params[$matches[1][$i]] = $matches[3][$i];
                }
            }
        }
        return $params;
    }

    /**
     * @param string $prefix
     * @param bool $moreEntropy
     *
     * @return string
     */
    public static function uniqid(string $prefix = '', bool $moreEntropy = false): string
    {
        $uniqid = self::random(2, true, true) . substr(uniqid('', true), -3) . self::random(5, true) . substr(uniqid(), -3);

        if (!empty($prefix)) {
            $uniqid = $prefix . $uniqid;
        }

        if (!empty($moreEntropy)) {
            $uniqid .= '.' . self::random(12, false, false, true);
        }

        return $uniqid;
    }

    /**
     * @param string $filePath
     *
     * @return bool
     */
    public static function fixFileEncoding(string $filePath): bool
    {
        if (!is_file($filePath)) {
            return false;
        }

        if (!($handle = fopen($filePath, 'r'))) {
            return false;
        }

        $sample = '';
        $line = 1;
        while (($buffer = @fgets($handle, 4096)) !== false && $line < 500) {
            $sample .= $buffer;
            $line++;
        }
        if (FileSystemHelper::isStreamResource($handle)) {
            fclose($handle);
        }

        // is utf-8 check 1
        if (CommonHelper::functionExists('mb_check_encoding') && mb_check_encoding($sample, 'UTF-8')) {
            return true;
        }

        // is utf-8 check 2
        if (self::isUtf8($sample)) {
            return true;
        }

        if (!CommonHelper::functionExists('mb_detect_encoding')) {
            return false;
        }

        $encodingList = [
            'UTF-8', 'UTF-32', 'UTF-32BE', 'UTF-32LE',
            'UTF-16', 'UTF-16BE', 'UTF-16LE', 'ISO-8859-1', 'WINDOWS-1252', 'ASCII',
        ];

        $encoding = mb_detect_encoding($sample, $encodingList, true);
        if ($encoding === 'UTF-8') {
            return true;
        }

        if (empty($encoding)) {
            return false; // what to do here?
        }

        if (!CommonHelper::functionExists('mb_convert_encoding')) {
            return false;
        }

        if (!is_writable($filePath)) {
            return false;
        }

        if (!($input = @file_get_contents($filePath))) {
            return false;
        }

        $input = mb_convert_encoding($input, 'UTF-8', $encoding);
        return (bool)file_put_contents($filePath, $input);
    }

    /**
     * @param string $string
     *
     * @return bool
     */
    public static function isUtf8(string $string): bool
    {
        // http://www.php.net/manual/en/function.mb-detect-encoding.php#68607
        return (bool)preg_match('%(?:
            [\xC2-\xDF][\x80-\xBF]                      # non-overlong 2-byte
            |\xE0[\xA0-\xBF][\x80-\xBF]                 # excluding overlongs
            |[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}          # straight 3-byte
            |\xED[\x80-\x9F][\x80-\xBF]                 # excluding surrogates
            |\xF0[\x90-\xBF][\x80-\xBF]{2}              # planes 1-3
            |[\xF1-\xF3][\x80-\xBF]{3}                  # planes 4-15
            |\xF4[\x80-\x8F][\x80-\xBF]{2}              # plane 16
            )+%xs', $string);
    }

    /**
     * @param string $content
     *
     * @return string
     */
    public static function decodeSurroundingTags(string $content): string
    {
        return (string)str_replace(
            [urlencode('['), urlencode(']'), urlencode('|'), urlencode('{'), urlencode('}')],
            ['[', ']', '|', '{', '}'],
            $content
        );
    }

    /**
     * @param string $str
     *
     * @return string
     */
    public static function normalizeTranslationString(string $str): string
    {
        $str = trim((string)str_replace(["\r\n", "\n", "\t", "\r"], ' ', $str));
        return (string)preg_replace('/\s{1,}/', ' ', $str);
    }

    /**
     * @param string $url
     *
     * @return string
     */
    public static function normalizeUrl(string $url): string
    {
        // if it has a query string
        if (
            strpos($url, '?')   !== false || strpos($url, '&') !== false ||
            strpos($url, '%3F') !== false || strpos($url, '%26') !== false
        ) {
            $replacements = [
                '&amp;'  => '&',
                '%5B'    => '[',
                '%5D'    => ']',
                '%26'    => '&',
                '%3D'    => '=',
                '%3F'    => '?',
                '%7C'    => '|',
                ' '      => '%20',
                '&utm;_' => '&utm_',
            ];
            $url = (string)str_replace(array_keys($replacements), array_values($replacements), $url);
        }
        return $url;
    }

    /**
     * @param string $content
     *
     * @return string
     */
    public static function normalizeUrlsInContent(string $content): string
    {
        $pattern = '/href(\s+)?=(\s+)?(\042|\047)(\s+)?(.*?)(\s+)?(\042|\047)/i';
        if (!preg_match_all($pattern, $content, $matches)) {
            return $content;
        }

        $matches = array_unique($matches[0]);
        $searchReplace = [];

        foreach ($matches as $match) {
            $normalized = self::normalizeUrl($match);
            if ($normalized != $match) {
                $searchReplace[$match] = $normalized;
            }
        }

        return str_ireplace(array_keys($searchReplace), array_values($searchReplace), $content);
    }

    /**
     * @param string $str
     *
     * @return string
     */
    public static function fromCamelCase(string $str): string
    {
        $str[0] = strtolower((string)$str[0]);
        return (string)preg_replace_callback('/([A-Z])/', function ($c) {
            return '_' . strtolower((string)$c[1]);
        }, $str);
    }

    /**
     * Translates a string with underscores
     * into camel case (e.g. first_name -> firstName)
     * http://paulferrett.com/2009/php-camel-case-functions/
     *
     * @param string $str String in underscore format
     * @param bool $capitalise_first_char If true, capitalise the first char in $str
     * @return string $str translated into camel caps
     */
    public static function toCamelCase(string $str, bool $capitalise_first_char = false): string
    {
        if ($capitalise_first_char) {
            $str[0] = strtoupper((string)$str[0]);
        }
        return (string)preg_replace_callback('/_([a-z])/', function ($c) {
            return strtoupper((string)$c[1]);
        }, $str);
    }

    /**
     * @param string $csvFile
     *
     * @return string
     */
    public static function detectCsvDelimiter(string $csvFile): string
    {
        $default = ',';

        if (empty($csvFile) || !is_file($csvFile)) {
            return $default;
        }

        if (!CommonHelper::functionExists('str_getcsv')) {
            return $default;
        }

        $delimiters = [
            ';'  => 0,
            ','  => 0,
            "\t" => 0,
            '|'  => 0,
        ];

        if (!($fp = fopen($csvFile, 'r'))) {
            return $default;
        }
        $firstLine = fgets($fp);
        if (FileSystemHelper::isStreamResource($fp)) {
            fclose($fp);
        }

        if (empty($firstLine)) {
            return $default;
        }

        foreach ($delimiters as $delimiter => $count) {
            $delimiters[$delimiter] = count(str_getcsv($firstLine, $delimiter));
        }

        if (max(array_values($delimiters)) == 0) {
            return $default;
        }

        $delimiter = array_search(max($delimiters), $delimiters);
        return !empty($delimiter) ? (string)$delimiter : $default;
    }

    /**
     * @param string $str
     * @param string $mask
     * @param int $count
     * @return string
     */
    public static function maskStringEnding(string $str, string $mask = 'x', int $count = 10): string
    {
        if (empty($str)) {
            return '';
        }

        return (string)substr_replace($str, str_repeat($mask, $count), -$count);
    }

    /**
     * @param string $email
     *
     * @return string
     */
    public static function maskEmailAddress(string $email): string
    {
        if (stripos($email, '@') === false) {
            return $email;
        }
        [$name, $domain] = explode('@', $email);

        // name
        $name   = (array)str_split((string)$name);
        $length = count($name);
        foreach ($name as $index => $letter) {
            if (!in_array($index, [0, $length-1])) {
                $name[$index] = '*';
            }
        }
        $name = implode('', $name);
        if (strlen($name) == 1) {
            $name = '*';
        }

        // domain tld
        $domain = explode('.', $domain);
        $tld    = array_pop($domain);
        $domain = implode('.', $domain);

        // domain name
        $domain = str_split($domain);
        $length = count($domain);
        foreach ($domain as $index => $letter) {
            if (!in_array($index, [0, $length-1])) {
                $domain[$index] = '*';
            }
        }
        $domain = implode('', $domain);
        if (strlen($domain) == 1) {
            $domain = '*';
        }

        return $name . '@' . ($domain . '.' . $tld);
    }

    /**
     * @param string $text
     *
     * @return string
     */
    public static function remove4BytesChars(string $text): string
    {
        $text = (string)preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $text);

        return (string)preg_replace('/\s{2,}/', ' ', $text);
    }

    /**
     * @param mixed $number
     *
     * @return string
     */
    public static function asPercentFloat($number): string
    {
        return (round((float)str_replace(',', '.', (string)$number), 2)) . '%';
    }

    /**
     * @param string $string
     *
     * @return bool
     */
    public static function isMd5(string $string): bool
    {
        return (bool)preg_match('/^[a-f0-9]{32}$/', $string);
    }

    /**
     * @param string $string
     *
     * @return string
     */
    public static function md5Once(string $string): string
    {
        return self::isMd5($string) ? (string)$string : md5($string);
    }

    /**
     * @param string $string
     *
     * @return bool
     */
    public static function isSha1(string $string): bool
    {
        return (bool)preg_match('/^[a-f0-9]{40}$/', $string);
    }

    /**
     * @param string $string
     *
     * @return string
     */
    public static function sha1Once(string $string): string
    {
        return self::isSha1($string) ? (string)$string : sha1($string);
    }

    /**
     * @param string $string
     *
     * @return string
     */
    public static function removeComparisonSignsPrefix(string $string): string
    {
        $string     = trim($string);
        $signs      = ['>=', '<=', '<>', '=', '>', '<']; // order matters
        $variations = [];

        foreach ($signs as $sign) {
            $variations[] = ($sign);
            $variations[] = preg_quote(urlencode($sign), '/');
            $variations[] = preg_quote(CHtml::encode($sign), '/');
        }

        $pattern = sprintf('/^(%s)/i', implode('|', $variations));

        return trim((string)preg_replace($pattern, '', $string));
    }

    /**
     * @param string $string
     * @param string $encryptionKey
     * @return string
     */
    public static function encrypt(string $string, string $encryptionKey): string
    {
        return self::getCryptCipher($encryptionKey)->encrypt($string);
    }

    /**
     * @param string $string
     * @param string $encryptionKey
     * @return string
     */
    public static function decrypt(string $string, string $encryptionKey): string
    {
        return self::getCryptCipher($encryptionKey)->decrypt($string);
    }

    /**
     * @param string $encryptionKey
     * @return \phpseclib\Crypt\AES
     */
    protected static function getCryptCipher(string $encryptionKey): phpseclib\Crypt\AES
    {
        $cipher = new \phpseclib\Crypt\AES();
        $cipher->setKeyLength(256);
        $cipher->setKey($encryptionKey);

        return $cipher;
    }
}
