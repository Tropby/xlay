<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if( isset( $_GET["url"] ) )
{
    require_once(dirname(__FILE__)."/../../src/xlay.inc.php");

    @mkdir("cache");
    $filename = "cache/".uniqid("cache_").".svg";

    $renderer = new \XLay\Renderer\SVG();
    try
    {
        $file = \XLay\XLay::loadLay6FromUrl($_GET["url"]);
        $svg = $renderer->render($file->getBoards()[0]);
    
        header("content-type: image/svg+xml");
        echo $svg;
        }
    catch(\Exception $e)
    {
        header("HTTP/1.0 404 URL not found!");
        echo "Failed to load file from URL: \"".$_GET["url"]."\"";
        die();
    }
}
else
{
    header("HTTP/1.0 404 URL not found!");
    echo "Usage: url.php?url=https://example.org/filename.lay6";
}