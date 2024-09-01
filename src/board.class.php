<?php

namespace XLay;

class Board
{
    private BoardHeader $header;
    private array $objects;
    private Connection $connection;

    public function parse( array & $data )
    {
        $this->header = new BoardHeader();
        $this->header->parse($data);

        $objectCount = $this->header->getObjectCount();
        $connectionCount = 0;
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
        }

        for( $i = 0; $i < $connectionCount; $i++ )
        {
            $this->connection = new Connection();
            $this->connection->parse($data);
        }
    }

    public function hasGroundPlane(int $Layer) : bool
    {
        return $this->header->getGroundPlane($Layer);
    }

    public function getSizeX() : float
    {
        return $this->header->getSizeX();
    }

    public function getSizeY() : float
    {
        return $this->header->getSizeY();
    }

    public function getObjects() : array
    {
        return $this->objects;
    }
}
