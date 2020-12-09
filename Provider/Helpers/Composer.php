<?php
namespace Provider\Helpers;


class Composer
{

    private $aComposer;
    function __construct(string $sFilename)
    {
        $this->aComposer = json_decode(file_get_contents($sFilename), true);
    }
}
