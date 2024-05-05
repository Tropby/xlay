<?php

namespace XLay;

class FileHeader 
{
    private array $magicNumber = [0,0,0,0];
    const MAGIC_NUMBER = [0x06, 0x33, 0xAA, 0xFF];
    private int $numberOfBoards;

    public function getNumberOfBoards() : int
    {
        return $this->numberOfBoards;
    }

    public function parse(array & $data)
    {
        $this->magicNumber[0] = reset( $data );
        $this->magicNumber[1] = next( $data );
        $this->magicNumber[2] = next( $data );
        $this->magicNumber[3] = next( $data );

        if( array_diff($this->magicNumber, FileHeader::MAGIC_NUMBER) )
        {
            throw new \Exception("Magic Number not matiching! Is this a LAY6 file?");
        }

        $this->numberOfBoards = Helper::getInt32($data);
    }
}
