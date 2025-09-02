<?php

declare(strict_types=1);

namespace FlagPal\FlagPal\Support;

class Arr
{
    public static function diffRecursive(array $array1, array $array2): array
    {
        $difference = [];

        foreach ($array1 as $key => $value) {
            if (! array_key_exists($key, $array2)) {
                $difference[$key] = $value;

                continue;
            }

            if (is_array($value) && is_array($array2[$key])) {
                $recursiveDiff = self::diffRecursive($value, $array2[$key]);

                // If there are any differences in the nested arrays, include the entire original array
                if (! empty($recursiveDiff)) {
                    $difference[$key] = $value;
                }
            } elseif ($value !== $array2[$key]) {
                $difference[$key] = $value;
            }
        }

        return $difference;
    }
}
