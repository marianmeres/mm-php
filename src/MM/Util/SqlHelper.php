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
	public static function getSqlReplaceMap(array $custom = [])
	{
		// mapa najcastejsich problematickych rozdielnych veci...
		return array_merge(
			[
				'{serial-primary-key}' => [
					'pgsql' => 'serial primary key',
					'sqlite' => 'integer not null primary key autoincrement',
					'mysql' => 'integer unsigned not null auto_increment primary key',
				],
				'{smallserial-primary-key}' => [
					'pgsql' => 'smallserial primary key',
					'sqlite' => 'smallint not null primary key autoincrement', // General error: 1 AUTOINCREMENT is only allowed on an INTEGER PRIMARY KEY
					'mysql' => 'integer not null auto_increment primary key',
				],
				'{bigserial-primary-key}' => [
					'pgsql' => 'bigserial primary key',
					'sqlite' => 'integer not null primary key autoincrement', // General error: 1 AUTOINCREMENT is only allowed on an INTEGER PRIMARY KEY
					'mysql' => 'bigint unsigned not null auto_increment primary key',
				],
				'{serial}' => [
					'pgsql' => 'serial', // 4 bytes autoincrementing integer 1 to 2147483647
					'sqlite' => 'integer not null autoincrement',
					'mysql' => 'integer unsigned not null auto_increment', // 1 to 4294967295
				],
				'{smallserial}' => [
					'pgsql' => 'smallserial',
					'sqlite' => 'integer not null autoincrement',
					'mysql' => 'smallint unsigned not null auto_increment',
				],
				'{bigserial}' => [
					'pgsql' => 'bigserial', // 4 bytes autoincrementing integer 1 to 2147483647
					'sqlite' => 'integer not null autoincrement',
					'mysql' => 'bigint unsigned not null auto_increment', // 1 to 4294967295
				],
				'{unsigned}' => [
					'pgsql' => '',
					'sqlite' => '',
					'mysql' => 'unsigned',
				],
				'{signed}' => [
					'pgsql' => '',
					'sqlite' => '',
					'mysql' => 'signed',
				],
				'{timestamp}' => [
					'pgsql' => 'timestamp with time zone',
					'sqlite' => 'timestamp', // sqlite v skutocnosti pouzije text
					'mysql' => 'datetime', // mysql nevie ulozit priamo timezone
				],
				'{timestamp-default-now}' => [
					'pgsql' => 'timestamp with time zone default now()',
					'sqlite' => "timestamp default '0000-00-00 00:00:00'", // sqlite v skutocnosti pouzije text
					'mysql' => 'datetime default CURRENT_TIMESTAMP', // mysql nevie ulozit priamo timezone
				],
				'{bool}' => [
					'pgsql' => 'smallint', // 2 bytes
					'sqlite' => 'int',
					'mysql' => 'tinyint unsigned', // 1 byte
				],
				'{tinyint}' => [
					'pgsql' => 'smallint', // 2 bytes
					'sqlite' => 'int',
					'mysql' => 'tinyint', // 1 byte
				],
				'{mediumtext}' => [
					'pgsql' => 'text',
					'sqlite' => 'text',
					'mysql' => 'mediumtext',
				],
				'{longtext}' => [
					'pgsql' => 'text',
					'sqlite' => 'text',
					'mysql' => 'longtext',
				],
				'{blob}' => [
					'pgsql' => 'bytea',
					'sqlite' => 'blob',
					'mysql' => 'blob',
				],
				'{longblob}' => [
					'pgsql' => 'bytea',
					'sqlite' => 'blob',
					'mysql' => 'longblob',
				],
				'{engine-innodb}' => [
					'pgsql' => '',
					'sqlite' => '',
					'mysql' => ' ENGINE=InnoDb ',
				],
				'{engine-myisam}' => [
					'pgsql' => '',
					'sqlite' => '',
					'mysql' => ' ENGINE=MyISAM ',
				],
				'{drop-table-cascade}' => [
					'pgsql' => 'cascade',
					'sqlite' => '', // sqlite nepodporuje cascade
					'mysql' => 'cascade',
				],
				'{default-charset-utf8}' => [
					'pgsql' => '',
					'sqlite' => '',
					'mysql' => ' DEFAULT CHARSET=utf8 ',
				],
				'{charset-utf8-per-col}' => [
					'pgsql' => '',
					'sqlite' => '',
					'mysql' => ' CHARACTER SET utf8 ',
				],
				'{collation-utf8-bin}' => [
					'pgsql' => '',
					'sqlite' => '',
					'mysql' => ' COLLATE=utf8_bin ',
				],
				'{collation-utf8-bin-per-col}' => [
					'pgsql' => '',
					'sqlite' => '',
					'mysql' => ' COLLATE utf8_bin ',
				],
				'{zero-timestamp}' => [
					'pgsql' => '1970-01-01 00:00:00+00',
					'sqlite' => '0000-00-00 00:00:00',
					'mysql' => '0000-00-00 00:00:00',
				],
				'{q}' => [
					'pgsql' => '"',
					'sqlite' => '"',
					'mysql' => '`',
				],
				'{xml}' => [
					'pgsql' => 'xml',
					'sqlite' => 'text',
					'mysql' => 'text',
				],

				// todo podla potreby
			],
			$custom
		);
	}

	/**
	 * @param $sql
	 * @param string $vendor
	 * @param array $map
	 * @return mixed
	 */
	public static function getVendorSql($sql, $vendor = 'pgsql', array $map = null)
	{
		if (empty($map)) {
			$map = self::getSqlReplaceMap();
		}

		$searchReplace = [];
		foreach ($map as $needle => $_replace) {
			// teoreticky nemusim pre dany needle a adapter mat replace...
			// vtedy sa jednoducho dany needle kluc bude ignorovat
			if (isset($_replace[$vendor])) {
				$searchReplace[$needle] = $_replace[$vendor];
			}
		}

		$out = str_replace(
			array_keys($searchReplace),
			array_values($searchReplace),
			$sql
		);

		// Sqlite hacks... toto cele je trosku problematicke, ze sa snazime
		// byt tak trosku schizofrenici a riesit paralene sqlite a mysql...
		// Sqlite ma vsak obrovsku vyhody ze vie trivialne bezat in memory, a tym
		// padom je priam stvorena na rychle testovanie...
		// Nicmenej, najskor dojde k situaciam, kde jednoducho sqlite bude musiet
		// byt pri niektorych testoch skipnuta
		//         if (preg_match("/sqlite|pgsql/", $vendor)) {
		//             // NEDOKONALY special case hack na enum fix pre sqlite
		//             // skusi "enum ..." definciu nahradit za "text"
		//             //$rgx = '/( enum\s*[^\)]+\s*\))\s*(,|})/i';
		//             // update: doplname aj not null
		//             //$rgx = '/( enum\s*[^\)]+\s*\))\s*((not)?\s+null)?\s*(,|})/i';
		//             //$rgx = '/( enum\s*[^\)]+\s*\))\s*((not)?\s+null)?\s*(,|})/i';
		//             $rgx = '/( enum\s*[^\)]+\s*\))([^,}]*)(,|})/i';
		// //            if (preg_match($rgx, $out, $m)) {
		// //                print_r($m);
		// //                exit;
		// //            }
		//             $out = preg_replace($rgx, ' text$2$3', $out);
		//             //die($out);
		//         }

		return $out;
	}
}
