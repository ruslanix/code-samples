<?php

namespace Application\CatalogParser\DB;

use Configs\GlobalConfigs;

class DBConnection
{
    protected
        static $connection = null
    ;

    static public function getConnection()
    {
        if(!self::$connection){
            self::$connection = \NewADOConnection(GlobalConfigs::DB_DRIVER);
            self::$connection->connect(
                GlobalConfigs::DB_HOST,
                GlobalConfigs::DB_USER,
                GlobalConfigs::DB_PASSWORD,
                GlobalConfigs::DB_NAME);

            self::$connection->SetCharSet('utf8');
        }

        return self::$connection;
    }
}
