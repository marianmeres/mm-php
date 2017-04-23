<?php
/**
 * Toto je wrapper nad nativnymi gz* umoznujuci gzipnut aj velmi velke fajly
 * bez zvysenych narokov na pamat - streamuje po 8 KB
 * 
 * zobrate a upravene z:
 * http://www.php.net/manual/en/function.gzwrite.php#34955
 * 
 */
function gzCompressFile($filename, $gzFilename = null, $level = 9)
{
    if (null === $gzFilename) {
        $gzFilename = "$filename.gz";
    }
    
    $level = (int) $level;
    if ($level < 0 || $level > 9) {
        $level = 9; // silently set to default
    }
    
    $error = false;
    
    if ($gp = gzopen($gzFilename, "wb$level")) {
        if ($fp = fopen($filename, 'rb')) {
            $length = 8192;
            while (!feof($fp)) {
                gzwrite($gp, fread($fp, $length), $length);
            }
            fclose($fp);
        } else {
            $error = true;
        }
        gzclose($gp);
    } else {
        $error = true;
    }
    
    return $error ? false : $gzFilename;
}