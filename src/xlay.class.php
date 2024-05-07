<?php

namespace XLay;

class XLay
{
    public static function loadLay6(string $filename) : File
    {
        if( !$filename || !file_exists($filename))
        {
            throw new \Exception("File not found: \"$filename\"");
        }
        
        $rawData = [];
        $fp = fopen($filename, "rb");
        while(!feof($fp) )
        {
            $data = fread($fp, 512);
            foreach( str_split($data) as $d)
            {
                $rawData[] = ord($d);
            }
        }
        fclose($fp);

        $file = new File();
        $file->parse($rawData);

        return $file;
    }

    public static function loadMacro(string $filename) : Macro
    {
        if( !$filename || !file_exists($filename))
        {
            throw new \Exception("File not found: \"$filename\"");
        }

        $rawData = [];
        $fp = fopen($filename, "rb");
        while(!feof($fp) )
        {
            $data = fread($fp, 512);
            foreach( str_split($data) as $d)
            {
                $rawData[] = ord($d);
            }
        }
        fclose($fp);

        $file = new Macro();
        $file->parse($rawData);

        return $file;
    }
}
