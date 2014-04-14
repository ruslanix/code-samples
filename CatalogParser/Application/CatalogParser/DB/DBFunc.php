<?php

namespace Application\CatalogParser\DB;

class DBFunc
{
    public function createSchema()
    {
        DBConnection::getConnection()->Execute('DROP TABLE IF EXISTS catalog_data');
        DBConnection::getConnection()->Execute('
            CREATE TABLE IF NOT EXISTS `catalog_data` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `catalog` varchar(50) COLLATE utf8_general_ci NOT NULL,
              `query` varchar(255) COLLATE utf8_general_ci NOT NULL,
              `data` text COLLATE utf8_general_ci,
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=0 ;
        ');
    }

    public function truncateDatabase()
    {
        DBConnection::getConnection()->Execute('TRUNCATE `catalog_data`');
    }

    public function saveCatalogData($catalog, $query, $data)
    {
        $catalog = DBConnection::getConnection()->qstr($catalog);
        $query = DBConnection::getConnection()->qstr($query);
        $data = DBConnection::getConnection()->qstr($data);

        DBConnection::getConnection()->Execute("
           INSERT INTO catalog_data (catalog, query, data)
           VALUES ($catalog, $query, $data)
       ");
    }
}
