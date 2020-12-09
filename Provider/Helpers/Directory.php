<?php
namespace Provider\Helpers;


class Directory
{

    /**
     * @return string
     */
    static function getSystemRoot():string
    {

        if(isset($_ENV['SYSTEM_ROOT']))
        {
            return $_ENV['SYSTEM_ROOT'];
        }
        else if(isset($_SERVER['SYSTEM_ROOT']))
        {
            return $_SERVER['SYSTEM_ROOT'];
        }

        return getcwd();
    }
}