<?php

function strToFloat($value)
{
    return is_float($value) ? $value : (float)str_replace([',', ' '], ['.', ''], trim(@(string)$value));
}

function ceilEx($val, $precision = 0)
{
    $val = (float)$val;
    $precision = (int)$precision;

    $result = round($val, $precision);
    if ($val - $result < EPSILON) {
        return $result;
    } else {
        return round($result + pow(10, -$precision), $precision);
    }
}

function floorEx($val, $precision = 0)
{
    $val = (float)$val;
    $precision = (int)$precision;

    $result = round($val, $precision);
    if ($result - $val < EPSILON) {
        return $result;
    } else {
        return round($result - pow(10, -$precision), $precision);
    }
}

function getIntFromArray($array, $key, $default = 0)
{
    return is_array($array) && isset($array[$key]) ? (int)$array[$key] : $default;
}

function getStrFromArray($array, $key, $default = '')
{
    return is_array($array) && isset($array[$key]) ? trim(@(string)$array[$key]) : $default;
}

function getBoolFromArray($array, $key, $default = false)
{
    if (is_array($array) && isset($array[$key])) {
        $s = strtolower(trim(@(string)$array[$key]));
        $result = $s && !in_array($s, ['false']);
    } else {
        $result = $default;
    }

    return $result;
}

function getFloatFromArray($array, $key, $default = 0)
{
    return is_array($array) && isset($array[$key]) ? strToFloat($array[$key]) : $default;
}

function getArrayFromArray($array, $key)
{
    return is_array($array) && isset($array[$key]) && is_array($array[$key]) ? $array[$key] : [];
}

function getPrimaryException(Exception $e)
{
    do {
        $result = $e;
        $e = $e->getPrevious();
    } while ($e);

    return $result;
}

function getPhoneCountryCode($s)
{
    $codes = [
        1 => 10, // США
        7 => 10, // Россия
        20 => 10, // Египет
        30 => 10, // Греция
        31 => 9, // Нидерланды
        32 => 9, // Бельгия
        33 => 9, // Франция
        34 => 9, // Испания
        39 => 10, // Италия
        40 => 9, // Румыния
        44 => 10, // Великобритания
        45 => 8, // Дания
        46 => 9, // Швеция
        47 => 8, // Норвегия
        48 => 9, // Польша
        49 => [10, 11], // Германия
        52 => 10, // Мексика
        54 => 11, // Аргентина
        64 => 9, // Новая Зеландия
        66 => 9, // Таиланд
        84 => 10, // Вьетнам
        86 => 11, // Китай
        90 => 10, // Турция
        351 => 9, // Португалия
        353 => 9, // Ирландия
        354 => 7, // Исландия
        357 => 8, // Кипр
        358 => 9, // Финляндия
        359 => 9, // Болгария
        370 => 8, // Литва
        371 => 8, // Латвия
        372 => [7, 8], // Эстония
        373 => 8, // Молдавия
        374 => 8, // Армения
        375 => 9, // Белоруссия
        380 => 9, // Украина
        382 => 8, // Черногория
        387 => 8, // Босния и Герцеговина
        420 => 9, // Чехия
        421 => 9, // Словакия
        886 => 9, // Тайвань
        971 => 9, // ОАЭ
        972 => 9, // Израиль
        992 => 9, // Таджикистан
        993 => 8, // Туркмения
        994 => 9, // Азербайджан
        995 => 9, // Грузия
        996 => 9, // Киргизия
        998 => 9, // Узбекистан
    ];

    if (!preg_match('/^\+\d+$/', $s)) {
        return false;
    }

    foreach ($codes as $code => $size) {
        $code = (string)$code;
        $codeLen = strlen($code);
        if (substr($s, 1, $codeLen) === $code) {
            list($min, $max) = is_array($size) ? $size : [$size, $size];
            $l = strlen($s) - 1 - $codeLen;
            if ($l >= $min && $l <= $max) {
                return $code;
            } else {
                return false;
            }
        }
    }

    return false;
}

function normalizePhone($s)
{
    $s = str_replace(['tel:', ':', '-', '(', ')', ' '], '', trim(@(string)$s));
    if (!preg_match('/^\+?\d+$/', $s)) {
        return false;
    }

    $c = $s[0];
    if ($c != '+') {
        $l = strlen($s);
        if ($l == 10 && $c != '0') {
            $s = '+7' . $s;
        } elseif ($l == 11 && ($c == '7' || $c == '8')) {
            $s = '+7' . substr($s, 1);
        } else {
            $s = '+' . $s;
        }
    }

    $code = getPhoneCountryCode($s);
    if (!$code) {
        return false;
    }

    return $s;
}

function e($s)
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
