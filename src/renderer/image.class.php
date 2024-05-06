<?php

namespace XLay\Renderer;

use stdClass;

class Image
{
    const SCALE = 20;

    public function render(\XLay\Board & $board, $layers = \XLay\Layer::LAYERS_DEFAULT_ORDER, array $backgroundColor = [50, 50, 50])
    {
        $img = imagecreatetruecolor($board->getSizeX() * Image::SCALE, $board->getSizeY() * Image::SCALE);

        $color = imagecolorallocate($img, $backgroundColor[0], $backgroundColor[1], $backgroundColor[2]);
        imagefill($img, 0, 0, $color);
        $color = imagecolorallocate($img, 250, 250, 250);

        $objects = $board->getObjects();

        ;
        foreach( $layers as $layer )
        {
            foreach($objects as $object)
            {
                if( $object->getLayer() == $layer )
                {
                    $this->drawObject($img, $object, 1);
                }
            }
        }

        foreach( $layers as $layer )
        {
            foreach($objects as $object)
            {
                if( $object->getLayer() == $layer || $object->getType() == \XLay\ItemType::THT_PAD)
                {
                    $this->drawObject($img, $object, 2);
                }
            }
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
            case \XLay\Layer::M: return imagecolorallocate($img, 81, 227, 253);
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

            case \XLay\ItemType::POLY:
                if( $step == 1 ) $this->drawPoly($img, $item);
                break;

            case \XLay\ItemType::THT_PAD:
                if( $step == 1 ) $this->drawTHTPad($img, $item);
                if( $step == 2 ) $this->drawDrill($img, $item);
                break;

            case \XLay\ItemType::SMD_PAD:
                if( $step == 1 ) $this->drawSMDPad($img, $item);
                break;

            case \XLay\ItemType::TEXT:
                if( $step == 1 ) $this->drawLine($img, $item);
                break;
    
            default:
                throw new \Exception("Unknown Object ".$item->getType()." Type!");
                break;
        }
    }

    private function drawSMDPad(& $img, \XLay\Item $item)
    {
        $this->setBrush($img, $item->getLayer(), 1);

        $subItems = $item->getTextObjects();
        foreach( $subItems as $si )
        {
            $this->drawObject($img, $si, 1);
        }

        $points = $item->getPoints();
        $arr = [];
        foreach( $points as $point )
        {
            $arr[] = $point->getX() * Image::SCALE;
            $arr[] = -$point->getY() * Image::SCALE;
        }

        $size = 1;
        $this->setBrush($img, $item->getLayer(), $size);

        imagefilledpolygon($img, $arr, count($arr)/2, IMG_COLOR_BRUSHED);
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

    private function drawDrill(& $img, \XLay\Item $item)
    {
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

    private function drawTHTPad(& $img, \XLay\Item $item)
    {
        $shape = $item->getTHTShape();

        if( $item->getPlatedThrough() )
            $color = $this->getColor($img, \XLay\Layer::M);
        else
            $color = $this->getColor($img, $item->getLayer());

        switch($shape)
        {
            case \XLay\ShapeType::CIRCLE:
                imagefilledarc(
                    $img,
                    $item->getX() * Image::SCALE,
                    -$item->getY() * Image::SCALE,
                    $item->getOuterRadius() * Image::SCALE,
                    $item->getOuterRadius() * Image::SCALE,
                    0,0,
                    $color, IMG_ARC_PIE);
                break;

            case \XLay\ShapeType::SQUARE:
                imagefilledrectangle(
                    $img,
                    $item->getX() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 2,
                    -$item->getY() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 2,
                    $item->getX() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 2,
                    -$item->getY() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 2,
                    $color);
                break;

            case \XLay\ShapeType::OCTAGON:

                $points = [
                    $item->getX() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 2,
                    -$item->getY() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 4,

                    $item->getX() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 4,
                    -$item->getY() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 2,

                    $item->getX() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 4,
                    -$item->getY() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 2,

                    $item->getX() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 2,
                    -$item->getY() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 4,

                    $item->getX() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 2,
                    -$item->getY() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 4,

                    $item->getX() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 4,
                    -$item->getY() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 2,

                    $item->getX() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 4,
                    -$item->getY() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 2,

                    $item->getX() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 2,
                    -$item->getY() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 4
                ];

                imagefilledpolygon($img, $points, 8, $color);
                break;

            case \XLay\ShapeType::CIRCLE_H:
                if( $item->getPlatedThrough() )
                    $this->setBrush($img, \XLay\Layer::M, $item->getOuterRadius() * Image::SCALE);
                else
                    $this->setBrush($img, $item->getLayer(), $item->getOuterRadius() * Image::SCALE);

                imageline(
                    $img,
                    $item->getX() * Image::SCALE - $item->getOuterRadius() * Image::SCALE/2,
                    -$item->getY() * Image::SCALE,
                    $item->getX() * Image::SCALE + $item->getOuterRadius() * Image::SCALE/2,
                    -$item->getY() * Image::SCALE,
                    IMG_COLOR_BRUSHED
                );
                break;

            case \XLay\ShapeType::OCTAGON_H:

                $points = [
                    $item->getX() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 2 - $item->getOuterRadius() * Image::SCALE/2,
                    -$item->getY() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 4,

                    $item->getX() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 4 - $item->getOuterRadius() * Image::SCALE/2,
                    -$item->getY() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 2,

                    $item->getX() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 4 + $item->getOuterRadius() * Image::SCALE/2,
                    -$item->getY() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 2,

                    $item->getX() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 2 + $item->getOuterRadius() * Image::SCALE/2,
                    -$item->getY() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 4,

                    $item->getX() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 2 + $item->getOuterRadius() * Image::SCALE/2,
                    -$item->getY() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 4,

                    $item->getX() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 4 + $item->getOuterRadius() * Image::SCALE/2,
                    -$item->getY() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 2,

                    $item->getX() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 4 - $item->getOuterRadius() * Image::SCALE/2,
                    -$item->getY() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 2,

                    $item->getX() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 2 - $item->getOuterRadius() * Image::SCALE/2,
                    -$item->getY() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 4
                ];

                imagefilledpolygon($img, $points, 8, $color);
                break;

            case \XLay\ShapeType::RECT_H:
                imagefilledrectangle(
                    $img,
                    $item->getX() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 2 - $item->getOuterRadius() * Image::SCALE/2,
                    -$item->getY() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 2,
                    $item->getX() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 2 + $item->getOuterRadius() * Image::SCALE/2,
                    -$item->getY() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 2,
                    $color);
                break;

            case \XLay\ShapeType::CIRCLE_V:
                if( $item->getPlatedThrough() )
                    $this->setBrush($img, \XLay\Layer::M, $item->getOuterRadius() * Image::SCALE);
                else
                    $this->setBrush($img, $item->getLayer(), $item->getOuterRadius() * Image::SCALE);

                imageline(
                    $img,
                    $item->getX() * Image::SCALE,
                    -$item->getY() * Image::SCALE - $item->getOuterRadius() * Image::SCALE/2,
                    $item->getX() * Image::SCALE,
                    -$item->getY() * Image::SCALE + $item->getOuterRadius() * Image::SCALE/2,
                    IMG_COLOR_BRUSHED
                );
                break;

            case \XLay\ShapeType::OCTAGON_V:

                $points = [
                    $item->getX() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 2,
                    -$item->getY() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 4 + $item->getOuterRadius() * Image::SCALE/2,

                    $item->getX() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 4,
                    -$item->getY() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 2 + $item->getOuterRadius() * Image::SCALE/2,

                    $item->getX() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 4,
                    -$item->getY() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 2 + $item->getOuterRadius() * Image::SCALE/2,

                    $item->getX() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 2,
                    -$item->getY() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 4 + $item->getOuterRadius() * Image::SCALE/2,

                    $item->getX() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 2,
                    -$item->getY() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 4 - $item->getOuterRadius() * Image::SCALE/2,

                    $item->getX() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 4,
                    -$item->getY() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 2 - $item->getOuterRadius() * Image::SCALE/2,

                    $item->getX() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 4,
                    -$item->getY() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 2 - $item->getOuterRadius() * Image::SCALE/2,

                    $item->getX() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 2,
                    -$item->getY() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 4 - $item->getOuterRadius() * Image::SCALE/2
                ];

                imagefilledpolygon($img, $points, 8, $color);
                break;

            case \XLay\ShapeType::RECT_V:
                imagefilledrectangle(
                    $img,
                    $item->getX() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 2,
                    -$item->getY() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 2 - $item->getOuterRadius() * Image::SCALE/2,
                    $item->getX() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 2,
                    -$item->getY() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 2 + $item->getOuterRadius() * Image::SCALE/2,
                    $color);
                break;

            default:
                throw new \Exception("Unknwon THT Shape [$shape]!");
                break;
        }
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

    private function drawPoly(& $img, \XLay\Item $item)
    {
        $subItems = $item->getTextObjects();
        foreach( $subItems as $si )
        {
            $this->drawObject($img, $si, 1);
        }

        $points = $item->getPoints();
        $first = true;
        $last = new \XLay\Point();
        $arr = [];
        foreach( $points as $point )
        {
            $arr[] = $point->getX() * Image::SCALE;
            $arr[] = -$point->getY() * Image::SCALE;
        }

        $size = $item->getLineWidth()*Image::SCALE;
        if( $size < 1 ) $size = 1;
        $this->setBrush($img, $item->getLayer(), $size);

        imagefilledpolygon($img, $arr, count($arr)/2, IMG_COLOR_BRUSHED);
    }

}