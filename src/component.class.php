<?php

namespace XLay;

class Component
{
   private float $offsetX;
   private float $offsetY;
   private int $centerMode;
   private float $rotation;

   private string $package;
   private string $comment;
   private int $use;

    public function parse(array & $data)
    {
        $this->offsetX = Helper::getFloat($data);
        $this->offsetY = Helper::getFloat($data);
        $this->centerMode = Helper::getUInt8($data);
        $this->rotation = Helper::getDouble($data);

        $len = Helper::getUInt32($data);
        $this->package = Helper::getRawString($data, $len);
        $len = Helper::getUInt32($data);
        $this->comment = Helper::getRawString($data, $len);

        $this->use = Helper::getUInt8($data);
    }
}