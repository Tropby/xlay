<?php

namespace XLay;

class File
{
    private FileHeader $header;
    private array $boards = [];
    private Trailer $trailer;

    public function parse(array & $data)
    {
        $this->header = new FileHeader();
        $this->header->parse($data);

        for( $i = 0; $i < $this->header->getNumberOfBoards(); $i++ )
        {
            $board = new Board();
            $board->parse($data);
            $this->boards[] = $board;
        }

        $this->trailer = new Trailer();
        $this->trailer->parse($data);
    }

    public function getBoards() : array
    {
        return $this->boards;
    }
}