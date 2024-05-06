<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function endsWith( $haystack, $needle ) {
    $length = strlen( $needle );
    if( !$length ) {
        return true;
    }
    return substr( $haystack, -$length ) === $needle;
}

if( isset( $_FILES["lay6"] ) )
{
    require_once("../src/xlay.inc.php");
    $xlay = new \XLay\XLay();

    $filename = uniqid("cache_").".png";

    if( endsWith($_FILES["lay6"]["tmp_name"], ".lay6") ) 
    {
        $file = $xlay->loadLay6($_FILES["lay6"]["tmp_name"]);
        $renderer = new \XLay\Renderer\Image();
        $renderer->render($file->getBoards()[0], $filename, \XLay\Layer::LAYERS_DEFAULT_ORDER);
    }
    else
    {
        $file = $xlay->loadMacro($_FILES["lay6"]["tmp_name"]);
        $renderer = new \XLay\Renderer\Image();
        $renderer->render($file, $filename, \XLay\Layer::LAYERS_DEFAULT_ORDER,[0,0,0],[$file->getOffsetX(),$file->getOffsetY()]);

    }
}

?>
<html>
    <head>
        <title>Sprint Layout Image Creator</title>
    </head>
    <body>
        <h1>Upload your layout</h1>
        <form method="POST" enctype="multipart/form-data" >
            <input type="file" name="lay6" /><br /><br />
            <input type="submit" value="Upload Lay6 File" name="submit" />
        </form>
        <hr />
<?php
if( isset( $_FILES["lay6"] ) )
{
    echo "<h2>".$_FILES["lay6"]["name"]."</h2>";
    echo '
        <a href="'.$filename.'" target="_blank"><img 
            src="'.$filename.'"
            style="width: 100%; max-width: 800px;"
        /></a>';
}
?>
    </body>
</html>

