<?php
/**
 *
 */

/**
 *
 */
class Server
{
    public static function autoloader()
    {

    }

    public function __construct()
    {
        spl_autoload_register();
    }
}
