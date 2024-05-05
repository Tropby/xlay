<?php

namespace XLay;

class XLay
{
    private array $rawData;
    private File $file;

    public function load(string $filename)
    {
        $this->rawData = [];
        $fp = fopen($filename, "rb");
        while(!feof($fp) )
        {
            $data = fread($fp, 512);
            foreach( str_split($data) as $d)
            {
                $this->rawData[] = ord($d);
            }
        }
        fclose($fp);

        $this->file = new File();
        $this->file->parse($this->rawData);
    }

    public function showAsHex()
    {
        while(($d = next($this->rawData)) !== false)
        {
            printf("%02X ", $d);
        }
    }

    public function getFile() : File
    {
        return $this->file;
    }
}
