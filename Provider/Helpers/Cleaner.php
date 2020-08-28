<?php

namespace Provider\Helpers;

use DirectoryIterator;
use Provider\Plugin;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Cleaner
{

    /**
     * @param Configuration $configuration
     */
    static function removePrevious(Configuration $configuration, Console $console)
    {

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($configuration->getVhostDir(),
                RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            if($fileinfo->isDir())
            {
                $console->log("Removing directory " . $fileinfo->getRealPath() , Plugin::$installerName);
                rmdir($fileinfo->getRealPath());
            }
            else
            {
                $console->log("Removing file " . $fileinfo->getRealPath() , Plugin::$installerName);
                unlink($fileinfo->getRealPath());
            }
        }

    }
}
