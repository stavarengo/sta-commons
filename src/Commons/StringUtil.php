<?php

namespace Sta\Commons;

use Sta\Commons\Exception\InvalidWordSeparator;
use Sta\Commons\Exception\PsrImplementationMissing;
use Sta\Commons\Exception\WeDontKnowHowToRecognizePostalCodeFromThisCountry;

class StringUtil
{
    public static function getHashTags($string)
    {
        if (!$string) {
            return [];
        }

        $matches = [];
        preg_match_all('/#[\\p{L}0-9_]+/u', $string, $matches);

        if (!$matches || !$matches[0]) {
            return [];
        }

        $allHashTags = array_map(
            function ($str) {
                return ltrim($str, '#');
            },
            $matches[0]
        );

        return $allHashTags;
    }

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
            $countryCodeWithTwoDigits = substr($phone, 0, 2);
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
        $url = preg_replace('/' . $regex . '/i', '', $url);
        $url = trim($url, ' ' . $allWordsSeparatorsTogether);

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

    /**
     * @param string $text
     *  The string that may contain postal codes.
     *
     * @param string $countryTwoLettersCode
     *  The country from where you want to extract its postal codes from a string.
     *
     * @return string[]
     *  We are going to return all the postal codes found, already formatted the way it is expected by the target
     *  country's official post office.
     */
    public static function getPostalCodeFromString(string $text, string $countryTwoLettersCode): array
    {
        $brCallback = function (array $regexMatchItem): ?string {
            $allMatchesOnlyNumbers = array_filter(
                array_map(
                    function (string $item) {
                        return preg_replace('/\D/', '', $item);
                    },
                    $regexMatchItem
                )
            );

            $postalCode = null;
            foreach ($allMatchesOnlyNumbers as $item) {
                if (strlen($item) == 8) {
                    $postalCode = $item;
                    break;
                }

                if (strlen($item) == 7) {
                    $postalCode = "0$item";
                }
            }

            if ($postalCode) {
                $postalCode = preg_replace('/(\d{5})(\d{3})/', '$1-$2', $postalCode);
            }

            return $postalCode;
        };

        $countryTwoLettersCode = strtoupper($countryTwoLettersCode);
        $regexToMatchPostalCode = [
            'BR' => [
                '/[\d .-]+/' => $brCallback
//                '/(^|\D)\d{2}[ .-]?\d{3}[ .-]?\d{3}(\D|$)/' => $brCallback,
//                '/(^|\D)\d{2}[ .-]?\d{3}- \d{3}(\D|$)/' => $brCallback,
//                '/(^|\D)\d{6}[ .-]?\d{2}(\D|$)/' => $brCallback,
//                '/(^|\D)\d{4}[ .-]?\d{4}(\D|$)/' => $brCallback,
//                '/(^|\D)\d{5} ?- ?\d{3}(\D|$)/' => $brCallback,
//                '/(^|\D)\d{5}[ .-]?\d{2}(\D|$)/' => $brCallback,
//                '/(^|\D)\d{4}[ .-]?\d{3}(\D|$)/' => $brCallback,
            ]
        ];

        if (!isset($regexToMatchPostalCode[$countryTwoLettersCode])) {
            throw new WeDontKnowHowToRecognizePostalCodeFromThisCountry(
                sprintf(
                    'We don\'t know how to recognize postal codes from this country yet. The supported countries are: "%s".',
                    implode('", "', array_keys($regexToMatchPostalCode))
                )
            );
        }

        $regexPatterns = $regexToMatchPostalCode[$countryTwoLettersCode];
        $allPostalCode = [];

        foreach ($regexPatterns as $regex => $callback) {
            $matches = [];
            if (!preg_match_all($regex, $text, $matches)) {
                continue;
            }
            foreach ($matches as $key => $matchItem) {
                $matchItem = preg_replace('/\D/', '', $matchItem);

                $allPostalCode[] = $callback($matchItem);
            }
        }
        $allPostalCode = array_values(array_unique(array_filter($allPostalCode)));

        return $allPostalCode;
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
        $regex = sprintf('/[(+0-9][0-9 ()-+-.]{%s,}\b/', $minimumDigitsToBeConsideredAnPhoneNumber);

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
        $emailRegex = '/(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){255,})(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){65,}@)(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22))(?:\.(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-[a-z0-9]+)*\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-[a-z0-9]+)*)|(?:\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\]))/iD';

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
     * The text you wanna extarct the URLs from.
     *
     * @param string $classImplementingPsr
     *
     *
     * @return \Psr\Http\Message\UriInterface[]
     */
    public static function getUrlsFromString(string $text, string $classImplementingPsr = '\GuzzleHttp\Psr7\Uri'): array
    {
        if (!class_exists($classImplementingPsr)) {
            throw new PsrImplementationMissing(
                sprintf(
                    'The classe "%s" does not exists. Please install GuzzleHttp or any other PSR-7 implementation you prefer.',
                    $classImplementingPsr
                )
            );
        }

        // @see https://mathiasbynens.be/demo/url-regex
        $regex = '/(((http|ftp|https):\/{2})?(([0-9a-z_-]+\.)+(aero|asia|biz|cat|com|coop|edu|gov|info|int|jobs|mil|mobi|museum|name|net|org|pro|tel|travel|ac|ad|ae|af|ag|ai|al|am|an|ao|aq|ar|as|at|au|aw|ax|az|ba|bb|bd|be|bf|bg|bh|bi|bj|bm|bn|bo|br|bs|bt|bv|bw|by|bz|ca|cc|cd|cf|cg|ch|ci|ck|cl|cm|cn|co|cr|cu|cv|cx|cy|cz|cz|de|dj|dk|dm|do|dz|ec|ee|eg|er|es|et|eu|fi|fj|fk|fm|fo|fr|ga|gb|gd|ge|gf|gg|gh|gi|gl|gm|gn|gp|gq|gr|gs|gt|gu|gw|gy|hk|hm|hn|hr|ht|hu|id|ie|il|im|in|io|iq|ir|is|it|je|jm|jo|jp|ke|kg|kh|ki|km|kn|kp|kr|kw|ky|kz|la|lb|lc|li|lk|lr|ls|lt|lu|lv|ly|ma|mc|md|me|mg|mh|mk|ml|mn|mn|mo|mp|mr|ms|mt|mu|mv|mw|mx|my|mz|na|nc|ne|nf|ng|ni|nl|no|np|nr|nu|nz|nom|pa|pe|pf|pg|ph|pk|pl|pm|pn|pr|ps|pt|pw|py|qa|re|ra|rs|ru|rw|sa|sb|sc|sd|se|sg|sh|si|sj|sj|sk|sl|sm|sn|so|sr|st|su|sv|sy|sz|tc|td|tf|tg|th|tj|tk|tl|tm|tn|to|tp|tr|tt|tv|tw|tz|ua|ug|uk|us|uy|uz|va|vc|ve|vg|vi|vn|vu|wf|ws|ye|yt|yu|za|zm|zw|arpa)(:[0-9]+)?((\/([~0-9a-zA-Z\#\+\%@\.\/_-]+))?(\?[0-9a-zA-Z\+\%@\/&\[\];=_-]+)?)?))\b/imuS';

        if (!preg_match_all($regex, $text, $matches)) {
            return [];
        }

        return array_map(
            function (string $uri) use ($classImplementingPsr) {
                if (!preg_match('_https?://_', $uri)) {
                    $uri = 'http://' . $uri;
                }
                return new $classImplementingPsr($uri);

            },
            $matches[0]
        );
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
}
