<?php
/**
 * @author Marian Meres
 */
namespace MM\Util;

/**
 * Class DbUtilPdo
 * @package MM\Util
 *
 * PDO helper. Features:
 * - unified api
 * - auto input escape (in typical scenarios)
 * - insert/update/delete api
 * - full raw sql support
 * - no joins support
 * - lazy JIT connect
 * - simple logger/profile
 * - tested for mysql, sqlite, pgsql
 *
 */
class DbUtilPdo
{
    const LOG_IDX_QUERY    = 0;
    const LOG_IDX_START    = 1;
    const LOG_IDX_EXTRA    = 2;
    const LOG_IDX_DURATION = 3;
    const LOG_IDX_LABEL    = 4;

    /**
     * Na debug...
     * @var string|null
     */
    public $logLabel;

    /**
     * Interny resource
     * @var \PDO
     */
    protected $_resource;

    /**
     * Optiony na connect ak nexistuje resource
     * @var array
     */
    protected $_options = array();

    /**
     * Pomocny flag... bude pouzity, len ak neexistuje nativna pdo (od php 5.3.3)
     * @var boolean
     */
    protected $_inTransaction = false;

    /**
     * Primitivny logger quericiek poslanych db enginu. Ak null, tak deaktivovany.
     * Pochopitelne loguje len to, co sa posiela cez tento helper (neloguje na
     * urovni konekcie). Defaultne neaktivny.
     *
     * @var array|null
     */
    protected $_queryLog;

    /**
     * Podobne ako vyssii $_queryLog, akurat neloguje samotne statementy, ale
     * iba suchy pocet queries... kedze toto nerobi ziaden overhead, robi to vzdy
     * a aktualne sa to neda vypnut (akurat resetnut)
     *
     * @var int
     */
    protected $_queryLogCounter = 0;

    /**
     * @var \Closure|null
     */
    public $logger;

    /**
     * Toto je taky trosku hack kvoli sqlite ale moze to mat vyuzitie aj inde
     * Davam to ako public aby sa to dalo lahko editovat
     *
     * @var array
     */
    public $autoInitCommands = array(
        'sqlite' => array(
            'PRAGMA foreign_keys = ON'
        ),
    );

    /**
     * @param mixed $optionsOrResource
     */
    public function __construct($optionsOrResource = null)
    {
        if ($optionsOrResource instanceof \PDO) {
            return $this->setResource($optionsOrResource);
        }

        if (is_array($optionsOrResource)) {
            $this->_options = $optionsOrResource;
        }

        return $this;
    }

    /**
     * De/Aktivuje logovanie quericiek.
     * @param  boolean $flag
     * @return bool
     */
    public function activateQueryLog($flag = true)
    {
        $oldValue = $this->_queryLog;
        $this->_queryLog = $flag ? (array) $this->_queryLog : null;

        // vratime bool ci bol prechadzajucu hodnotu
        return is_array($oldValue);

        //return $this;
    }

    /**
     * @return bool
     */
    public function isQueryLogActive()
    {
        return is_array($this->_queryLog);
    }

    /**
     * @return DbUtilPdo
     */
    public function resetQueryLog()
    {
        // resetneme na cisty array iba ak je array, null ma specialny vyznam
        // (deaktivovane logovanie)
        if (is_array($this->_queryLog)) {
            $this->_queryLog = array();
        }
        return $this;
    }

    /**
     * @return array|null
     */
    public function getQueryLog()
    {
        return $this->_queryLog;
    }

    /**
     * @return int
     */
    public function getQueryLogCounter()
    {
        return $this->_queryLogCounter;
    }

    /**
     * @return $this
     */
    public function resetQueryLogCounter()
    {
        $this->_queryLogCounter = 0;
        return $this;
    }

    /**
     * Interny logger.
     * @param  string $sql
     * @param null $extra
     * @return DbUtilPdo
     */
    protected function _log($sql, $extra = null)
    {
        // toto robime vzdy
        $this->_queryLogCounter++;

        // toto iba ak je array
        if (is_array($this->_queryLog)) {

            // Krasotina: urezeme prilis dlhe stringy v logu...
            if (is_array($extra)) {
                array_walk($extra, function (&$value) {
                    if (strlen($value) > 32) {
                        $value = substr($value, 0, 32) . "...";
                    }
                });
            }

            $this->_queryLog[] = array(
                self::LOG_IDX_QUERY => $sql,
                self::LOG_IDX_START => microtime(true),
                self::LOG_IDX_EXTRA => $extra,
                self::LOG_IDX_DURATION => null,
                self::LOG_IDX_LABEL => $this->logLabel,
            );
        }

        // iny sposob logovania - via externy callback logger
        if (is_callable($this->logger)) {
            // call_user_func_array(self::$logger, array($sql, $extra));
            call_user_func_array($this->logger, array($sql, $extra));
        }

        return $this;
    }

    /**
     * Zapise trvanie v milisekundach do posledneho log zaznamu.
     */
    protected function _logUpdateLastQueryDuration()
    {
        if (is_array($this->_queryLog) && !empty($this->_queryLog)) {
            // asi by sa dalo ist cez current(), co ale moze byt najskor
            // problematicke po prechadzani logu... neviem ale naisto
            $currentIdx = max(0, count($this->_queryLog) - 1);
            $current =& $this->_queryLog[$currentIdx];
            if (!isset($current[self::LOG_IDX_DURATION])) {
                $current[self::LOG_IDX_DURATION] = round(
                    (microtime(true) - $current[self::LOG_IDX_START]) * 1000
                );
            }
        }
    }

    /**
     * Vrati driver connection resource
     * @return \PDO
     */
    public function getResource()
    {
        return $this->connect()->_resource;
    }

    /**
     * @return boolean
     */
    public function isConnected()
    {
        return (bool) $this->_resource;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->_options;
    }

    /**
     * Setne priamo resource konekcie. Sikovne ak treba tejto utilitke supnut
     * uz existujucu konnekciu odinokadial. Konkretny class musi zistit,
     * ci dany resource je pre neho validny.
     *
     * @param \PDO|null $pdo
     * @throws \InvalidArgumentException
     * @return DbUtilPdo
     */
    public function setResource($pdo = null)
    {
        // ak null tak reset a return early
        if (null === $pdo) {
            $this->_resource = null;
            return $this;
        }

        if (!$pdo instanceof \PDO) {
            throw new \InvalidArgumentException("Invalid resource");
        }

        // pre istotu setupneme... ale aj tak sa na to neda uplne spolahnut...
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

        // custom auto init commands if any... defenzivne a via surove PDO tu
        // musime ist... priklad:
        // $db = new DbUtilPdo();
        // unset($db->autoInitCommands['sqlite']);
        // $db->setResource(new \PDO("sqlite::memory:"))
        // $this->assertEquals(0, $db->fetchOneSql("PRAGMA foreign_keys;"));
        $name = strtolower($pdo->getAttribute(\PDO::ATTR_DRIVER_NAME));
        if (!empty($this->autoInitCommands[$name])) {
            foreach ((array) $this->autoInitCommands[$name] as $command) {
                $this->_log($command);
                $pdo->exec($command);
                $this->_logUpdateLastQueryDuration();
            }
        }

        $this->_resource = $pdo;

        return $this;
    }

    /**
     * Skusi vytvorit pdo na zaklade optionov. Na optiony pouzivame zf2
     * format, aby sme boli prenostitelni, t.j.:
     * array(
     *   'driver'   => sqlite|mysql|pgsql
     *   'database' => nazov db|pre sqlite: cesta k db, alebo :memory:
     *   // nizsie su ignorovane ak je driver sqlite
     *   'hostname' => ...,
     *   'port'     => ...,
     *   'username' => ...,
     *   'password' => ...,
     * )
     *
     * Umyselne ako handy staticka utilitka
     *
     * @param  array $options
     * @param  bool $debug
     * @throws \RuntimeException
     * @return \PDO
     */
    public static function factoryResource(array $options, $debug = false)
    {
        $o   = $options;
        $dsn = "";

        if (!empty($o['driver'])
            && preg_match('/^(pdo_)?(mysql|pgsql|sqlite)$/i', $o['driver'], $m)
            ) {
            $dsn .= strtolower($m[2]) . ":";
        } else {
            throw new \RuntimeException("Driver option not set or not recognized");
        }

        if (empty($o['database'])) {
            throw new \RuntimeException("Missing 'database' option");
        }

        // sqlite je simple, tam staci malo
        if (preg_match('/sqlite/i', $o['driver'])) {
            $dsn .= $o['database'];
            if ($debug) {
                return array('dsn' => $dsn);
            }
            return new \PDO($dsn);
        }

        // ostatne potrebuju minimalne aj hostname
        if (empty($o['hostname'])) {
            throw new \RuntimeException("Missing 'hostname' option");
        }

        $dsn .= "host=" . strtolower($o['hostname'])
              . ";dbname=" . $o['database'];

        if (!empty($o['port'])) {
            $dsn .= ";port=" . $o['port'];
        }

        $username = isset($o['username']) ? $o['username'] : null;
        $password = isset($o['password']) ? $o['password'] : null;

        // unsetneme vsetko zname, a zvysok posleme ako driver specific optiony
        $base = array('driver', 'hostname', 'database', 'port', 'username', 'password');
        foreach ($base as $k) {
            unset($o[$k]);
        }

        // test hack
        if ($debug) {
            return array('dsn' => $dsn, 'username' => $username,
                'password' => $password,'driver_options' => $o,
            );
        }

        return new \PDO($dsn, $username, $password, $o);
    }

    /**
     * Realne skusi connectnut db server;
     * @throws \RuntimeException
     * @return DbUtilPdo
     */
    public function connect()
    {
        if ($this->_resource) {
            return $this;
        }

        if (empty($this->_options)) {
            throw new \RuntimeException(
                "Unable to connect. Neither resource nor options are available."
            );
        }

        $pdo = self::factoryResource($this->_options);
        $this->setResource($pdo);

        return $this;
    }

    /**
     * Znici resource ak ho ma
     * @return DbUtilPdo
     */
    public function disconnect()
    {
        // $this->_options = array();
        return $this->setResource(null);
    }

    /**
     * Vyrobi a vrati prepared statement
     * @param  string $sql
     * @return \PDOStatement
     */
    public function prepare($sql)
    {
        return $this->getResource()->prepare($sql);
    }

    /**
     * Vykona dany surovy $sql alebo prepared statement
     * @param $rawSqlOrPreparedStatement
     * @param array $data
     * @return bool|int
     */
    public function execute($rawSqlOrPreparedStatement, array $data = array())
    {
        $db = $this->getResource();
        if ($rawSqlOrPreparedStatement instanceof \PDOStatement) {
            $stmt = $rawSqlOrPreparedStatement;
            $this->_log($stmt->queryString, $data);
            $out = $stmt->execute($data); // true/false
        } else {
            $sql = $rawSqlOrPreparedStatement;
            $this->_log($sql);
            $out = $db->exec($sql); // affected rows
        }

        $this->_logUpdateLastQueryDuration();

        return $out;
    }

    /**
     * Vysklada a vykona query a vrati PDOStatement
     * @param $fields
     * @param $table
     * @param null $where
     * @param array $addons
     * @return \PDOStatement
     */
    public function query($fields, $table, $where = null, array $addons = null)
    {
        return $this->querySql(
            "SELECT $fields FROM " . $this->qi($table), $where, $addons
        );
    }

    /**
     * Vykona surovu query a vrati PDOStatement
     * @param $sql
     * @param null $where
     * @param array $addons
     * @return \PDOStatement
     */
    public function querySql($sql, $where = null, array $addons = null)
    {
        $db = $this->getResource();

        // if (!empty($where)) {
        if ("" != ($where = trim($this->buildSqlWhere($where)))) {
            // $sql .= " WHERE " . ltrim($this->buildSqlWhere($where));
            $sql .= " WHERE $where";
        }

        if (!empty($addons)) {
            $sql .= $this->buildSqlAddons($addons);
        }

        if (!empty($addons['debug'])) {
            // hack-debug-magic-string-included
            switch ($addons['debug']) {
                case 'die':
                    die($sql);
                case 'echo':
                    echo "\n$sql";
                    break;
                default:
                    return $sql;
            }
            // return ('die' === $addons['debug']) ? die($sql) : $sql;
        }

        $this->_log($sql);

        // Executes an SQL statement, returning a result set as a PDOStatement
        // object
        $out = $db->query($sql, \PDO::FETCH_ASSOC);

        $this->_logUpdateLastQueryDuration();

        return $out;
    }

    /**
     * Vrati vsetky riadky so vsetkymi stlpcami
     * @param  string $fields
     * @param  string $table
     * @param  mixed $where
     * @param  mixed $addons
     * @return array
     */
    public function fetchAll($fields, $table, $where = null, array $addons = null)
    {
        $stmt = $this->query($fields, $table, $where, $addons);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Vrati vsetko pre (polo) surove sql
     * @param  string $sql
     * @param  mixed $where
     * @param  array $addons
     * @return array
     */
    public function fetchAllSql($sql, $where = null, array $addons = null)
    {
        $stmt = $this->querySql($sql, $where, $addons);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Vrati prvy riadok z resultsetu ako assoc pole
     *
     * @param  string $fields
     * @param  string $table
     * @param  mixed $where
     * @param  array $addons
     * @return array|null
     */
    public function fetchRow($fields, $table, $where = null, array $addons = null)
    {
        $stmt = $this->query($fields, $table, $where, array_merge(
            (array) $addons, array('limit' => 1)
        ));
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        // za istych okolnosti toto je dost podstatne... aj ked tu najskor nie,
        // lebo sme vyssie limitovali na 1, ale nicomu to neublizi
        $stmt->closeCursor();

        return $row ?: null;
    }

    /**
     * fetch row pre surove sql
     *
     * @param  string $sql
     * @param  mixed $where
     * @param  array $addons
     * @return array|null
     */
    public function fetchRowSql($sql, $where = null, array $addons = null)
    {
        $stmt = $this->querySql($sql, $where, $addons);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        return $row ?: null;
    }

    /**
     * Vrati pole hodnot prveho stlpca (0-ty index) zo vsetkych riadkov
     * v resultsete
     *
     * @param  string $fields
     * @param  string $table
     * @param  mixed $where
     * @param  array $addons
     * @return array|null
     */
    public function fetchCol($fields, $table, $where = null, array $addons = null)
    {
        $stmt = $this->query($fields, $table, $where, $addons);
        $out = $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);
        return $out ?: null;
    }

    /**
     * fetch col pre surove sql
     *
     * @param  string $sql
     * @param  mixed $where
     * @param  array $addons
     * @return array|null
     */
    public function fetchColSql($sql, $where = null, array $addons = null)
    {
        $stmt = $this->querySql($sql, $where, $addons);
        $out = $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);
        return $out ?: null;
    }

    /**
     * Vrati hodnotu prveho stlpca v prvom riadku z resulsetu
     *
     * @param  string $fields
     * @param  string $table
     * @param  mixed $where
     * @param  array $addons
     * @return string|null
     */
    public function fetchOne($fields, $table, $where = null, array $addons = null)
    {
        $addons = (array) $addons;
        if (!array_key_exists('limit', $addons)) { // defaultne pridame limit 1
            $addons['limit'] = 1;
        }

        $stmt = $this->query($fields, $table, $where, $addons);
        $col = $stmt->fetchColumn(0);
        $stmt->closeCursor();
        return $col !== false ? $col : null;
    }

    /**
     * fetch one pre surove sql
     *
     * @param  string $sql
     * @param  mixed $where
     * @param  array $addons
     * @return string|null
     */
    public function fetchOneSql($sql, $where = null, array $addons = null)
    {
        $addons = (array) $addons;
        if (!array_key_exists('limit', $addons)) { // defaultne pridame limit 1
            $addons['limit'] = 1;
        }

        $stmt = $this->querySql($sql, $where, $addons);
        $col = $stmt->fetchColumn(0);
        $stmt->closeCursor();
        return $col !== false ? $col : null;
    }

    /**
     * Vrati pocet zaznamov z $table podla $where
     *
     * @param  string  $table
     * @param  mixed  $where
     * @param  boolean $debug
     * @return int
     */
    public function fetchCount($table, $where = null, $debug = false)
    {
        return (int) $this->fetchOne("COUNT(*) AS count", $table, $where,
            array('debug' => $debug)
        );
    }

    /**
     * @param $keyCol
     * @param $valCol
     * @param $table
     * @param null $where
     * @param array $addons
     * @return array
     */
    public function fetchPairs($keyCol, $valCol, $table, $where = null,
                              array $addons = null)
    {
        $stmt = $this->query("$keyCol, $valCol", $table, $where, $addons);
        return $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
    }

    /**
     * @param $sql
     * @param null $where
     * @param array $addons
     * @return array
     */
    public function fetchPairsSql($sql, $where = null, array $addons = null)
    {
        $stmt = $this->querySql($sql, $where, $addons);
        return $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
    }


    /**
     * Insertne $data do $table
     *
     * @param  string  $table
     * @param  array   $data
     * @param  boolean $debug
     * @return int
     */
    public function insert($table, array $data, $debug = false)
    {
        $db = $this->getResource();

        if (empty($data)) {
            return false;
        }

        $columns = $values = array();
        foreach ($data as $column => $value) {

            // ne neescapovanie podporujeme operator "="
            $sign = $this->_getOptionalSignFromColNotation($column, false);

            if (null === $value) {
                $value = 'NULL';
            }
            else if (" = " != $sign) {
                $value = $this->qv($value);
            }

            $columns[] = $this->qi($column);
            $values[]  = $value;
            // $values[]  = null === $v ? 'NULL' : $this->qv($v);
        }

        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $this->qi($table), implode(", ", $columns), implode(", ", $values)
        );

        if ($debug) {
            // hack-debug-magic-string-included
            return ('die' === $debug) ? die($sql) : $sql;
        }

        $this->_log($sql);

        // returns the number of rows that were modified or deleted by the SQL
        // statement you issued. If no rows were affected, returns 0.
        $out = $db->exec($sql);

        $this->_logUpdateLastQueryDuration();

        return $out;
    }

    /**
     * @param  string $name
     * @return int
     */
    public function lastInsertId($name = null)
    {
        $db = $this->getResource();

        // rychly hack pre postgres... POZOR: mozu nastat situacie kedy toto
        // nebude fungovat (ak napr. insert trigerne ine inserty inde...)
        // a navyse toto hodi, ak este v sessione nebol pouzity insert
        if ('pgsql' == $this->getDriverName()) {
            $sql = $name ? "SELECT CURRVAL('$name')" : "SELECT LASTVAL()";
            $this->_log($sql);
            $stmt = $db->query($sql);
            $out = $stmt->fetchColumn(0);
            $this->_logUpdateLastQueryDuration();
            return $out;
        }

        // kedze ak lastInsertId je realny db server dotaz, tak si to logneme
        $this->_log("-- PDO::lastInsertId()");

        // pre sqlite a mysql by toto malo byt ok
        $out = $db->lastInsertId($name);

        $this->_logUpdateLastQueryDuration();

        return $out;
    }

    /**
     * Updadne $data v $table podla $where
     *
     * @param  string  $table
     * @param  array   $data
     * @param  mixed  $where
     * @param  boolean $debug
     * @return int      affected rows
     */
    public function update($table, array $data, $where, $debug = false)
    {
        $db = $this->getResource();

        if (empty($data)) {
            return false;
        }

        // return false, ak posielame empty where (false, prazdne pole..)
        // mimo toho, ze to velmi nedava zmysel, chceme tu by radsej prisni
        if (empty($where)) {
            return 0;
        }

        // boolean special case pohodlie... lebo postgres pinda:
        // Datatype mismatch: 7 ERROR:  argument of WHERE must be type boolean, not type integer
        if (true === $where) {
            $where = "1=1";
        }

        $sql = sprintf("UPDATE %s SET", $this->qi($table));

        foreach ($data as $column => $value) {

            // ne neescapovanie podporujeme operator "="
            $sign = $this->_getOptionalSignFromColNotation($column, false);

            if (null === $value) {
                $value = 'NULL';
            }
            else if (" = " != $sign) {
                $value = $this->qv($value);
            }

            $sql .= sprintf(" %s = %s,", $this->qi($column), $value);
        }

        // $sql = rtrim($sql, ",") . " WHERE " . ltrim($this->buildSqlWhere($where));
        $sql = rtrim($sql, ",");
        // if ("" != ($where = trim($this->buildSqlWhere($where)))) {
        //     $sql .= " WHERE $where";
        // }
        $sql .= " WHERE " . ltrim($this->buildSqlWhere($where));

        if ($debug) {
            // hack-debug-magic-string-included
            return ('die' === $debug) ? die($sql) : $sql;
        }

        $this->_log($sql);

        // toto by malo vratit affected rows count
        $out = $db->exec($sql);

        $this->_logUpdateLastQueryDuration();

        return $out;
    }

    /**
     * Zmaze z $table podla $where
     *
     * Tu umyselne where davam ako povinny argument do signatury, aby sa omylom
     * vsetko nezmazalo. Nicmenej where moze byt null (co skonci ako noop)
     *
     * @param $table
     * @param $where
     * @param array $addons
     * @param bool $debug
     * @return int|string|void
     */
    public function delete($table, $where, array $addons = null, $debug = false)
    {
        $db = $this->getResource();

        // return false, ak posielame empty where (false, prazdne pole..)
        // mimo toho, ze to velmi nedava zmysel, chceme tu by radsej prisni
        if (empty($where)) {
            return 0;
        }

        // boolean special case pohodlie... lebo postgres pinda:
        // Datatype mismatch: 7 ERROR:  argument of WHERE must be type boolean, not type integer
        if (true === $where) {
            $where = "1=1";
        }

        $sql = "DELETE FROM " . $this->qi($table);
//        if (!empty($where)) { // tu uz always true
//            $sql .= " WHERE " . ltrim($this->buildSqlWhere($where));
//        }
        $sql .= " WHERE " . ltrim($this->buildSqlWhere($where));

        // pri delete dava zmysel akurat tak limit
        if (!empty($addons)) {
            $sql .= $this->buildSqlAddons($addons);
        }

        if ($debug) {
            // hack-debug-magic-string-included
            return ('die' === $debug) ? die($sql) : $sql;
        }

        $this->_log($sql);

        $out = $db->exec($sql);

        $this->_logUpdateLastQueryDuration();

        return $out;
    }

    /**
     * Quotne identifikator (nazov stlpca/tabulky) aby bol safe v sql
     *
     * @param  string $identifier
     * @return string
     */
    public function qi($identifier)
    {
        // return early ak $identifier obsahuje "." to nechceme quotovat
        if (false !== strpos($identifier, ".")) {
            return $identifier;
        }

        // mysql pouziva backtick "`"
        if ('mysql' == $this->getDriverName()) {
            return '`' . str_replace('`', '``', $identifier) . '`';
        }

        // '"' by malo byt safe pre vsetko ostatne
        return '"' . str_replace('"', '\\' . '"', $identifier) . '"';
    }

    /**
     * Quotne hodnotu aby bola safe v sql
     *
     * @param $value
     * @param int $valueTypeHint
     * @return string
     */
    public function qv($value, $valueTypeHint = \PDO::PARAM_STR)
    {
        // Scalar variables are: integer, float, string or boolean
        if (is_scalar($value)) {
            return $this->getResource()->quote($value, $valueTypeHint);
        }

        // FEATURE: callbacky neescapujeme... tam je idea, ze mame veci pod
        // kontrolou
        if ($value instanceof \Closure) {
            return $value($this, $valueTypeHint); // posielame sameho seba ako parameter
        }

        // tu ako fallback predpokladame objekt, ktore vie castovat na string
        return $this->getResource()->quote("$value", $valueTypeHint);
    }

    /**
     * Skusi zistit ci sme vo vnutri transakcie (fallbackuje na nativnu PDO
     * funkcionalitu, ak je k dispozicii). Vlastna detekcia bude fungovat,
     * len ak bola transakcia zacata via tento helper...
     *
     * @return bool
     */
    public function inTransaction()
    {
        $db = $this->getResource();

        if (method_exists($db, 'inTransaction')) {
            $this->_inTransaction = $db->inTransaction();
        }

        return $this->_inTransaction;
    }

    /**
     * Zacne transakciu
     *
     * @param  boolean $strict
     * @return DbUtilPdo
     */
    public function begin($strict = true)
    {
        $db = $this->getResource();

        if ($strict || !$this->inTransaction()) {
            $this->_log("begin;");
            $db->beginTransaction();
            $this->_logUpdateLastQueryDuration();
        }
        // $this->getResource()->beginTransaction();

        $this->_inTransaction = true;
        return $this;
    }

    /**
     * Komitne. Ak $strict je false, tak realne posle serveru prikaz na komit
     * iba ak interny flag hovori ze sme v transakcii, inak ticho ignoruje.
     *
     * @param  boolean $strict
     * @return DbUtilPdo
     */
    public function commit($strict = true)
    {
        $db = $this->getResource();

        if ($strict || $this->inTransaction()) {
            $this->_log("commit;");
            $db->commit();
            $this->_logUpdateLastQueryDuration();
        }

        $this->_inTransaction = false;
        return $this;
    }

    /**
     * Rollbackne. Ak $strict je false, tak realne posle serveru prikaz na rollback
     * iba ak interny flag hovori ze sme v transakcii, inak ticho ignoruje.
     *
     * @param  boolean $strict
     * @return DbUtilPdo
     */
    public function rollback($strict = true)
    {
        $db = $this->getResource();

        if ($strict || $this->inTransaction()) {
            $this->_log("rollback;");
            $db->rollBack();
            $this->_logUpdateLastQueryDuration();
        }

        $this->_inTransaction = false;
        return $this;
    }

    /**
     * Interna magia - do nazvu stlpca je mozne zakodovat znamienko porovnania:
     * "col!", "col<", "col>", "col<>", "col>=", "col<=" a ine...
     *
     * Pozor: ak najde match, tak ho z nazvu vyreze ($col je referencovany)
     *
     * @param  string  $col
     * @param  boolean $forceNOT
     * @return string
     */
    protected static function _getOptionalSignFromColNotation(&$col, $forceNOT = false)
    {
        $sign = '';

        // match na 2 posledne znaky
        if (preg_match("/^(!=|<>|<=|>=|!~)$/", substr($col, -2), $m)) {
            $sign = $m[1] == '!=' ? " <> " : " $m[1] "; // normalize to <>
            if (" !~ " == $sign) {
                $sign = " NOT LIKE ";
            }
            $col = substr($col, 0, -2);
        }

        // match na 1 posledny znak
        else if (preg_match("/^(!|<|>|=|~)$/", substr($col, -1), $m)) {
            $sign = $m[1] == '!' ? " <> " : " $m[1] ";
            if (" ~ " == $sign) {
                $sign = " LIKE ";
            }
            $col = substr($col, 0, -1);
        }

        // ak forcujeme NOT, tak bud NOT alebo nic (ostatne ignorujeme)
        if ($forceNOT) {
            return " <> " == $sign ? " NOT " : " ";
        }

        return $sign;
    }

    /**
     * Toto davam aj ako public - prax ukazuje, ze sa to hodi... ale chova sa to
     * trosku rozdielne - nemodifikuje to priamo $col a defaultne vracia "="
     * a nie empty string
     *
     * @param $col
     * @return array
     */
    public static function getSignFromColNotation($col)
    {
        $pseudoClone = $col . "";
        $sign = trim(
            self::_getOptionalSignFromColNotation($pseudoClone, false)
        );
        return array(
            'column' => $pseudoClone, 'sign' => $sign ?: '='
        );
    }

    /**
     * Toto vysklada z where "AND $k = $v" podmienku... s tym, ze pozna rozne
     * notacie a aj nejake carovne stringy... pridana hodnota oproti rucnemu
     * vyskladaniu je automaticke quotovanie (ak $where je pole)... mozne pouzitie:
     *
     * $where                      ---> $sql
     * ------------------------------------------------------
     * "id = 1"                    ---> id = 1
     * array("id" => 1)            ---> id = '1'
     * array("id" => array(1,2))   ---> id IN ('1','2')
     * array("id!" => 1)           ---> id <> '1'
     * array("id<" => 1)           ---> id < '1'
     * array("id<=" => 1)          ---> id <= '1'
     * array("id!" => array(1,2))  ---> id NOT IN ('1','2')
     * array("id~" => 'abc')       ---> id LIKE 'abc'
     * array("id!~" => 'abc')      ---> id NOT LIKE 'abc'
     * array("=" => "nedotknute")  ---> nedotknute
     *
     * @param  mixed $where
     * @param  string $operator
     * @return string
     */
    public function buildSqlWhere($where = null, $operator = "AND")
    {
        $sql = "";

        if (is_array($where)) {
            foreach ($where as $col => $val) {

                // ak je $col rovny "=" vtedy nic neescapuje ani nevyskladava...
                // taky mini hack ak potrebujem dat nejake special veci (ci uz
                // nejaku expressionu alebo vnorenu "OR" podmienku atd...)
                // ale stale chcem vyuzit $where (vzdy mozem sql napisat rucne)
                if ("=" == $col) {
                    $sql .= "$val "; // tu sa nepouzije ani operator
                }

                // trosku podobna functionalita ako vyssie (tu sa ale pouzije
                // $col), akurat ina notacia
                // else if ($val instanceof \Closure) {
                //     $sign = $this->_getOptionalSignFromColNotation($col);
                //     $sql .= sprintf(
                //         "$operator %s %s %s ", $this->qi($col), $sign, $val()
                //     );
                // }

                // ak je hodnota array, tak vyskladame (NOT)? IN (...)
                else if (is_array($val)) {
                    foreach ($val as $k => $v) {
                        $val[$k] = $this->qv($v);
                    }
                    $sign = $this->_getOptionalSignFromColNotation($col, true);
                    $sql .= sprintf("$operator %s%sIN (%s) ",
                        $this->qi($col), $sign, implode(",", $val)
                    );
                }

                // nakoniec normalne "k znamienko v"
                else {
                    if (null === $val) {
                        $sign = $this->_getOptionalSignFromColNotation($col, true);
                        $val  = " IS{$sign}NULL";
                    } else {
                        $sign = $this->_getOptionalSignFromColNotation($col);
                        if ("" == $sign) {
                            $sign = " = ";
                        }
                        $val = $sign . $this->qv($val);
                    }

                    $sql .= sprintf(
                        "$operator %s%s ", $this->qi($col), $val
                    );
                }
            }
        } else if ("" != trim($where)) {
            $sql .= "$operator $where";
        }

        // odrezeme $operator zlava
        if (0 === strpos($sql, $operator)) {
            $sql = substr($sql, strlen($operator));
        }

        return " " . rtrim($sql);
    }

    /**
     * @param  array $addons
     * @return string
     */
    public function buildSqlAddons(array $addons = null)
    {
        $sql = " ";

        if (!empty($addons['group_by'])) {
            $sql .= sprintf("GROUP BY %s ", $addons['group_by']);
        }

        if (!empty($addons['order_by'])) {
            $sql .= "ORDER BY {$addons['order_by']} ";
        }

        if (!empty($addons['limit'])) {
            $sql .= sprintf("LIMIT %d ", $addons['limit']);
        }

        if (!empty($addons['offset'])) {
            $sql .= sprintf("OFFSET %d ", $addons['offset']);
        }

        return rtrim($sql);
    }

    /**
     * @return string
     */
    public function getDriverName()
    {
        return strtolower(
            $this->getResource()->getAttribute(\PDO::ATTR_DRIVER_NAME)
        );
    }

    /**
     * Sugar
     * @return bool
     */
    public function isSqlite()
    {
        return 'sqlite' == $this->getDriverName();
    }

    /**
     * Sugar
     * @return bool
     */
    public function isMysql()
    {
        return 'mysql' == $this->getDriverName();
    }

    /**
     * Sugar
     * @return bool
     */
    public function isPgsql()
    {
        return 'pgsql' == $this->getDriverName();
    }

    /**
     * @return array
     */
    public function getTables($details = false)
    {
        $out = [];

        if ($this->isSqlite()) {
            if ($details) {
                $out = $this->fetchAll("*", "sqlite_master", ['type' => 'table']);
            } else {
                $out = $this->fetchCol("name", "sqlite_master", ['type' => 'table']);
            }
        }

        elseif ($this->isPgsql()) {
            if ($details) {
                $out = $this->fetchAll("*", "pg_catalog.pg_tables", ['schemaname' => 'public']);
            } else {
                $out = $this->fetchCol("tablename", "pg_catalog.pg_tables", ['schemaname' => 'public']);
            }
        }

        elseif ($this->isMysql()) {
            if ($details) {
                $out = $this->fetchAllSql("show full tables");
            } else {
                $out = $this->fetchColSql("show tables");
            }
        }

        return $out;
    }

    /**
     * Vrati zakladne data o stlpcoch pre danu tabulku
     *
     * @param $tableName
     * @return array
     * @throws \Exception
     */
    public function getColumns($tableName)
    {
        $out = [];
        $tableName = $this->qv($tableName);

        if ($this->isSqlite()) {
            $rows = $this->fetchAllSql("PRAGMA table_info($tableName)");
            foreach($rows as $row) {
                $out[$row['name']] = [
                    'type' => $row['type'],
                ];
            }
        }
        elseif ($this->isMysql()) {
            $dbName = $this->fetchOneSql("select database()");
            $rows = $this->fetchAllSql(
                "select COLUMN_NAME, COLUMN_TYPE "
                . "FROM INFORMATION_SCHEMA.COLUMNS "
                . "WHERE TABLE_SCHEMA='$dbName' "
                . "AND TABLE_NAME=$tableName");
            foreach($rows as $row) {
                $out[$row['COLUMN_NAME']] = [
                    'type' => $row['COLUMN_TYPE'],
                ];
            }
        }
        elseif ($this->isPgsql()) {
            $rows = $this->fetchAllSql(
                "SELECT column_name, data_type "
                . "FROM information_schema.columns "
                . "WHERE table_schema='public' AND table_name=$tableName"
            );
            foreach($rows as $row) {
                $out[$row['column_name']] = [
                    'type' => $row['data_type'],
                ];
            }
        }
        else {
            $driver = $this->getDriverName();
            throw new \RuntimeException(
                "Driver '$driver' not supported in " . __METHOD__
            );
        }

        return $out;
    }
}
