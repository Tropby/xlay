<?php

namespace XLay;

class Item
{
    private int $type;
    private float $x;
    private float $y;
    private float $out;
    private float $in;

    private float $lineWidth;
    private int $layer;
    private int $shape;

    private int $componentId;
    private int $selected;

    private array $style;

    private int $styleCustom;

    private float $groundDistance;
    private int $thermobarier;
    private int $flipVertical;
    private int $cutoff;
    private int $thzise;
    private int $metalisation;
    private int $soldermask;

    private array $points = [];
    private array $textObjects = [];

    private Component $component;

    public function parse( array & $data, bool $textChild = false, $layer = null )
    {
        $this->type = Helper::getUInt8($data);
        $this->x = Helper::getFloat($data) / 10000.0;
        $this->y = Helper::getFloat($data) / 10000.0;
        $this->out = Helper::getFloat($data) / 10000.0 * 2; // 2*r = d
        $this->in = Helper::getFloat($data) / 10000.0 * 2; // 2*r = d
        $this->lineWidth = Helper::getUInt32($data) / 10000.0;
        Helper::getUInt8($data);; // reserved
        $this->layer = Helper::getUInt8($data);
        if( $layer ) $this->layer =  $layer;
        $this->shape = Helper::getUInt8($data);
        Helper::getUInt32($data); // reserved
        $this->componentId = Helper::getUInt16($data);
        $this->selected = Helper::getUInt8($data);
        $this->style = Helper::getRawData($data, 4);
        Helper::getUInt8($data);
        Helper::getUInt8($data);
        Helper::getUInt8($data);
        Helper::getUInt8($data);
        Helper::getUInt8($data);
        $this->styleCustom = Helper::getUInt8($data);
        $this->groundDistance = Helper::getUInt32($data) / 10000.0;
        Helper::getUInt32($data); // reserved
        Helper::getUInt8($data);
        $this->thermobarier = Helper::getUInt8($data);
        $this->flipVertical = Helper::getUInt8($data);
        $this->cutoff = Helper::getUInt8($data);
        $this->thzise = Helper::getUInt32($data);
        $this->metalisation = Helper::getUInt8($data);
        $this->soldermask = Helper::getUInt8($data);
        Helper::getUInt8($data);
        Helper::getUInt8($data);
        Helper::getRawData($data, 16); // reserved

        if( !$textChild )
        {
            // TEXT // MARKER // GROUPS ??????
            $len = Helper::getUInt32($data);
            Helper::getRawData($data, $len);

            $len = Helper::getUInt32($data);
            Helper::getRawData($data, $len);

            $len = Helper::getUInt32($data);
            Helper::getRawData($data, $len*4);
        }

        if( $this->type == ItemType::CIRCLE )
        {
            return;
        }

        if( $this->type == ItemType::TEXT )
        {
            $textObjectCount = Helper::getUInt32($data);
            
            for($i = 0; $i < $textObjectCount; $i++ )
            {
                $obj = new Item();
                $obj->parse($data, true, $this->layer);
                $this->textObjects[] = $obj;
            }

            if( $this->shape == 1 )
            {
                $this->component = new Component();
                $this->component->parse($data);
            }

            return;
        }

        $polyCount = Helper::getUInt32($data);
        while($polyCount)
        {
            $point = new Point();
            $point->parse($data);
            $this->points[] = $point;
            $polyCount--;
        }
    }

    public function getTextObjects() : array 
    {
        return $this->textObjects;
    }

    public function getPoints() : array
    {
        return $this->points;
    }

    public function getX() : float
    {
        return $this->x;
    }

    public function getRadius() : float
    {
        return ($this->out + $this->in) / 2;
    }

    public function getOuterRadius() : float
    {
        return $this->out;
    }

    public function getInnerRadius() : float
    {
        return $this->in;
    }

    public function getLineWidth() : float
    {
        return $this->lineWidth;
    }

    public function getEndAngle() : float
    {
        // End angle for circles
        return ($this->lineWidth*10.0);
    }

    public function getStartAngle() : float
    {
        // Start angle for circles
        $result = 0;
        $result += $this->style[0] << 0;
        $result += $this->style[1] << 8;
        $result += $this->style[2] << 16;
        $result += $this->style[3] << 24;
        return $result / 1000;
    }

    public function getY() : float
    {
        return $this->y;
    }

    public function getType() : int
    {
        return $this->type;
    }

    public function getPlatedThrough() : bool
    {
        return $this->metalisation;
    }

    public function getTHTShape() : int
    {
        return $this->shape;
    }

    public function getLayer() : int
    {
        return $this->layer;
    }
}