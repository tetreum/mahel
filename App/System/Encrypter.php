<?php

namespace App\System;

class Encrypter
{
    public static $key = null;
    public static $ivSize = null;
    public static $iv = null;

    public static function setKeys ($keys) {
        self::$key = $keys["key"];
        self::$ivSize = $keys["ivSize"];
        self::$iv = base64_decode($keys["iv"]);
    }

    /**
     * Generate a random string, using a cryptographically secure
     * pseudorandom number generator (random_int)
     *
     * For PHP 7, random_int is a PHP core function
     * For PHP 5.x, depends on https://github.com/paragonie/random_compat
     *
     * @param int $length      How many characters do we want?
     * @param string $keyspace A string of all possible characters
     *                         to select from
     * @return string
     */
    public static function generateRandomString($length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ')
    {
        $str = '';
        $max = mb_strlen($keyspace, '8bit') - 1;
        for ($i = 0; $i < $length; ++$i) {
            $str .= $keyspace[random_int(0, $max)];
        }
        return $str;
    }

    public static function strToHex($string) {
        $hexstr = unpack('H*', $string);
        return array_shift($hexstr);
    }

    public static function generateKeys ()
    {
        $ivSize = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);

        return [
            "key" => pack('H*', self::strToHex(self::generateRandomString(32))),
            "ivSize" => $ivSize,
            "iv" => base64_encode(mcrypt_create_iv($ivSize, MCRYPT_RAND))
        ];
    }

    public static function encrypt ($string)
    {
        $ciphertext = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, self::$key,
            $string, MCRYPT_MODE_CBC, self::$iv);

        # encode the resulting cipher text so it can be represented by a string
        return base64_encode(self::$iv . $ciphertext);
    }

    public static function decrypt ($b64)
    {
        $ciphertext_dec = base64_decode($b64);

        # retrieves the IV, iv_size should be created using mcrypt_get_iv_size()
        $iv_dec = substr($ciphertext_dec, 0, self::$ivSize);

        # retrieves the cipher text (everything except the $iv_size in the front)
        $ciphertext_dec = substr($ciphertext_dec, self::$ivSize);

        # may remove 00h valued characters from end of plain text
        return mcrypt_decrypt(MCRYPT_RIJNDAEL_128, self::$key,
            $ciphertext_dec, MCRYPT_MODE_CBC, $iv_dec);
    }
}