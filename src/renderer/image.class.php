<?php

namespace XLay\Renderer;

use stdClass;

class Image implements IRenderer
{
    const SCALE = 20;

    private $offset = [0,0];
    private $colorScheme = \XLay\Layer::COLORS_DEFAULT;

    public function setColorScheme(array $colorScheme)
    {
        $this->colorScheme = $colorScheme;
    }

    public function render(
        & $board,
        $filename = null,
        $layers = \XLay\Layer::LAYERS_DEFAULT_ORDER,
        $offset = [0,0],
        $flipHorizontal = false
    )
    {
        $this->offset = $offset;

        $img = imagecreatetruecolor(($board->getSizeX() + $offset[0]) * Image::SCALE, ($board->getSizeY() + $offset[1]) * Image::SCALE);

        $color = $this->getColor($img, \XLay\Layer::B);
        imagefill($img, 0, 0, $color);
        $color = imagecolorallocate($img, 250, 250, 250);

        $objects = $board->getObjects();

        foreach( $layers as $layer )
        {
            foreach($objects as $object)
            {
                if( $object->getLayer() == $layer && !$object->getSoldermask())
                {
                    $this->drawObject($img, $object, 1);
                }
            }

            foreach($objects as $object)
            {
                if( $object->getLayer() == $layer && $object->getSoldermask())
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

        if( $flipHorizontal )
        {
            imageflip($img, IMG_FLIP_HORIZONTAL);
        }

        if( $filename == null )
            header("Content-Type: image/png");
        imagepng($img, $filename);
    }

    private function getColor(& $img, int $layer) : int
    {
        if( isset( $this->colorScheme[$layer] ) )
        {
            return imagecolorallocate(
                $img,
                $this->colorScheme[$layer][0],
                $this->colorScheme[$layer][1],
                $this->colorScheme[$layer][2]
            );
        }
        return imagecolorallocate($img, 255, 255, 0);
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
            $arr[] = $point->getX() * Image::SCALE + $this->offset[0]  * Image::SCALE;
            $arr[] = -$point->getY() * Image::SCALE + $this->offset[1]  * Image::SCALE;
        }

        $size = 1;
        $this->setBrush($img, $item->getLayer(), $size);

        imagefilledpolygon($img, $arr, count($arr)/2, IMG_COLOR_BRUSHED);
    }

    private function drawCircle(& $img, \XLay\Item $item)
    {
        $size = ($item->getOuterRadius()-$item->getInnerRadius())/2*Image::SCALE;

        $layer = $item->getLayer();
        if( $item->getPlatedThrough() )
        {
            $layer = \XLay\Layer::M;
        }

        if( $item->getSoldermask() )
        {
            $layer = $layer | \XLay\Layer::COPPER;
        }

        $this->setBrush($img, $layer, $size);

        imagearc(
            $img,
            $item->getX() * Image::SCALE + $this->offset[0]  * Image::SCALE,
            -$item->getY() * Image::SCALE + $this->offset[1]  * Image::SCALE,
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
            $item->getX() * Image::SCALE + $this->offset[0]  * Image::SCALE,
            -$item->getY() * Image::SCALE + $this->offset[1]  * Image::SCALE,
            $item->getInnerRadius() * Image::SCALE,
            $item->getInnerRadius() * Image::SCALE,
            0,0,
            $color, IMG_ARC_PIE);
    }

    private function drawTHTPad(& $img, \XLay\Item $item)
    {
        $shape = $item->getTHTShape();

        $layer = $item->getLayer();
        if( $item->getPlatedThrough() )
        {
            $layer = \XLay\Layer::M;
        }

        if( $item->getSoldermask() )
        {
            $layer = $layer | \XLay\Layer::COPPER;
        }

        $color = $this->getColor($img, $layer);

        switch($shape)
        {
            case \XLay\ShapeType::CIRCLE:
                imagefilledarc(
                    $img,
                    $item->getX() * Image::SCALE + $this->offset[0]  * Image::SCALE,
                    -$item->getY() * Image::SCALE + $this->offset[1]  * Image::SCALE,
                    $item->getOuterRadius() * Image::SCALE,
                    $item->getOuterRadius() * Image::SCALE,
                    0,0,
                    $color, IMG_ARC_PIE);
                break;

            case \XLay\ShapeType::SQUARE:
                imagefilledrectangle(
                    $img,
                    $item->getX() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 2 + $this->offset[0]  * Image::SCALE,
                    -$item->getY() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 2 + $this->offset[1]  * Image::SCALE,
                    $item->getX() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 2 + $this->offset[0]  * Image::SCALE,
                    -$item->getY() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 2 + $this->offset[1]  * Image::SCALE,
                    $color);
                break;

            case \XLay\ShapeType::OCTAGON:

                $points = [
                    $item->getX() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 2 + $this->offset[0]  * Image::SCALE,
                    -$item->getY() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 4 + $this->offset[1]  * Image::SCALE,

                    $item->getX() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 4 + $this->offset[0]  * Image::SCALE,
                    -$item->getY() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 2 + $this->offset[1]  * Image::SCALE,

                    $item->getX() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 4 + $this->offset[0]  * Image::SCALE,
                    -$item->getY() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 2 + $this->offset[1]  * Image::SCALE,

                    $item->getX() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 2 + $this->offset[0]  * Image::SCALE,
                    -$item->getY() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 4 + $this->offset[1]  * Image::SCALE,

                    $item->getX() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 2 + $this->offset[0]  * Image::SCALE,
                    -$item->getY() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 4 + $this->offset[1]  * Image::SCALE,

                    $item->getX() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 4 + $this->offset[0]  * Image::SCALE,
                    -$item->getY() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 2 + $this->offset[1]  * Image::SCALE,

                    $item->getX() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 4 + $this->offset[0]  * Image::SCALE,
                    -$item->getY() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 2 + $this->offset[1]  * Image::SCALE,

                    $item->getX() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 2 + $this->offset[0]  * Image::SCALE,
                    -$item->getY() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 4 + $this->offset[1]  * Image::SCALE
                ];

                imagefilledpolygon($img, $points, 8, $color);
                break;

            case \XLay\ShapeType::CIRCLE_H:
                $this->setBrush($img, $layer, $item->getOuterRadius() * Image::SCALE);

                imageline(
                    $img,
                    $item->getX() * Image::SCALE - $item->getOuterRadius() * Image::SCALE/2 + $this->offset[0]  * Image::SCALE,
                    -$item->getY() * Image::SCALE + $this->offset[1]  * Image::SCALE,
                    $item->getX() * Image::SCALE + $item->getOuterRadius() * Image::SCALE/2 + $this->offset[0]  * Image::SCALE,
                    -$item->getY() * Image::SCALE + $this->offset[1]  * Image::SCALE,
                    IMG_COLOR_BRUSHED
                );
                break;

            case \XLay\ShapeType::OCTAGON_H:

                $points = [
                    $item->getX() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 2 - $item->getOuterRadius() * Image::SCALE/2 + $this->offset[0]  * Image::SCALE,
                    -$item->getY() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 4 + $this->offset[1]  * Image::SCALE,

                    $item->getX() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 4 - $item->getOuterRadius() * Image::SCALE/2 + $this->offset[0]  * Image::SCALE,
                    -$item->getY() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 2 + $this->offset[1]  * Image::SCALE,

                    $item->getX() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 4 + $item->getOuterRadius() * Image::SCALE/2 + $this->offset[0]  * Image::SCALE,
                    -$item->getY() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 2 + $this->offset[1]  * Image::SCALE,

                    $item->getX() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 2 + $item->getOuterRadius() * Image::SCALE/2 + $this->offset[0]  * Image::SCALE,
                    -$item->getY() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 4 + $this->offset[1]  * Image::SCALE,

                    $item->getX() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 2 + $item->getOuterRadius() * Image::SCALE/2 + $this->offset[0]  * Image::SCALE,
                    -$item->getY() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 4 + $this->offset[1]  * Image::SCALE,

                    $item->getX() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 4 + $item->getOuterRadius() * Image::SCALE/2 + $this->offset[0]  * Image::SCALE,
                    -$item->getY() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 2 + $this->offset[1]  * Image::SCALE,

                    $item->getX() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 4 - $item->getOuterRadius() * Image::SCALE/2 + $this->offset[0]  * Image::SCALE,
                    -$item->getY() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 2 + $this->offset[1]  * Image::SCALE,

                    $item->getX() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 2 - $item->getOuterRadius() * Image::SCALE/2 + $this->offset[0]  * Image::SCALE,
                    -$item->getY() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 4 + $this->offset[1]  * Image::SCALE
                ];

                imagefilledpolygon($img, $points, 8, $color);
                break;

            case \XLay\ShapeType::RECT_H:
                imagefilledrectangle(
                    $img,
                    $item->getX() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 2 - $item->getOuterRadius() * Image::SCALE/2 + $this->offset[0]  * Image::SCALE,
                    -$item->getY() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 2 + $this->offset[1]  * Image::SCALE,
                    $item->getX() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 2 + $item->getOuterRadius() * Image::SCALE/2 + $this->offset[0]  * Image::SCALE,
                    -$item->getY() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 2 + $this->offset[1]  * Image::SCALE,
                    $color);
                break;

            case \XLay\ShapeType::CIRCLE_V:
                $this->setBrush($img, $layer, $item->getOuterRadius() * Image::SCALE);

                imageline(
                    $img,
                    $item->getX() * Image::SCALE + $this->offset[0]  * Image::SCALE,
                    -$item->getY() * Image::SCALE - $item->getOuterRadius() * Image::SCALE/2 + $this->offset[1]  * Image::SCALE,
                    $item->getX() * Image::SCALE + $this->offset[0]  * Image::SCALE,
                    -$item->getY() * Image::SCALE + $item->getOuterRadius() * Image::SCALE/2 + $this->offset[1]  * Image::SCALE,
                    IMG_COLOR_BRUSHED
                );
                break;

            case \XLay\ShapeType::OCTAGON_V:

                $points = [
                    $item->getX() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 2 + $this->offset[0]  * Image::SCALE,
                    -$item->getY() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 4 + $item->getOuterRadius() * Image::SCALE/2 + $this->offset[1]  * Image::SCALE,

                    $item->getX() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 4 + $this->offset[0]  * Image::SCALE,
                    -$item->getY() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 2 + $item->getOuterRadius() * Image::SCALE/2 + $this->offset[1]  * Image::SCALE,

                    $item->getX() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 4 + $this->offset[0]  * Image::SCALE,
                    -$item->getY() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 2 + $item->getOuterRadius() * Image::SCALE/2 + $this->offset[1]  * Image::SCALE,

                    $item->getX() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 2 + $this->offset[0]  * Image::SCALE,
                    -$item->getY() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 4 + $item->getOuterRadius() * Image::SCALE/2 + $this->offset[1]  * Image::SCALE,

                    $item->getX() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 2 + $this->offset[0]  * Image::SCALE,
                    -$item->getY() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 4 - $item->getOuterRadius() * Image::SCALE/2 + $this->offset[1]  * Image::SCALE,

                    $item->getX() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 4 + $this->offset[0]  * Image::SCALE,
                    -$item->getY() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 2 - $item->getOuterRadius() * Image::SCALE/2 + $this->offset[1]  * Image::SCALE,

                    $item->getX() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 4 + $this->offset[0]  * Image::SCALE,
                    -$item->getY() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 2 - $item->getOuterRadius() * Image::SCALE/2 + $this->offset[1]  * Image::SCALE,

                    $item->getX() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 2 + $this->offset[0]  * Image::SCALE,
                    -$item->getY() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 4 - $item->getOuterRadius() * Image::SCALE/2 + $this->offset[1]  * Image::SCALE
                ];

                imagefilledpolygon($img, $points, 8, $color);
                break;

            case \XLay\ShapeType::RECT_V:
                imagefilledrectangle(
                    $img,
                    $item->getX() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 2 + $this->offset[0]  * Image::SCALE,
                    -$item->getY() * Image::SCALE - $item->getOuterRadius() * Image::SCALE / 2 - $item->getOuterRadius() * Image::SCALE/2 + $this->offset[1]  * Image::SCALE,
                    $item->getX() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 2 + $this->offset[0]  * Image::SCALE,
                    -$item->getY() * Image::SCALE + $item->getOuterRadius() * Image::SCALE / 2 + $item->getOuterRadius() * Image::SCALE/2 + $this->offset[1]  * Image::SCALE,
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

                $layer = $item->getLayer();
                if( $item->getPlatedThrough() )
                {
                    $layer = \XLay\Layer::M;
                }
        
                if( $item->getSoldermask() )
                {
                    $layer = $layer | \XLay\Layer::COPPER;
                }

                $this->setBrush($img, $layer, $size);

                imageline($img, 
                    $last->getX() * Image::SCALE + $this->offset[0]  * Image::SCALE,
                    -$last->getY() * Image::SCALE + $this->offset[1]  * Image::SCALE,
                    $point->getX() * Image::SCALE + $this->offset[0]  * Image::SCALE,
                    -$point->getY() * Image::SCALE + $this->offset[1]  * Image::SCALE,
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
            $arr[] = $point->getX() * Image::SCALE + $this->offset[0]  * Image::SCALE;
            $arr[] = -$point->getY() * Image::SCALE + $this->offset[1]  * Image::SCALE;
        }

        $layer = $item->getLayer();
        if( $item->getPlatedThrough() )
        {
            $layer = \XLay\Layer::M;
        }

        if( $item->getSoldermask() )
        {
            $layer = $layer | \XLay\Layer::COPPER;
        }

        $size = $item->getLineWidth()*Image::SCALE;
        if( $size < 1 ) $size = 1;
        $this->setBrush($img, $layer, $size);

        imagefilledpolygon($img, $arr, count($arr)/2, IMG_COLOR_BRUSHED);
    }

}