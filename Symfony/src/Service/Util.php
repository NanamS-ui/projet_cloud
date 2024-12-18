<?php
namespace App\Service;
class Util{
    public static function generateToken(int $length = 16):string
    {
        return substr(bin2hex(random_bytes($length/2)),0,$length);
    }
    public static function generatePin(int $length = 4):int
    {
        $min = pow(10,$length - 1 );
        $max = pow(10,$length ) - 1;
        return random_int($min,$max);
    }
}