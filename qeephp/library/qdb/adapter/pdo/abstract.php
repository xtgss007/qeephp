<?php
abstract class QDB_Adapter_Pdo_Abstract extends QDB_Adapter_Abstract {
    public function __construct($dsn, $id) {
        if (!is_array($dsn)) { $dsn = QDB::parseDSN($dsn); }

        parent::__construct($dsn, $id);
    }

    public function connect($pconnect = false, $force_new = false) {
        if (!$force_new && $this->isConnected()) { return; }

        $dsn = array();
        if (!empty($this->_dsn['database'])) { $dsn['dbname'] = $this->_dsn['database']; }
        if (!empty($this->_dsn['host'])) { $dsn['host'] = $this->_dsn['host']; }
        if (!empty($this->_dsn['port'])) { $dsn['port'] = $this->_dsn['port']; }

        $user = $this->_dsn['login'];
        $password = $this->_dsn['password'];

        $dsn_string = sprintf('%s:%s', $this->_pdo_type, http_build_query($dsn, '', ';'));

        try {
            $this->_conn = new PDO($dsn_string, $user, $password);
        } catch (PDOException $e) { throw $e; }
    }

    public function close() {
        parent::_clear();
    }

    public function pconnect() {
        $this->connect();
    }

    public function nconnect() {
        $this->connect(false, true);
    }

    public function isConnected() {
        return $this->_conn instanceof PDO;
    }

    public function qstr($value) {
        if (is_int($value) || is_float($value)) { return $value; }
        if (is_bool($value)) { return $value ? $this->_true_value : $this->_false_value; }
        if (is_null($value)) { return $this->_null_value; }

        return $this->_conn->quote($value);
    }

    public function affectedRows() {
        return $this->_lastrs instanceof PDOStatement ? $this->_lastrs->rowCount() : 0;
    }

    public function execute($sql, $inputarr = null) {
        if (!$this->isConnected()) { $this->connect(); }

        $sth = $this->_conn->prepare($sql);
        if ($this->_log_enabled) { QLog::log($sql, QLog::DEBUG); }

        $result = $sth->execute((array)$inputarr);
        if (false === $result) {
            $error = $sth->errorInfo();
            $this->_last_err = $error[2];
            $this->_last_err_code = $error[1];
            $this->_has_failed_query = true;

            throw new QDB_Exception($sql, $this->_last_err, $this->_last_err_code);
        }

        $this->_lastrs = $sth;
        if ('select' == strtolower(substr($sql, 0, 6))) {
            return new QDB_Result_Pdo($this->_lastrs, $this->_fetch_mode);
        } else {
            return $this->affectedRows();
        }
    }

    function selectLimit($sql, $offset = 0, $length = 30, array $inputarr = null)
    {
        $sql = sprintf('%s LIMIT %d OFFSET %d', $sql, $length, $offset);
        return $this->_execute($sql, $inputarr);
    }
}

class QDB_Adapter_Pdo_Exception extends QException {
}
