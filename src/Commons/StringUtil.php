<?php
namespace Sta\Commons;

use Sta\Commons\Exception\InvalidWordSeparator;

class StringUtil
{
    /**
     * @param $url
     * @param string $wordSeparator
     * @param null $maxLength
     *
     * @return string
     */
    public static function normalizeUrl($url, $wordSeparator = '-', $maxLength = null)
    {
        $wordSeparatorAllowed = ['.', '-', '_'];

        if (!in_array($wordSeparator, $wordSeparatorAllowed)) {
            throw new InvalidWordSeparator("The word separator '$wordSeparator', is not allowed.");
        }

        $url = trim(mb_strtolower($url, 'UTF-8'));
        if ($maxLength !== null) {
            $url = mb_substr($url, 0, $maxLength, 'UTF-8');
        }

        $charsToReplace = [
            '  ' => ' ',
            ' ' => $wordSeparator,
            $wordSeparator . $wordSeparator => $wordSeparator,
            '--' => '-',
            '__' => '_',
            '_-_' => '-',
            '-_-' => '-',
            '_-' => '-',
            '-_' => '-',
            '&' => 'and',
        ];

        foreach ($charsToReplace as $search => $replace) {
            do {
                $url = str_ireplace($search, $replace, $url);
            } while (stripos($url, $search) !== false);
        }

        $url = self::removeAccents($url);

        $allWordsSeparatorsTogether = implode('', $wordSeparatorAllowed);

        //remove os caractres não alfa numericos e não permitidos na url
        $regex = 'a-z0-9' . preg_quote($allWordsSeparatorsTogether);
        $regex = '[^' . $regex . ']';
        $url   = preg_replace('/' . $regex . '/i', '', $url);
        $url   = trim($url, ' ' . $allWordsSeparatorsTogether);

        foreach ($charsToReplace as $search => $replace) {
            do {
                $url = str_ireplace($search, $replace, $url);
            } while (stripos($url, $search) !== false);
        }
        
        if ($maxLength) {
            while (mb_strlen($rawUrl = rawurldecode($url), 'UTF-8') > $maxLength) {
                $url = mb_substr($url, 0, mb_strlen($url) - 1);
            };
        } else {
            $rawUrl = rawurldecode($url);
        }

        return $rawUrl;
    }

    /**
     * @param string $esCreatorNetworkDescription
     * @param string $replacement
     *
     * @return string
     */
    public static function removeContactInformationFromString($esCreatorNetworkDescription, $replacement = '')
    {
        // Remove URLs
        $esCreatorNetworkDescription = preg_replace(
            '/[a-zA-Z]*[:\/\/]*[A-Za-z0-9\-_]+\.+[A-Za-z0-9\.\/%&=\?\-_]+/i',
            $replacement,
            $esCreatorNetworkDescription
        );
        // Remove emails
        $esCreatorNetworkDescription = preg_replace(
            '/[^@\s]*@[^@\s]*\.[^@\s]*/',
            $replacement,
            $esCreatorNetworkDescription
        );
        $esCreatorNetworkDescription = preg_replace('/[a-z]+?@/', "$replacement@", $esCreatorNetworkDescription);
        // Remove telefones
        $esCreatorNetworkDescription = preg_replace('/[0-9]{3}/', $replacement, $esCreatorNetworkDescription);

        return $esCreatorNetworkDescription;
    }

    /**
     * @param $string
     *
     * @return string
     */
    public static function removeAccents($string)
    {
        $array1 = [
            'á',
            'à',
            'â',
            'ã',
            'ä',
            'é',
            'è',
            'ê',
            'ë',
            'í',
            'ì',
            'î',
            'ï',
            'ó',
            'ò',
            'ô',
            'õ',
            'ö',
            'ú',
            'ù',
            'û',
            'ü',
            'ç',
            'Á',
            'À',
            'Â',
            'Ã',
            'Ä',
            'É',
            'È',
            'Ê',
            'Ë',
            'Í',
            'Ì',
            'Î',
            'Ï',
            'Ó',
            'Ò',
            'Ô',
            'Õ',
            'Ö',
            'Ú',
            'Ù',
            'Û',
            'Ü',
            'Ç',
        ];
        $array2 = [
            'a',
            'a',
            'a',
            'a',
            'a',
            'e',
            'e',
            'e',
            'e',
            'i',
            'i',
            'i',
            'i',
            'o',
            'o',
            'o',
            'o',
            'o',
            'u',
            'u',
            'u',
            'u',
            'c',
            'A',
            'A',
            'A',
            'A',
            'A',
            'E',
            'E',
            'E',
            'E',
            'I',
            'I',
            'I',
            'I',
            'O',
            'O',
            'O',
            'O',
            'O',
            'U',
            'U',
            'U',
            'U',
            'C',
        ];
        $string = str_replace($array1, $array2, $string);

        //remove os ascentos
        $string = iconv('UTF-8', 'US-ASCII//TRANSLIT', $string);

        return $string;
    }
}
