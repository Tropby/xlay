<?php

namespace XLay\Renderer;

use stdClass;

class Image
{
    const SCALE = 20;

    public function render(\XLay\Board & $board)
    {
        $img = imagecreatetruecolor($board->getSizeX() * Image::SCALE, $board->getSizeY() * Image::SCALE);

        $color = imagecolorallocate($img, 50, 50, 50);
        imagefill($img, 0, 0, $color);
        $color = imagecolorallocate($img, 250, 250, 250);

        $objects = $board->getObjects();

        foreach($objects as $object)
        {
            $this->drawObject($img, $object, 1);
        }

        foreach($objects as $object)
        {
            $this->drawObject($img, $object, 2);
        }

        header("Content-Type: image/png");
        imagepng($img);
    }

    private function getColor(& $img, int $layer) : int
    {
        switch($layer)
        {
            case \XLay\Layer::C1: return imagecolorallocate($img, 30, 106, 249);
            case \XLay\Layer::C2: return imagecolorallocate($img, 0, 186, 0);
            case \XLay\Layer::S1: return imagecolorallocate($img, 255, 0, 0);
            case \XLay\Layer::S2: return imagecolorallocate($img, 225, 215, 4);
            case \XLay\Layer::I1: return imagecolorallocate($img, 194, 124, 20);
            case \XLay\Layer::I2: return imagecolorallocate($img, 238, 182, 98);
            case \XLay\Layer::O: return imagecolorallocate($img, 255, 255, 255);
            default: return imagecolorallocate($img, 255, 255, 0);
        }
    }

    private function setBrush(&$img, int $layer, float $size)
    {
        static $brushes = [];
        $bn = $layer."_".$size;

        if( !isset($brushes[$bn]))
        {
            $brush = imagecreatetruecolor($size, $size);
            imageantialias($brush, true);
            $black = imagecolorallocate($brush, 0, 0, 0);
            imagecolortransparent($brush, $black);
            $c = $this->getColor($img, $layer);
            imagefilledarc($brush,
                $size/2,
                $size/2,
                $size,
                $size,
                0, 0, 
                $c, IMG_ARC_PIE
            );
            $brushes[$bn] = $brush;
        }
        imagesetbrush($img, $brushes[$bn]);
    }

    private function drawObject(& $img, \XLay\Item $item, int $step)
    {
        switch($item->getType())
        {
            case \XLay\ItemType::LINE:
                if( $step == 1 ) $this->drawLine($img, $item);
                break;

            case \XLay\ItemType::CIRCLE:
                if( $step == 1 ) $this->drawCircle($img, $item);
                break;

            case \XLay\ItemType::THT_PAD:
                if( $step == 2 ) $this->drawTHTPad($img, $item);
                break;

            case \XLay\ItemType::TEXT:
                if( $step == 2 ) $this->drawLine($img, $item);
                break;
    
        }
    }

    private function drawCircle(& $img, \XLay\Item $item)
    {
        $size = ($item->getOuterRadius()-$item->getInnerRadius())/2*Image::SCALE;

        $this->setBrush($img, $item->getLayer(), $size);

        imagearc(
            $img,
            $item->getX() * Image::SCALE,
            -$item->getY() * Image::SCALE,
            $item->getRadius() * Image::SCALE,
            $item->getRadius() * Image::SCALE,
            -$item->getEndAngle(),
            -$item->getStartAngle(),
            IMG_COLOR_BRUSHED);
    }

    private function drawTHTPad(& $img, \XLay\Item $item)
    {
        $color = $this->getColor($img, $item->getLayer());
        imagefilledarc(
            $img,
            $item->getX() * Image::SCALE,
            -$item->getY() * Image::SCALE,
            $item->getOuterRadius() * Image::SCALE,
            $item->getOuterRadius() * Image::SCALE,
            0,0,
            $color, IMG_ARC_PIE);

        $color = imagecolorallocate($img, 0, 0, 0);
        imagefilledarc(
            $img,
            $item->getX() * Image::SCALE,
            -$item->getY() * Image::SCALE,
            $item->getInnerRadius() * Image::SCALE,
            $item->getInnerRadius() * Image::SCALE,
            0,0,
            $color, IMG_ARC_PIE);
    }

    private function drawLine(& $img, \XLay\Item $item)
    {
        $subItems = $item->getTextObjects();
        foreach( $subItems as $si )
        {
            $this->drawObject($img, $si, 1);
        }

        $points = $item->getPoints();
        $first = true;
        $last = new \XLay\Point();
        foreach( $points as $point )
        {
            if( !$first )
            {
                $size = $item->getLineWidth()*Image::SCALE;
                if( $size < 1 ) $size = 1;

                $this->setBrush($img, $item->getLayer(), $size);

                imageline($img, 
                    $last->getX() * Image::SCALE,
                    -$last->getY() * Image::SCALE,
                    $point->getX() * Image::SCALE,
                    -$point->getY() * Image::SCALE,
                    IMG_COLOR_BRUSHED
                );

            }
            $first = false;

            $last = $point;
        }
    }

}