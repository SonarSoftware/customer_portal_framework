<?php

namespace SonarSoftware\CustomerPortalFramework\Helpers;

use Carbon\Carbon;

class CreditCardValidator
{
    protected static $cards = [
        // Debit cards must come first, since they have more specific patterns than their credit-card equivalents.

        CreditCardType::VISAELECTRON => [
            'type' => CreditCardType::VISAELECTRON,
            'pattern' => '/^4(026|17500|405|508|844|91[37])/',
            'length' => [16],
            'cvcLength' => [3],
            'luhn' => true,
        ],
        CreditCardType::MAESTRO => [
            'type' => CreditCardType::MAESTRO,
            'pattern' => '/^(5(018|0[23]|[68])|6(39|7))/',
            'length' => [12, 13, 14, 15, 16, 17, 18, 19],
            'cvcLength' => [3],
            'luhn' => true,
        ],
        CreditCardType::FORBRUGSFORENINGEN => [
            'type' => CreditCardType::FORBRUGSFORENINGEN,
            'pattern' => '/^600/',
            'length' => [16],
            'cvcLength' => [3],
            'luhn' => true,
        ],
        CreditCardType::DANKORT => [
            'type' => CreditCardType::DANKORT,
            'pattern' => '/^5019/',
            'length' => [16],
            'cvcLength' => [3],
            'luhn' => true,
        ],
        // Credit cards
        CreditCardType::VISA => [
            'type' => CreditCardType::VISA,
            'pattern' => '/^4/',
            'length' => [13, 16, 19],
            'cvcLength' => [3],
            'luhn' => true,
        ],
        CreditCardType::MASTERCARD => [
            'type' => CreditCardType::MASTERCARD,
            'pattern' => '/^(5[0-5]|(222[1-9]|22[3-9][0-9]|2[3-6][0-9]{2}|27[01][0-9]|2720))/', // 2221-2720, 51-55
            'length' => [16],
            'cvcLength' => [3],
            'luhn' => true,
        ],
        CreditCardType::AMEX => [
            'type' => CreditCardType::AMEX,
            'pattern' => '/^3[47]/',
            'format' => '/(\d{1,4})(\d{1,6})?(\d{1,5})?/',
            'length' => [15],
            'cvcLength' => [3, 4],
            'luhn' => true,
        ],
        CreditCardType::DINERSCLUB => [
            'type' => CreditCardType::DINERSCLUB,
            'pattern' => '/^3[0689]/',
            'length' => [14],
            'cvcLength' => [3],
            'luhn' => true,
        ],
        CreditCardType::DISCOVER => [
            'type' => CreditCardType::DISCOVER,
            'pattern' => '/^6([045]|22)/',
            'length' => [16],
            'cvcLength' => [3],
            'luhn' => true,
        ],
        CreditCardType::UNIONPAY => [
            'type' => CreditCardType::UNIONPAY,
            'pattern' => '/^(62|88)/',
            'length' => [16, 17, 18, 19],
            'cvcLength' => [3],
            'luhn' => false,
        ],
        CreditCardType::JCB => [
            'type' => CreditCardType::JCB,
            'pattern' => '/^35/',
            'length' => [16],
            'cvcLength' => [3],
            'luhn' => true,
        ],
    ];

    /**
     * @param $number
     * @return array
     */
    public static function validCreditCard($number): array
    {
        $ret = [
            'valid' => false,
            'number' => '',
            'type' => '',
        ];

        // Strip non-numeric characters
        $number = strip_non_numeric($number);

        $type = self::creditCardType($number);

        if (isset(self::$cards[$type]) && self::validCard($number, $type)) {
            return [
                'valid' => true,
                'number' => $number,
                'type' => $type,
            ];
        }

        return $ret;
    }

    protected static function creditCardType($number): string
    {
        foreach (self::$cards as $type => $card) {
            if (preg_match($card['pattern'], $number)) {
                return $type;
            }
        }

        return '';
    }

    protected static function validCard($number, $type): bool
    {
        return (
            self::validPattern($number, $type)
            && self::validLength($number, $type)
            && self::validLuhn($number, $type)
        );
    }

    protected static function validPattern($number, $type): bool|int
    {
        return preg_match(self::$cards[$type]['pattern'], $number);
    }

    protected static function validLength($number, $type): bool
    {
        foreach (self::$cards[$type]['length'] as $length) {
            if (strlen($number) == $length) {
                return true;
            }
        }

        return false;
    }

    protected static function validLuhn($number, $type): bool
    {
        if (!self::$cards[$type]['luhn']) {
            return true;
        } else {
            return self::luhnCheck($number);
        }
    }

    protected static function luhnCheck($number): bool
    {
        $checksum = 0;
        for ($i = (2 - (strlen($number) % 2)); $i <= strlen($number); $i += 2) {
            $checksum += (int)($number[$i - 1]);
        }

        // Analyze odd digits in even length strings or even digits in odd length strings.
        for ($i = (strlen($number) % 2) + 1; $i < strlen($number); $i += 2) {
            $digit = (int)($number[$i - 1]) * 2;
            if ($digit < 10) {
                $checksum += $digit;
            } else {
                $checksum += ($digit - 9);
            }
        }

        return ($checksum % 10) == 0;
    }

    public static function validCvc($cvc, $type): bool
    {
        return (ctype_digit($cvc) && array_key_exists($type, self::$cards) && self::validCvcLength($cvc, $type));
    }

    protected static function validCvcLength($cvc, $type): bool
    {
        foreach (self::$cards[$type]['cvcLength'] as $length) {
            if (strlen($cvc) == $length) {
                return true;
            }
        }

        return false;
    }

    public static function validDate($year, $month): array
    {
        $validResponse = [
            'valid' => true,
            'message' => '',
        ];

        $exprMonth = sprintf("%02d", $month);
        if ($exprMonth < 1 || $exprMonth > 12) {
            return [
                'valid' => false,
                'message' => 'The expiration month is invalid.',
            ];
        }

        $exprYear = $year;
        if (strlen($exprYear) !== 4) {
            return [
                'valid' => false,
                'message' => 'You must input a 4 digit year.',
            ];
        }

        $now = Carbon::now();
        if ($now->year < $exprYear) {
            return $validResponse;
        }

        if ($now->year === $exprYear && $now->month <= $exprMonth) {
            return $validResponse;
        }

        return [
            'valid' => false,
            'message' => 'Expiration date is invalid.',
        ];
    }
}
