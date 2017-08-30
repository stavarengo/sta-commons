<?php

namespace Sta\Commons;

use Sta\Commons\Exception\InvalidWordSeparator;

class StringUtil
{
    /**
     * Formata um número de telefone.
     * Créditos:
     *    Função original - http://www.danielkassner.com/2010/05/21/format-us-phone-number-using-php
     *    Adaptada por Rafael Stavarengo
     *
     * @param string $phone
     *        O telefone que será formatado.
     * @param boolean $convert
     *        Default true. Determina se as letras serão corespondidas em seus respectivos números.
     *        Exemplo:
     *            1-800-TERMINIX, vira (180) 0837-6464
     *            1-800-Flowers, vira (180) 0356-9377
     *            18-3666-Sony, vira (18) 3666-7669
     *
     * @return string
     */
    public static function formatPhone($phone, $convert = true)
    {
        // If we have not entered a phone number just return empty
        if (empty($phone)) {
            return '';
        }

        $startWithPlus = !!preg_match('/^\+/', $phone);
        // Strip out any extra characters that we do not need only keep letters and numbers
        $phone = preg_replace('/[^0-9A-Za-z]/', '', $phone);
        // Keep original phone in case of problems later on but without special characters
        $originalPhone = $phone;

        // Do we want to convert phone numbers with letters to their number equivalent?
        // Samples are: 1-800-TERMINIX, 1-800-FLOWERS, 1-800-Petmeds
        if ($convert == true && !is_numeric($phone)) {
            $replace = [
                '2' => ['a', 'b', 'c'],
                '3' => ['d', 'e', 'f'],
                '4' => ['g', 'h', 'i'],
                '5' => ['j', 'k', 'l'],
                '6' => ['m', 'n', 'o'],
                '7' => ['p', 'q', 'r', 's'],
                '8' => ['t', 'u', 'v'],
                '9' => ['w', 'x', 'y', 'z'],
            ];

            // Replace each letter with a number
            // Notice this is case insensitive with the str_ireplace instead of str_replace
            foreach ($replace as $digit => $letters) {
                $phone = str_ireplace($letters, $digit, $phone);
            }
        }

        $countryCode = null;
        if ($startWithPlus) {
            // Try to guess the country code from the string
            $countryCodeWithTreeDigits = substr($phone, 0, 3);
            $countryCodeWithTwoDigits  = substr($phone, 0, 2);
            if (CountriesCodes::isThisAnRecognizedPhoneCode($countryCodeWithTreeDigits)) {
                $countryCode = $countryCodeWithTreeDigits;
            } else if (CountriesCodes::isThisAnRecognizedPhoneCode($countryCodeWithTwoDigits)) {
                $countryCode = $countryCodeWithTwoDigits;
            }

            if ($countryCode) {
                $phone = preg_replace('/^' . preg_quote($countryCode) . '/', '', $phone);
            }
        }

        $length = strlen($phone);
        // 55 75 9 9288 8889
        switch ($length) {
            case 7:
            case 8:
                // Format: xxx-xxxx ou xxxx-xxxx
                $formattedNumber = preg_replace('/(.{3,4})(.{4})/', '$1-$2', $phone);
                break;
            case 9:
                // Format: x xxxx-xxxx
                $formattedNumber = preg_replace('/(.)(.{4})(.{4})/', '$1 $2-$3', $phone);
                break;
            case 10:
                // Format: (xx) xxxx-xxxx
                $formattedNumber = preg_replace('/(.{2})(.{4})(.{4})/', '($1) $2-$3', $phone);
                break;
            case 11:
                // Format: (xx) x xxxx-xxxx
                $formattedNumber = preg_replace('/(.{2})(.)(.{4})(.{4})/', '($1) $2 $3-$4', $phone);
                break;
            case 12:
                if ($startWithPlus) {
                    // Format: +xxx xxxx-xxxx
                    $formattedNumber = preg_replace('/(.{3})(.{4})(.{4})/', '($1) $2-$3', $phone);
                } else {
                    // Format: (xxx) xxxx-xxxx
                    $formattedNumber = preg_replace('/(.{3})(.{4})(.{4})/', '($1) $2-$3', $phone);
                }
                break;
            default:
                $formattedNumber = $originalPhone;
        }

        if ($countryCode) {
            $formattedNumber = "+$countryCode $formattedNumber";
        } else if ($startWithPlus) {
            $formattedNumber = "+$formattedNumber";
        }

        return $formattedNumber;
    }

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
     * @param string $text
     * @param int $minimumDigitsToBeConsideredAnPhoneNumber
     *
     * @return string[]
     */
    public static function getPhonesFromString(string $text, int $minimumDigitsToBeConsideredAnPhoneNumber = 7): array
    {
        $phonesFound = [];
        $regex       = sprintf('/[(+0-9][0-9 ()-+-.]{%s,}\b/', $minimumDigitsToBeConsideredAnPhoneNumber);

        if (preg_match_all($regex, $text, $matches)) {
            $phonesFound = array_filter(
                array_map('trim', $matches[0]),
                function ($item) use ($minimumDigitsToBeConsideredAnPhoneNumber) {
                    if (preg_match('/[a-zA-Z]/', $item)) {
                        return false;
                    }

                    $itemOnlyNumbers = preg_replace('/\D/', '', $item);
                    if (strlen($itemOnlyNumbers) < $minimumDigitsToBeConsideredAnPhoneNumber) {
                        return false;
                    }

//                    $item = preg_replace('/[^(+0-9]/', $item);

                    return !!$item;
                }
            );
        }

        return $phonesFound;
    }

    /**
     * @param string $text
     *
     * @return string[]
     */
    public static function getEmailsFromString(string $text): array
    {
        $emailsFound = [];
        $emailRegex  = '/(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){255,})(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){65,}@)(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22))(?:\.(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-[a-z0-9]+)*\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-[a-z0-9]+)*)|(?:\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\]))/iD';

        $textLines = explode("\n", $text);
        foreach ($textLines as $line) {
            $line = trim($line);
            if ($line && strpos($line, '@') && preg_match_all($emailRegex, $line, $matches)) {
                $emailsFound = array_merge($emailsFound, $matches[0]);
            }
        }

        return $emailsFound;
    }

    /**
     * @param string $text
     * @param string $replacement
     *
     * @return string
     */
    public static function removeContactInformationFromString(string $text, $replacement = ''): string
    {
        // Remove emails
        $text = preg_replace('/[^@\s]+@[^@\s]*\.[^@\s]*/', "$replacement@$replacement.$replacement", $text);

        // Remove URLs
        $text = preg_replace(
            '/[a-zA-Z]*[:\/\/]*[A-Za-z0-9\-_]+\.+[A-Za-z0-9\.\/%&=\?\-_]+/i',
            $replacement,
            $text
        );

        // Remove telefones
        $text = preg_replace('/[0-9]{3}/', $replacement, $text);

        return $text;
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
