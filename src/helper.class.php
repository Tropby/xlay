<?php

namespace XLay;

class Helper 
{
    static public function combineRects(array $r1, array $r2)
    {
        $r1[0] = min($r1[0], $r2[0]);
        $r1[1] = min($r1[1], $r2[1]);
        $r1[2] = max($r1[2], $r2[2]);
        $r1[3] = max($r1[3], $r2[3]);
        return $r1;
    }

    static public function getInt32(array & $data) : int
    {
        $result = 0;
        $result += next($data) << 0;
        $result += next($data) << 8;
        $result += next($data) << 16;
        $result += next($data) << 24;

        if($result >= 2147483648)
            	$result -= 4294967296;

        return $result;
    }

    static public function getUInt16(array & $data) : int
    {
        $result = 0;
        $result += next($data) << 0;
        $result += next($data) << 8;

        return $result;
    }

    static public function getUInt32(array & $data) : int
    {
        $result = 0;
        $result += next($data) << 0;
        $result += next($data) << 8;
        $result += next($data) << 16;
        $result += next($data) << 24;

        return $result;
    }

    static public function getUInt64(array & $data) : int
    {
        $result = 0;
        $result += next($data) << 0;
        $result += next($data) << 8;
        $result += next($data) << 16;
        $result += next($data) << 24;
        $result += next($data) << 32;
        $result += next($data) << 40;
        $result += next($data) << 48;
        $result += next($data) << 56;

        return $result;
    }

    static public function getUInt8(array & $data) : int
    {
        return next($data);
    }

    static public function getRawData(array & $data, int $size) : array
    {
        $result = [];
        while($size)
        {
            $result[] = next($data);
            $size--;
        }
        return $result;
    }

    static public function getRawString( array & $data, int $size ) : string
    {
        $result = "";

        for( $i = 0; $i < $size; $i++ )
        {
            $result .= chr(next($data));
        }

        return $result;
    }

    static public function getString( array & $data, int $maxSize = -1 ) : string
    {
        $result = "";
        $size = next($data);
        if( $maxSize < 0 ) $maxSize = $size;

        for( $i = 0; $i < $size; $i++ )
        {
            $result .= chr(next($data));
            $maxSize--;
        }

        while( $maxSize )
        {
            next($data);
            $maxSize--;
        }

        return $result;
    }

    static public function getDouble( array & $data ) : float
    {
        $temp = Helper::getUint64($data);
        return unpack('d', pack('Q', $temp))[1];
    }

    static public function getFloat( array & $data ) : float
    {
        $temp = Helper::getUint32($data);
        return unpack('f', pack('i', $temp))[1];
    }
}