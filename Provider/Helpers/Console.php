<?php
namespace Provider\Helpers;

use Composer\IO\IOInterface;

class Console
{
    private IOInterface $io;
    function __construct(IOInterface $io)
    {
        $this->io = $io;
    }

    function log($sMessage, $sTopic = 'Novum http util')
    {
        $this->io->write(" -  $sTopic <info>{$sMessage}</info>");
    }
}
