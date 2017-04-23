<?php
/**
 * @author Marian Meres
 */
namespace MM\Util;

/**
 *
 */
class SqlHelper
{
    /**
     * @param array $custom
     * @return array
     */
    public static function getSqlReplaceMap(array $custom = array())
    {
        // mapa najcastejsich problematickych rozdielnych veci...
        return array_merge(array(
            '{serial-primary-key}' => array(
                'pgsql'  => "serial primary key",
                'sqlite' => "integer not null primary key autoincrement",
                'mysql'  => "integer unsigned not null auto_increment primary key",
            ),
            '{smallserial-primary-key}' => array(
                'pgsql'  => "smallserial primary key",
                'sqlite' => "smallint not null primary key autoincrement", // General error: 1 AUTOINCREMENT is only allowed on an INTEGER PRIMARY KEY
                'mysql'  => "integer not null auto_increment primary key",
            ),
            '{bigserial-primary-key}' => array(
                'pgsql'  => "bigserial primary key",
                'sqlite' => "integer not null primary key autoincrement", // General error: 1 AUTOINCREMENT is only allowed on an INTEGER PRIMARY KEY
                'mysql'  => "bigint unsigned not null auto_increment primary key",
            ),
            '{serial}' => array(
                'pgsql'  => "serial", // 4 bytes autoincrementing integer 1 to 2147483647
                'sqlite' => "integer not null autoincrement",
                'mysql'  => "integer unsigned not null auto_increment", // 1 to 4294967295
            ),
            '{smallserial}' => array(
                'pgsql'  => "smallserial",
                'sqlite' => "integer not null autoincrement",
                'mysql'  => "smallint unsigned not null auto_increment",
            ),
            '{bigserial}' => array(
                'pgsql'  => "bigserial", // 4 bytes autoincrementing integer 1 to 2147483647
                'sqlite' => "integer not null autoincrement",
                'mysql'  => "bigint unsigned not null auto_increment", // 1 to 4294967295
            ),
            '{unsigned}' => array(
                'pgsql'  => "",
                'sqlite' => "",
                'mysql'  => "unsigned",
            ),
            '{signed}' => array(
                'pgsql'  => "",
                'sqlite' => "",
                'mysql'  => "signed",
            ),
            '{timestamp}' => array(
                'pgsql'  => "timestamp with time zone",
                'sqlite' => "timestamp", // sqlite v skutocnosti pouzije text
                'mysql'  => "datetime", // mysql nevie ulozit priamo timezone
            ),
            '{timestamp-default-now}' => array(
                'pgsql'  => "timestamp with time zone default now()",
                'sqlite' => "timestamp default '0000-00-00 00:00:00'", // sqlite v skutocnosti pouzije text
                'mysql'  => "datetime default CURRENT_TIMESTAMP", // mysql nevie ulozit priamo timezone
            ),
            '{bool}' => array(
                'pgsql'  => "smallint", // 2 bytes
                'sqlite' => "int",
                'mysql'  => "tinyint unsigned", // 1 byte
            ),
            '{tinyint}' => array(
                'pgsql'  => "smallint", // 2 bytes
                'sqlite' => "int",
                'mysql'  => "tinyint", // 1 byte
            ),
            '{mediumtext}' => array(
                'pgsql'  => "text",
                'sqlite' => "text",
                'mysql'  => "mediumtext",
            ),
            '{longtext}' => array(
                'pgsql'  => "text",
                'sqlite' => "text",
                'mysql'  => "longtext",
            ),
            '{blob}' => array(
                'pgsql'  => "bytea",
                'sqlite' => "blob",
                'mysql'  => "blob",
            ),
            '{longblob}' => array(
                'pgsql'  => "bytea",
                'sqlite' => "blob",
                'mysql'  => "longblob",
            ),
            '{engine-innodb}' => array(
                'pgsql'  => "",
                'sqlite' => "",
                'mysql'  => " ENGINE=InnoDb ",
            ),
            '{engine-myisam}' => array(
                'pgsql'  => "",
                'sqlite' => "",
                'mysql'  => " ENGINE=MyISAM ",
            ),
            '{drop-table-cascade}' => array(
                'pgsql'  => "cascade",
                'sqlite' => "", // sqlite nepodporuje cascade
                'mysql'  => "cascade",
            ),
            '{default-charset-utf8}' => array(
                'pgsql'  => "",
                'sqlite' => "",
                'mysql'  => " DEFAULT CHARSET=utf8 ",
            ),
            '{charset-utf8-per-col}' => array(
                'pgsql'  => "",
                'sqlite' => "",
                'mysql'  => " CHARACTER SET utf8 ",
            ),
            '{collation-utf8-bin}' => array(
                'pgsql'  => "",
                'sqlite' => "",
                'mysql'  => " COLLATE=utf8_bin ",
            ),
            '{collation-utf8-bin-per-col}' => array(
                'pgsql'  => "",
                'sqlite' => "",
                'mysql'  => " COLLATE utf8_bin ",
            ),
            '{zero-timestamp}' => array(
                'pgsql'  => "1970-01-01 00:00:00+00",
                'sqlite' => "0000-00-00 00:00:00",
                'mysql'  => "0000-00-00 00:00:00",
            ),
            '{q}' => [
                'pgsql'  => '"',
                'sqlite' => '"',
                'mysql'  => "`",
            ],
            '{xml}' => [
                'pgsql'  => 'xml',
                'sqlite' => 'text',
                'mysql'  => "text",
            ],

            // todo podla potreby
        ), $custom);
    }

    /**
     * @param $sql
     * @param string $vendor
     * @param array $map
     * @return mixed
     */
    public static function getVendorSql($sql, $vendor = 'mysql', array $map = null)
    {
        if (empty($map)) {
            $map = self::getSqlReplaceMap();
        }

        $searchReplace = array();
        foreach ($map as $needle => $_replace) {
            // teoreticky nemusim pre dany needle a adapter mat replace...
            // vtedy sa jednoducho dany needle kluc bude ignorovat
            if (isset($_replace[$vendor])) {
                $searchReplace[$needle] = $_replace[$vendor];
            }
        }

        $out = str_replace(
            array_keys($searchReplace), array_values($searchReplace), $sql
        );

        // Sqlite hacks... toto cele je trosku problematicke, ze sa snazime
        // byt tak trosku schizofrenici a riesit paralene sqlite a mysql...
        // Sqlite ma vsak obrovsku vyhody ze vie trivialne bezat in memory, a tym
        // padom je priam stvorena na rychle testovanie...
        // Nicmenej, najskor dojde k situaciam, kde jednoducho sqlite bude musiet
        // byt pri niektorych testoch skipnuta
        if (preg_match("/sqlite|pgsql/", $vendor)) {
            // NEDOKONALY special case hack na enum fix pre sqlite
            // skusi "enum ..." definciu nahradit za "text"
            //$rgx = '/( enum\s*[^\)]+\s*\))\s*(,|})/i';
            // update: doplname aj not null
            //$rgx = '/( enum\s*[^\)]+\s*\))\s*((not)?\s+null)?\s*(,|})/i';
            //$rgx = '/( enum\s*[^\)]+\s*\))\s*((not)?\s+null)?\s*(,|})/i';
            $rgx = '/( enum\s*[^\)]+\s*\))([^,}]*)(,|})/i';
//            if (preg_match($rgx, $out, $m)) {
//                print_r($m);
//                exit;
//            }
            $out = preg_replace($rgx, ' text$2$3', $out);
            //die($out);
        }

        return $out;
    }
}