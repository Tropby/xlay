<?php

namespace XLay;

class Point
{
    private float $x;
    private float $y;

    public function parse(array & $data)
    {
        $this->x = Helper::getFloat($data) / 10000.0;
        $this->y = Helper::getFloat($data) / 10000.0;
    }

    public function getX() : float
    {
        return $this->x;
    }

    public function getY() : float
    {
        return $this->y;
    }
}