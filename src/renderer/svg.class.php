<?php

namespace XLay\Renderer;

class SVG implements IRenderer
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
    ) : string
    {
        $this->offset = $offset;

        $svg = 
        '<svg
            xmlns="http://www.w3.org/2000/svg"
            width="'.$board->getSizeX().'mm"
            height="'.$board->getSizeY().'mm"
            viewBox="0 0 '.$board->getSizeX().' '.$board->getSizeY().'"
            '.($flipHorizontal ? 'style="display: block; transform: scale(-1,1)"' : '').'
        >';

        $color = $this->getColor(\XLAy\Layer::B);
        $svg .= '<rect fill="'.$color.'" width="'.$board->getSizeX().'" height="'.$board->getSizeY().'" />';

        $objects = $board->getObjects();

        foreach( $layers as $layer )
        {
            foreach($objects as $object)
            {
                if( $object->getLayer() == $layer && !$object->getSoldermask())
                {
                    $svg .= $this->drawObject($object, 1);
                }
            }

            foreach($objects as $object)
            {
                if( $object->getLayer() == $layer && $object->getSoldermask())
                {
                    $svg .= $this->drawObject($object, 1);
                }
            }
        }

        foreach( $layers as $layer )
        {
            foreach($objects as $object)
            {
                if( $object->getLayer() == $layer || $object->getType() == \XLay\ItemType::THT_PAD)
                {
                    $svg .= $this->drawObject($object, 2);
                }
            }
        }

        $svg .= '</svg>';

        if( $filename )
        {
            $fp = fopen($filename, "w");
            fwrite($fp, $svg);
            fclose($fp);
        }

        return $svg;
    }

    private function getColor(int $layer) : string
    {
        if( isset( $this->colorScheme[$layer] ) )
        {
            return sprintf(
                "#%02X%02X%02X",
                $this->colorScheme[$layer][0],
                $this->colorScheme[$layer][1],
                $this->colorScheme[$layer][2]);
        }
        return "#FFFF00";
    }

    private function drawObject(\XLay\Item $item, int $step) : string
    {
        $svg = '';
        switch($item->getType())
        {
            case \XLay\ItemType::LINE:
                if( $step == 1 ) $svg .= $this->drawLine($item);
                break;

            case \XLay\ItemType::CIRCLE:
                if( $step == 1 ) $svg .= $this->drawCircle($item);
                break;

            case \XLay\ItemType::POLY:
                if( $step == 1 ) $svg .= $this->drawPoly($item);
                break;

            case \XLay\ItemType::THT_PAD:
                if( $step == 1 ) $svg .= $this->drawTHTPad($item);
                if( $step == 2 ) $svg .= $this->drawDrill($item);
                break;

            case \XLay\ItemType::SMD_PAD:
                if( $step == 1 ) $svg .= $this->drawSMDPad($item);
                break;

            case \XLay\ItemType::TEXT:
                if( $step == 1 ) $svg .= $this->drawLine($item);
                break;
    
            default:
                throw new \Exception("Unknown Object ".$item->getType()." Type!");
                break;
        }
        return $svg;
    }

    private function drawSMDPad(\XLay\Item $item) : string
    {
        return $this->drawPoly($item);
    }

    private function drawCircle(\XLay\Item $item) : string
    {
        $size = ($item->getOuterRadius()-$item->getInnerRadius())/2;

        $layer = $item->getLayer();
        if( $item->getPlatedThrough() )
        {
            $layer = \XLay\Layer::M;
        }

        if( $item->getSoldermask() )
        {
            $layer = $layer | \XLay\Layer::COPPER;
        }

        $color = $this->getColor($layer);

       

        if( $item->getStartAngle() != $item->getEndAngle() )
        {
            $startX = cos( -$item->getEndAngle() / 180 * pi() ) * $item->getRadius()/2 + $item->getX();
            $startY = sin( -$item->getEndAngle() / 180 * pi() ) * $item->getRadius()/2 + -$item->getY();
    
            $endX = cos( -$item->getStartAngle() / 180 * pi() ) * $item->getRadius()/2 + $item->getX();
            $endY = sin( -$item->getStartAngle() / 180 * pi() ) * $item->getRadius()/2 + -$item->getY();

            $dir = 0;

            if(($item->getEndAngle()-180) > $item->getStartAngle())
            {
                $dir = 1;
            }

            $svg = '
                <path 
                    stroke-linecap="round"
                    style="fill: none;"
                    stroke-width="'.($size).'" stroke="'.$color.'" d="
                    M '.$startX.' '.$startY.' 
                    A '.($item->getRadius()/2).' '.($item->getRadius()/2).' 0 '.$dir.' 1 '.$endX.' '.$endY.'" />
            ';    
        }
        else
        {
            $svg = 
            '<circle
                style="fill: none;" 
                cx="'.($item->getX()).'"
                cy="'.(-$item->getY()).'"
                r="'.($item->getRadius()/2).'"
                stroke-width="'.($size).'"
                stroke="'.$color.'"
            />';
        }


        return $svg;
    }

    private function drawDrill(\XLay\Item $item) : string
    {
        $color = "#000000";

        $svg = 
            '<circle 
                cx="'.($item->getX()).'"
                cy="'.(-$item->getY()).'"
                r="'.($item->getInnerRadius()/2).'"
                stroke-width="0"
                fill="'.$color.'"
            />';

        return $svg;
    }

    private function drawTHTPad(\XLay\Item $item)
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

        $color = $this->getColor($layer);
        $svg = '';

        switch($shape)
        {
            case \XLay\ShapeType::CIRCLE:
                $svg .= 
                    '<circle 
                        cx="'.($item->getX()).'"
                        cy="'.(-$item->getY()).'"
                        r="'.($item->getOuterRadius()/2).'"
                        stroke-width="0"
                        fill="'.$color.'"
                    />';
                break;

            case \XLay\ShapeType::SQUARE:
                $svg .= 
                    '<rect
                        width="'.($item->getOuterRadius()).'"
                        height="'.($item->getOuterRadius()).'"
                        x="'.($item->getX()-$item->getOuterRadius()/2).'"
                        y="'.(-$item->getY()-$item->getOuterRadius()/2).'"
                        stroke-width="0"
                        fill="'.$color.'"
                    />';
                break;

            case \XLay\ShapeType::OCTAGON:

                $points = [
                    [$item->getX() - $item->getOuterRadius() / 2 + $this->offset[0] ,
                    -$item->getY() + $item->getOuterRadius() / 4 + $this->offset[1]] ,

                    [$item->getX() - $item->getOuterRadius() / 4 + $this->offset[0] ,
                    -$item->getY()  + $item->getOuterRadius() / 2 + $this->offset[1]] ,

                    [$item->getX()  + $item->getOuterRadius() / 4 + $this->offset[0] ,
                    -$item->getY() + $item->getOuterRadius() / 2 + $this->offset[1]] ,

                    [$item->getX() + $item->getOuterRadius() / 2 + $this->offset[0] ,
                    -$item->getY() + $item->getOuterRadius() / 4 + $this->offset[1]] ,

                    [$item->getX() + $item->getOuterRadius() / 2 + $this->offset[0] ,
                    -$item->getY()  - $item->getOuterRadius() / 4 + $this->offset[1]] ,

                    [$item->getX() + $item->getOuterRadius() / 4 + $this->offset[0] ,
                    -$item->getY() - $item->getOuterRadius() / 2 + $this->offset[1]] ,

                    [$item->getX() - $item->getOuterRadius() / 4 + $this->offset[0] ,
                    -$item->getY() - $item->getOuterRadius() / 2 + $this->offset[1]] ,

                    [$item->getX() - $item->getOuterRadius() / 2 + $this->offset[0] ,
                    -$item->getY() - $item->getOuterRadius() / 4 + $this->offset[1] ]
                ];

                $svg .= $this->drawPath($points, $color, 0, '#000000');

                break;

            case \XLay\ShapeType::CIRCLE_H:
                $ry = $item->getRadius()-$item->getRadius()/4;
                $rx = $ry;

                $svg .= '
                    <path 
                        fill="'.$color.'" stroke-linecap="round"
                        stroke-width="0" stroke="#000000" d="
                        M '.($item->getX()-$rx).' '.(-$item->getY()-$ry).' 
                        A '.($item->getRadius()/2).' '.($item->getRadius()/2).' 0 0 0 '.($item->getX()-$rx).' '.(-$item->getY()+$ry).'

                        L '.($item->getX()+$rx).' '.(-$item->getY()+$ry).' 

                        A '.($item->getRadius()/2).' '.($item->getRadius()/2).' 0 0 0 '.($item->getX()+$rx).' '.(-$item->getY()-$ry).'

                        L '.($item->getX()-$rx).' '.(-$item->getY()-$ry).' 

                        
                        " />
                    ';
                break;

            case \XLay\ShapeType::OCTAGON_H:
                $points = [
                    [$item->getX() - $item->getOuterRadius() / 2 - $item->getOuterRadius()/2 + $this->offset[0] ,
                    -$item->getY() + $item->getOuterRadius() / 4 + $this->offset[1]] ,

                    [$item->getX() - $item->getOuterRadius() / 4 - $item->getOuterRadius()/2 + $this->offset[0] ,
                    -$item->getY() + $item->getOuterRadius() / 2 + $this->offset[1]] ,

                    [$item->getX() + $item->getOuterRadius() / 4 + $item->getOuterRadius()/2 + $this->offset[0] ,
                    -$item->getY() + $item->getOuterRadius() / 2 + $this->offset[1]] ,

                    [$item->getX() + $item->getOuterRadius() / 2 + $item->getOuterRadius()/2 + $this->offset[0] ,
                    -$item->getY() + $item->getOuterRadius() / 4 + $this->offset[1]] ,

                    [$item->getX() + $item->getOuterRadius() / 2 + $item->getOuterRadius()/2 + $this->offset[0] ,
                    -$item->getY() - $item->getOuterRadius() / 4 + $this->offset[1]] ,

                    [$item->getX() + $item->getOuterRadius() / 4 + $item->getOuterRadius()/2 + $this->offset[0] ,
                    -$item->getY() - $item->getOuterRadius() / 2 + $this->offset[1]] ,

                    [$item->getX() - $item->getOuterRadius() / 4 - $item->getOuterRadius()/2 + $this->offset[0] ,
                    -$item->getY() - $item->getOuterRadius() / 2 + $this->offset[1]] ,

                    [$item->getX() - $item->getOuterRadius() / 2 - $item->getOuterRadius()/2 + $this->offset[0] ,
                    -$item->getY() - $item->getOuterRadius() / 4 + $this->offset[1]] 
                ];
                $svg .= $this->drawPath($points, $color, 0, '#000000');

                break;

            case \XLay\ShapeType::RECT_H:
                $svg .= 
                    '<rect
                        width="'.($item->getOuterRadius()*2).'"
                        height="'.($item->getOuterRadius()).'"
                        x="'.($item->getX()-$item->getOuterRadius()).'"
                        y="'.(-$item->getY()-$item->getOuterRadius()/2).'"
                        stroke-width="0"
                        fill="'.$color.'"
                    />';
                break;

            case \XLay\ShapeType::CIRCLE_V:
                $ry = $item->getRadius()-$item->getRadius()/4;
                $rx = $ry;

                $svg .= '
                    <path 
                        fill="'.$color.'" stroke-linecap="round"
                        stroke-width="0" stroke="#000000" d="
                        M '.($item->getX()-$rx).' '.(-$item->getY()+$ry).' 
                        A '.($item->getRadius()/2).' '.($item->getRadius()/2).' 0 0 0 '.($item->getX()+$rx).' '.(-$item->getY()+$ry).'

                        L '.($item->getX()+$rx).' '.(-$item->getY()-$ry).' 

                        A '.($item->getRadius()/2).' '.($item->getRadius()/2).' 0 0 0 '.($item->getX()-$rx).' '.(-$item->getY()-$ry).'

                        L '.($item->getX()-$rx).' '.(-$item->getY()+$ry).' 

                        
                        " />
                    ';
                break;

            case \XLay\ShapeType::OCTAGON_V:

                $points = [
                    [$item->getX() - $item->getOuterRadius() / 2 + $this->offset[0] ,
                    -$item->getY() + $item->getOuterRadius() / 4 + $item->getOuterRadius()/2 + $this->offset[1]] ,

                    [$item->getX() - $item->getOuterRadius() / 4 + $this->offset[0] ,
                    -$item->getY() + $item->getOuterRadius() / 2 + $item->getOuterRadius()/2 + $this->offset[1]] ,

                    [$item->getX() + $item->getOuterRadius() / 4 + $this->offset[0] ,
                    -$item->getY() + $item->getOuterRadius() / 2 + $item->getOuterRadius()/2 + $this->offset[1]] ,

                    [$item->getX() + $item->getOuterRadius() / 2 + $this->offset[0] ,
                    -$item->getY() + $item->getOuterRadius() / 4 + $item->getOuterRadius()/2 + $this->offset[1]] ,

                    [$item->getX() + $item->getOuterRadius() / 2 + $this->offset[0] ,
                    -$item->getY() - $item->getOuterRadius() / 4 - $item->getOuterRadius()/2 + $this->offset[1]] ,

                    [$item->getX() + $item->getOuterRadius() / 4 + $this->offset[0] ,
                    -$item->getY() - $item->getOuterRadius() / 2 - $item->getOuterRadius()/2 + $this->offset[1]] ,

                    [$item->getX() - $item->getOuterRadius() / 4 + $this->offset[0] ,
                    -$item->getY() - $item->getOuterRadius() / 2 - $item->getOuterRadius()/2 + $this->offset[1]] ,

                    [$item->getX() - $item->getOuterRadius() / 2 + $this->offset[0] ,
                    -$item->getY() - $item->getOuterRadius() / 4 - $item->getOuterRadius()/2 + $this->offset[1]] 
                ];

                $svg = $this->drawPath($points, $color, 0, '#000000');
                break;

            case \XLay\ShapeType::RECT_V:
                $svg .= 
                    '<rect
                        width="'.($item->getOuterRadius()).'"
                        height="'.($item->getOuterRadius()*2).'"
                        x="'.($item->getX()-$item->getOuterRadius()/2).'"
                        y="'.(-$item->getY()-$item->getOuterRadius()).'"
                        stroke-width="0"
                        fill="'.$color.'"
                    />';
                break;

            default:
                throw new \Exception("Unknwon THT Shape [$shape]!");
                break;
        }

        return $svg;
    }

    private function drawLine(\XLay\Item $item)
    {
        $svg = '';

        $subItems = $item->getTextObjects();
        foreach( $subItems as $si )
        {
            $svg .= $this->drawObject($si, 1);
        }

        $size = $item->getLineWidth();
        if( $size < 0 ) $size = 0.1;

        $layer = $item->getLayer();
        if( $item->getPlatedThrough() )
        {
            //$layer = \XLay\Layer::M;
        }

        if( $item->getSoldermask() )
        {
            $layer = $layer | \XLay\Layer::COPPER;
        }

        $points = $item->getPoints();
        $first = true;

        $color = $this->getColor($layer);

        $svg .= '<path style="fill: none;" stroke-linecap="round" stroke-width="'.$size.'" stroke="'.$color.'" d="';

        foreach( $points as $point )
        {
            if( !$first )
            {

                $svg .= ' L '.$point->getX().' '.-$point->getY();
            }
            else
            {
                $svg .= 'M '.$point->getX().' '.-$point->getY();
            }
            $first = false;
        }

        $svg .= '" />';

        return $svg;
    }

    private function drawPoly(\XLay\Item $item) : string
    {
        $svg = '';

        $subItems = $item->getTextObjects();
        foreach( $subItems as $si )
        {
            $svg .= $this->drawObject($si, 1);
        }

        $size = $item->getLineWidth();
        if( $size <= 0 ) $size = 0.1;

        $layer = $item->getLayer();
        if( $item->getPlatedThrough() )
        {
            //$layer = \XLay\Layer::M;
        }

        if( $item->getSoldermask() )
        {
            $layer = $layer | \XLay\Layer::COPPER;
        }

        $points = $item->getPoints();
        $first = true;

        $color = $this->getColor($layer);

        $svg .= '<path fill="'.$color.'" stroke-width="0" stroke-linecap="round" d="';

        foreach( $points as $point )
        {
            if( !$first )
            {

                $svg .= ' L '.$point->getX().' '.-$point->getY();
            }
            else
            {
                $svg .= 'M '.$point->getX().' '.-$point->getY();
            }
            $first = false;
        }

        $svg .= '" />';

        return $svg;
    }

    function drawPath($points, $fill, $strokeWidth, $color) : string
    {
        $svg = '<path fill="'.$fill.'" stroke-width="'.$strokeWidth.'" stroke-linecap="round" stroke="'.$color.'" d="';

        $first = true;

        foreach( $points as $point )
        {
            if( !$first )
            {

                $svg .= ' L '.$point[0].' '.$point[1];
            }
            else
            {
                $svg .= 'M '.$point[0].' '.$point[1];
            }
            $first = false;
        }

        $svg .= '" />';

        return $svg;
    }

}