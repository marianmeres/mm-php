<?php
/**
 * @author Marian Meres
 */
namespace MM\Session\SaveHandler;

use MM\Session\Exception;

/**
 * Pridava featuru dodatocneho unique stlpca per session
 */
class DbTableUniqueAddon extends DbTable
{
    /**
     * Nazov stlpca, ktory bude porovanvany na dodatocne unique checky
     * @var string
     */
    public $uniqueName = 'user_id';

    /**
     * Hodnota unique addonu
     * @var mixed
     */
    public $uniqueValue;

    /**
     * Napriek tomu, ze vyssie su verejne, davam to aj ako "recommender" setter
     * (ako sucast api), nech to zakrici, ak by sa na to malo niekde zabudunut
     * pri zmene save-handlera.
     *
     * @param $value
     * @param null $name
     * @return $this
     */
    public function setUnique($value, $name = null)
    {
        $this->uniqueValue = $value;
        if ($name) {
            $this->uniqueName = $name;
        }
        return $this;
    }

    /**
     * na rychlu ukazku - nevola sa nikde
     *
     * @return string
     */
    public static function getDefaultSql()
    {
        $d = self::$tableDefinition;
        $sql = parent::getDefaultSql();

        $self = new self;
        $uniqueName = $self->uniqueName;

        $sql .= "
ALTER TABLE $d[table_name] ADD COLUMN $uniqueName INT;
CREATE UNIQUE INDEX $d[table_name]_unique ON $d[table_name] ($uniqueName);
";
        return $sql;
    }

    /**
     * @param $sessId
     * @param array $row
     * @return bool
     * @throws Exception
     */
    protected function _postRead($sessId, array &$row)
    {
        // radsej kricime, aby sme hned videli problem
        if (!array_key_exists($this->uniqueName, $row)) {
            throw new Exception("Key '" . $this->uniqueName . "' not found");
        }

        $this->uniqueValue = $row[$this->uniqueName];

        return true;
    }

    /**
     * override with unique addon
     *
     * @param string $sessId
     * @param string $data
     * @return bool|mixed
     */
    public function write($sessId, $data)
    {
        $d = self::$tableDefinition;
        $where = array($d['column_id'] => $sessId);
        $data  = array(
            $d['column_data'] => (string) $data,
        );

        if (!$this->_preWrite($sessId, $data)) {
            return false;
        }

        $data[$this->uniqueName] = $this->uniqueValue;

        // insert vs update?
        $row = $this->_dbu->fetchRow(
            "$d[column_lifetime],$this->uniqueName", $d['table_name'], $where
        );

        // mozno bude treba mazat podla uniqueValue, kvoli unique indexu...
        // (pre citatelnost vnaram if-y)
        if (null !== $this->uniqueValue) {

            if (// ak existuje zaznam podla sid a ma rozdielny pid...
                ($row && $row[$this->uniqueName] != $this->uniqueValue)
                // alebo ak neexistuje vobec (v tabulke moze vysiet pid pre stare sid)
                // tento case moze byt legitimne 0 affected
                || !$row
            ) {
                $this->_dbu->delete($d['table_name'], array(
                    $this->uniqueName => $this->uniqueValue
                ));
            }

            // warning
            if ($row
                && $row[$this->uniqueName] != $this->uniqueValue
                && $this->logger instanceof \Closure) {
                // tato situacia (prepis unique hodnoty) je technicky validna
                // ale aplikacne by k tomu najskor nemalo dochadzat myslim...
                call_user_func_array($this->logger, [sprintf(
                    "Warning: potential (security) error - '%s' unique value '%s' is being overridden to '%s'",
                    $this->uniqueName, $row[$this->uniqueName], $this->uniqueValue
                )]);
            }

        }

        // insert
        if (empty($row)) {
            $data[$d['column_id']] = $sessId;

            // bud "custom", alebo php ini default
            $data[$d['column_lifetime']]
                = !empty($this->_lifetime)
                ? $this->_lifetime
                : ini_get("session.gc_maxlifetime");

            $data[$d['column_valid_until']] = time() + $data[$d['column_lifetime']];

            $result = (bool) $this->_dbu->insert($d['table_name'], $data);
        }
        // update
        else {
            // ak mame "custom" lifetime, tak ho pouzijeme, inak ponechame povodny
            $data[$d['column_lifetime']] = !empty($this->_lifetime)
                ? $this->_lifetime
                : $row[$d['column_lifetime']];

            $data[$d['column_valid_until']] = time() + $data[$d['column_lifetime']];

            $result = (bool) $this->_dbu->update($d['table_name'], $data, $where);
        }

        return $this->_postWrite($sessId, $data, $result);
    }

}