<?php

namespace XLay;

class Macro
{
    private FileHeader $header;
    private array $objects = [];
    private Connection $connection;

    private int $width;
    private int $height;

    private array $offset;

    public function parse(array & $data)
    {
        $this->header = new FileHeader();
        $this->header->parse($data);

        $objectCount = $this->header->getNumberOfBoards();

        $connectionCount = 0;


        $rect = [99999, 99999, -99999, -99999];
        for( $i = 0; $i < $objectCount; $i++ )
        {
            $object = new Item();
            $object->parse($data);
            $this->objects[] = $object;

            if( $object->getType() == ItemType::SMD_PAD ||
                $object->getType() == ItemType::THT_PAD )
                {
                    $connectionCount++;
                }

            $rect = Helper::combineRects( $rect, $object->getBoundingReact() );
        }

        $this->width = $rect[2] - $rect[0] + 3;
        $this->height = $rect[3] - $rect[1] + 3;
        $this->offset[0] = -$rect[0] + 3;
        $this->offset[1] = $rect[3] + 3;

        echo "<pre>
            width: {$this->width}<br />
            height: {$this->height}<br />
            OX: {$this->offset[0]}<br />
            OY: {$this->offset[1]}<hr />
        </pre>";

        for( $i = 0; $i < $connectionCount; $i++ )
        {
            $this->connection = new Connection();
            $this->connection->parse($data);
        }
    }

    public function getSizeX() : float
    {
        return $this->width;
    }

    public function getSizeY() : float
    {
        return $this->height;
    }

    public function getOffsetX() : float
    {
        return $this->offset[0];
    }

    public function getOffsetY() : float
    {
        return $this->offset[1];
    }

    public function getObjects() : array
    {
        return $this->objects;
    }    
}