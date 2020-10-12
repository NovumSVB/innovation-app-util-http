<?php
namespace Provider\Helpers;


class Directory
{

    /**
     * @todo this may not be the best way of detecting the current root but as this is supposed to work within both
     * Docker and outside, currently cannot come up with a butter sollution.
     * @return string
     */
    static function getSystemRoot():string
    {
        return getcwd();
    }
}