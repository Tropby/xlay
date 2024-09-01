<?php

namespace XLay;

class BoardHeader
{
    private string $name;
    private float $sizeX;
    private float $sizeY;
    private array $groundPlane;

    private float $activeGridSize;
    private float $zoom;
    private int $viewportOffsetX;
    private int $viewportOffsetY;

    private int $activeLayer;

    private array $visibleLayer;

    private int $showScannedCopyTop;
    private int $showScannedCopyBottom;
    private string $showScannedCopyTopPath;
    private string $showScannedCopyBottomPath;
    private int $dpiTop;
    private int $dpiBottom;

    private int $shiftXTop;
    private int $shiftYTop;
    private int $shiftXBottom;
    private int $shiftYBottom;

    private float $centerX;
    private float $centerY;

    private bool $multilayer;

    private int $objectCount;

    public function parse( array & $data )
    {
        $this->name = Helper::getString($data, 30);
        Helper::getUInt32($data); // reseved ?

        $this->sizeX = Helper::getUInt32($data) / 10000.0;
        $this->sizeY = Helper::getUInt32($data) / 10000.0;
        $this->groundPlane = Helper::getRawData($data, 7);
        $this->activeGridSize = Helper::getDouble($data) / 10000.0;
        $this->zoom = Helper::getDouble($data);
        $this->viewportOffsetX = Helper::getUInt32($data);
        $this->viewportOffsetY = Helper::getUInt32($data);
    
        $this->activeLayer = Helper::getUInt8($data);
        Helper::getRawData($data, 3);
    
        $this->visibleLayer = Helper::getRawData($data, 7);
        $this->showScannedCopyTop = Helper::getUInt8($data);
        $this->showScannedCopyBottom = Helper::getUInt8($data);
        $this->showScannedCopyTopPath = Helper::getString($data, 200);
        $this->showScannedCopyBottomPath = Helper::getString($data, 200);
        $this->dpiTop = Helper::getUInt32($data) / 10000.0;
        $this->dpiBottom = Helper::getUInt32($data) / 10000.0;


        $this->shiftXTop = Helper::getUInt32($data);
        $this->shiftYTop = Helper::getUInt32($data);
        $this->shiftXBottom = Helper::getUInt32($data);
        $this->shiftYBottom = Helper::getUInt32($data);

        Helper::getUInt32($data); // reseved ?
        Helper::getUInt32($data); // reseved ?

        $this->centerX = Helper::getInt32($data) / 10000.0;
        $this->centerY = Helper::getInt32($data) / 10000.0;

        $this->multilayer = Helper::getUInt8($data) != 0;

        $this->objectCount = Helper::getUInt32($data);
    }

    public function getGroundPlane(int $layer) : bool
    {
        return $this->groundPlane[$layer-1];
    }

    public function getObjectCount() : int 
    {
        return $this->objectCount;
    }

    public function getSizeX() : float
    {
        return $this->sizeX;
    }

    public function getSizeY() : float
    {
        return $this->sizeY;
    }
}
