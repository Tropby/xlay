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

if( isset( $_FILES["lay6"] ))
{
    require_once("../../src/xlay.inc.php");

    $filename = uniqid("cache_").".png";

    $renderer = new \XLay\Renderer\Image();
    if( isset($_POST["photo"]))
    {
        $renderer->setColorScheme(\XLay\Layer::COLORS_FOTO);
    }

    switch( $_POST["layers"] )
    {
        case "LAYERS_TOP_ONLY_ORDER": $layers = \XLay\Layer::LAYERS_TOP_ONLY_ORDER; break;
        case "LAYERS_BOTTOM_ONLY_ORDER": $layers = \XLay\Layer::LAYERS_BOTTOM_ONLY_ORDER; break;
        default: $layers = \XLay\Layer::LAYERS_DEFAULT_ORDER; break;
    }

    if( substr($_FILES["lay6"]["name"], -5) == ".lay6" )
    {
        $file = \XLay\XLay::loadLay6($_FILES["lay6"]["tmp_name"]);
        unlink($_FILES["lay6"]["tmp_name"]);
        $renderer->render($file->getBoards()[0], $filename, $layers, [0,0], isset($_POST["fliph"]));
    }
    else
    {
        $file = \XLay\XLay::loadMacro($_FILES["lay6"]["tmp_name"]);
        unlink($_FILES["lay6"]["tmp_name"]);
        $renderer->render($file, $filename, $layers, [$file->getOffsetX(),$file->getOffsetY()], isset($_POST["fliph"]));
    }
}

?>
<html>
    <head>
        <title>Sprint Layout Image Creator</title>
    </head>
    <body>
        <h1>Upload your SprintLayout File</h1>
        <form method="POST" enctype="multipart/form-data" >
            <input type="file" name="lay6" /><br /><br />
            <select name="layers">
                <option value="LAYERS_DEFAULT_ORDER">Layout Default</option>
                <option value="LAYERS_TOP_ONLY_ORDER">Top Only</option>
                <option value="LAYERS_BOTTOM_ONLY_ORDER">Bottom Only</option>
            </select><br /><br />
            <input type="checkbox" name="photo" id="photo" /> <label for="photo">Photo Renderer</label><br /><br />
            <input type="checkbox" name="fliph" /> <label for="fliph">Flip Horizontal</label><br /><br />
            <input type="submit" value="Upload *.lay6/*.lmk File" name="submit" />
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

