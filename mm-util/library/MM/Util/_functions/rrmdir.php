<?php
/**
 * recursive rmdir
 * @param $dir
 */
function rrmdir($dir)
{
    $iterator = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($dir),
        \RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $path) {
        if (preg_match('/^\.\.?$/', $path->getFilename())) {
            continue;
        }
        if ($path->isDir()) {
            rmdir($path->getPathname());
        } else {
            unlink($path->getPathname());
        }
    }

    unset($iterator); // needed to avoid "permission denied" below
    rmdir($dir);
}


/*
// toto funguje pekne, ale je to menej citatelne
//http://www.php.net/manual/en/function.unlink.php#100092
function rrmdir($path)
{
  return is_file($path)?
    @unlink($path):
    array_map('rrmdir',glob($path.'/*'))==@rmdir($path)
  ;
}
*/