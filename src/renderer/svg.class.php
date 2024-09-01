<?php

namespace XLay\Renderer;

use XLay\XLay;

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
        \Xlay\Board & $board,
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
        >';

        $color = $this->getColor(\XLAy\Layer::B);
        $svg .= '<rect fill="'.$color.'" width="'.$board->getSizeX().'" height="'.$board->getSizeY().'" />';

        $transX = $offset[0];
        if( $flipHorizontal )
        {
            $transX -= $board->getSizeX();
        }

        $svg .= '<g transform="'.($flipHorizontal ? 'scale(-1,1) ' : '').'translate('.$transX.', '.$offset[1].')">';

        $objects = $board->getObjects();

        foreach( $layers as $layer )
        {
            if( $board->hasGroundPlane($layer) )
            {
                $svg .= '<g id="'.\XLay\Layer::getLayerName($layer | \XLay\Layer::GROUND_PLANE).'">';

                // Draw the groundplane
                $svg .= 
                    '<rect
                        width="'.($board->getSizeX()).'"
                        height="'.($board->getSizeY()).'"
                        x="0"
                        y="0"
                        stroke-width="0"
                        fill="'.$this->getColor($layer | \XLay\Layer::GROUND_PLANE).'"
                    />';

                foreach($objects as $object)
                {
                    if( ($object->getLayer() == $layer || $object->getPlatedThrough()) && $object->getGroundDistance() > 0)
                    {
                        $svg .= $this->drawObject($object, 0);
                    }
                }

                $svg .= '</g>';
            }

            
            $svg .= '<g id="'.\XLay\Layer::getLayerName($layer).'">';

            // Draw the Layer
            foreach($objects as $object)
            {
                if( $object->getLayer() == $layer )
                {
                    $svg .= $this->drawObject($object, 1);
                }
            }
            $svg .= '</g>';
        }

        // Draw Throuholes
        $svg .= '<g id="THT">';
        foreach($objects as $object)
        {
            if( ( in_array($object->getLayer(), $layers) && $object->getPlatedThrough() ) || 
                ( in_array(\XLay\Layer::C1, $layers) && $object->getPlatedThrough() ) || 
                ( in_array(\XLay\Layer::C2, $layers) && $object->getPlatedThrough() ) )
            {
                $svg .= $this->drawObject($object, 2);
            }
        }
        $svg .= '</g>';

        // Draw Drills
        $svg .= '<g id="DRILLS">';
        foreach($objects as $object)
        {
            if( $object->getType() == \XLay\ItemType::THT_PAD )
            {
                $svg .= $this->drawObject($object, 3);
            }
        }
        $svg .= '</g>';

        $svg .= '</g>';
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
        
        throw new \Exception("Can not get color for layer $layer [".dechex($layer)." // ".decbin($layer)."]!<br /><br />".nl2br(print_r($this->colorScheme, true)));
    }

    private function drawObject(\XLay\Item $item, int $step) : string
    {
        $svg = '';
        switch($item->getType())
        {
            case \XLay\ItemType::LINE:
                if( $step == 0 ) $svg .= $this->drawLine($item, true);
                if( $step == 1 ) $svg .= $this->drawLine($item);
                break;

            case \XLay\ItemType::CIRCLE:
                if( $step == 0 ) $svg .= $this->drawCircle($item, true);
                if( $step == 1 ) $svg .= $this->drawCircle($item);
                break;

            case \XLay\ItemType::POLY:
                if( $step == 0 ) $svg .= $this->drawPoly($item, true);
                if( $step == 1 ) $svg .= $this->drawPoly($item);
                break;

            case \XLay\ItemType::THT_PAD:
                if( $step == 0 ) $svg .= $this->drawTHTPad($item, true);
                if( !$item->getPlatedThrough() && $step == 1) $svg .= $this->drawTHTPad($item);
                if( $item->getPlatedThrough() && $step == 2) $svg .= $this->drawTHTPad($item);
                if( $step == 3 ) $svg .= $this->drawDrill($item);
                break;

            case \XLay\ItemType::SMD_PAD:
                if( $step == 0 ) $svg .= $this->drawSMDPad($item, true);
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

    private function drawSMDPad(\XLay\Item $item, bool $groundPlaneCutting = false) : string
    {
        return $this->drawPoly($item, $groundPlaneCutting);
    }

    private function drawCircle(\XLay\Item $item, bool $groundPlaneCutting = false) : string
    {
        $size = ($item->getOuterRadius()-$item->getInnerRadius())/2;

        $layer = $item->getLayer();
        if( $item->getPlatedThrough() )
        {
            $layer = \XLay\Layer::M;
        }

        if( $groundPlaneCutting )
        {
            $size += $item->getGroundDistance() * 2;
            $layer = \XLay\Layer::B;
        }

        if( $item->getSoldermask() )
        {
            $layer = $layer | \XLay\Layer::COPPER;
        }

        $color = $this->getColor($layer);

       

        if( $item->getStartAngle() != $item->getEndAngle() )
        {
            $degree = $item->getEndAngle() - $item->getStartAngle();
            $degree = $degree < 0 ? ($degree + 360) : $degree;

            $startX = cos( -$item->getEndAngle() / 180 * pi() ) * $item->getRadius()/2 + $item->getX();
            $startY = sin( -$item->getEndAngle() / 180 * pi() ) * $item->getRadius()/2 + -$item->getY();
    
            $endX1 = cos( (-$item->getEndAngle()+$degree/3) / 180 * pi() ) * $item->getRadius()/2 + $item->getX();
            $endY1 = sin( (-$item->getEndAngle()+$degree/3) / 180 * pi() ) * $item->getRadius()/2 + -$item->getY();

            $endX2 = cos( (-$item->getEndAngle()+$degree*2/3) / 180 * pi() ) * $item->getRadius()/2 + $item->getX();
            $endY2 = sin( (-$item->getEndAngle()+$degree*2/3) / 180 * pi() ) * $item->getRadius()/2 + -$item->getY();

            $endX3 = cos( -$item->getStartAngle() / 180 * pi() ) * $item->getRadius()/2 + $item->getX();
            $endY3 = sin( -$item->getStartAngle() / 180 * pi() ) * $item->getRadius()/2 + -$item->getY();

            $svg = '
                <path 
                    stroke-linecap="round"
                    style="fill: none;"
                    stroke-width="'.($size).'" stroke="'.$color.'" d="
                    M '.$startX.' '.$startY.' 
                    A '.($item->getRadius()/2).' '.($item->getRadius()/2).' 0 0 1 '.$endX1.' '.$endY1.'
                    A '.($item->getRadius()/2).' '.($item->getRadius()/2).' 0 0 1 '.$endX2.' '.$endY2.'
                    A '.($item->getRadius()/2).' '.($item->getRadius()/2).' 0 0 1 '.$endX3.' '.$endY3.'
                    " />
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

    private function drawTHTPad(\XLay\Item $item, bool $groundPlaneCutting = false)
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

        $outerRadius = $item->getOuterRadius();

        if( $groundPlaneCutting )
        {
            $outerRadius += $item->getGroundDistance() * 2;
            $layer = \XLay\Layer::B;
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
                        r="'.($outerRadius/2).'"
                        stroke-width="0"
                        fill="'.$color.'"
                    />';
                break;

            case \XLay\ShapeType::SQUARE:
                $svg .= 
                    '<rect
                        width="'.($outerRadius).'"
                        height="'.($outerRadius).'"
                        x="'.($item->getX()-$outerRadius/2).'"
                        y="'.(-$item->getY()-$outerRadius/2).'"
                        stroke-width="0"
                        fill="'.$color.'"
                    />';
                break;

            case \XLay\ShapeType::OCTAGON:

                $points = [
                    [$item->getX() - $outerRadius / 2  ,
                    -$item->getY() + $outerRadius/ 4] ,

                    [$item->getX() - $outerRadius / 4  ,
                    -$item->getY()  + $outerRadius / 2 ],

                    [$item->getX() + $outerRadius / 4  ,
                    -$item->getY() + $outerRadius / 2] ,

                    [$item->getX() + $outerRadius / 2  ,
                    -$item->getY() + $outerRadius / 4] ,

                    [$item->getX() + $outerRadius / 2  ,
                    -$item->getY()  - $outerRadius / 4] ,

                    [$item->getX() + $outerRadius / 4  ,
                    -$item->getY() - $outerRadius / 2] ,

                    [$item->getX() - $outerRadius / 4  ,
                    -$item->getY() - $outerRadius / 2] ,

                    [$item->getX() - $outerRadius / 2  ,
                    -$item->getY() - $outerRadius / 4]
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
                    [$item->getX() - $outerRadius / 2 - $outerRadius/2  ,
                    -$item->getY() + $outerRadius / 4 ],

                    [$item->getX() - $outerRadius / 4 - $outerRadius/2  ,
                    -$item->getY() + $outerRadius / 2 ],

                    [$item->getX() + $outerRadius / 4 + $outerRadius/2  ,
                    -$item->getY() + $outerRadius / 2 ],

                    [$item->getX() + $outerRadius / 2 + $outerRadius/2  ,
                    -$item->getY() + $outerRadius / 4 ],

                    [$item->getX() + $outerRadius / 2 + $outerRadius/2  ,
                    -$item->getY() - $outerRadius / 4 ],

                    [$item->getX() + $outerRadius / 4 + $outerRadius/2  ,
                    -$item->getY() - $outerRadius / 2 ],

                    [$item->getX() - $outerRadius / 4 - $outerRadius/2  ,
                    -$item->getY() - $outerRadius / 2 ],

                    [$item->getX() - $outerRadius / 2 - $outerRadius/2  ,
                    -$item->getY() - $outerRadius / 4 ]
                ];
                $svg .= $this->drawPath($points, $color, 0, '#000000');

                break;

            case \XLay\ShapeType::RECT_H:
                $svg .= 
                    '<rect
                        width="'.($outerRadius*2).'"
                        height="'.($outerRadius).'"
                        x="'.($item->getX()-$outerRadius).'"
                        y="'.(-$item->getY()-$outerRadius/2).'"
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
                    [$item->getX() - $outerRadius / 2  ,
                    -$item->getY() + $outerRadius / 4 + $outerRadius/2 ],

                    [$item->getX() - $outerRadius / 4  ,
                    -$item->getY() + $outerRadius / 2 + $outerRadius/2 ],

                    [$item->getX() + $outerRadius / 4  ,
                    -$item->getY() + $outerRadius / 2 + $outerRadius/2] ,

                    [$item->getX() + $outerRadius / 2  ,
                    -$item->getY() + $outerRadius / 4 + $outerRadius/2 ],

                    [$item->getX() + $outerRadius / 2  ,
                    -$item->getY() - $outerRadius / 4 - $outerRadius/2 ],

                    [$item->getX() + $outerRadius / 4  ,
                    -$item->getY() - $outerRadius / 2 - $outerRadius/2 ],

                    [$item->getX() - $outerRadius / 4  ,
                    -$item->getY() - $outerRadius / 2 - $outerRadius/2 ],

                    [$item->getX() - $outerRadius / 2  ,
                    -$item->getY() - $outerRadius / 4 - $outerRadius/2 ]
                ];

                $svg = $this->drawPath($points, $color, 0, '#000000');
                break;

            case \XLay\ShapeType::RECT_V:
                $svg .= 
                    '<rect
                        width="'.($outerRadius).'"
                        height="'.($outerRadius*2).'"
                        x="'.($item->getX()-$outerRadius/2).'"
                        y="'.(-$item->getY()-$outerRadius).'"
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

    private function drawLine(\XLay\Item $item, bool $groundPlaneCutting = false)
    {
        $svg = '';

        if( $item->showTextObjects() )
        {
            $subItems = $item->getTextObjects();
            foreach( $subItems as $si )
            {
                $svg .= $this->drawObject($si, 1);
            }
        }

        $size = $item->getLineWidth();
        if( $size < 0 ) $size = 0.1;

        $layer = $item->getLayer();

        if( $item->getSoldermask() )
        {
            $layer = $layer | \XLay\Layer::COPPER;
        }

        if( $groundPlaneCutting )
        {
            $size += $item->getGroundDistance() * 2;
            $layer = \XLay\Layer::B;
        }

        $points = $item->getPoints();
        $first = true;

        $color = $this->getColor($layer);

        $svg .= '<path style="fill: none;" stroke-linecap="round" stroke-linejoin="round" stroke-width="'.$size.'" stroke="'.$color.'" d="';

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

    private function drawPoly(\XLay\Item $item, bool $groundPlaneCutting = false) : string
    {
        $svg = '';

        $subItems = $item->getTextObjects();
        foreach( $subItems as $si )
        {
            $svg .= $this->drawObject($si, 1);
        }

        if( $item->getType() != \XLay\ItemType::SMD_PAD )
        {
            $size = $item->getLineWidth();
            if( $size <= 0 ) $size = 0.1;
        }
        else
        {
            $size = 0;
        }

        $layer = $item->getLayer();

        if( $item->getCutOff() )
        {
            $layer = \XLay\Layer::B;
        }

        if( $item->getSoldermask() )
        {
            $layer = $layer | \XLay\Layer::COPPER;
        }

        if( $groundPlaneCutting )
        {
            $size += $item->getGroundDistance() * 2;
            $layer = \XLay\Layer::B;
        }

        $points = $item->getPoints();
        $first = true;

        $color = $this->getColor($layer);

        $lineStyle = "round";
        $lineJoin = "round";
        if( $item->getType() == \XLay\ItemType::SMD_PAD )
        {
            $lineStyle = "square";
            $lineJoin = "miter";
        }

        $svg .= '<path fill="'.$color.'" stroke="'.$color.'" stroke-width="'.$size.'" stroke-linejoin="'.$lineJoin.'" stroke-linecap="'.$lineStyle.'" d="';

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

        $svg .= ' Z" />';

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