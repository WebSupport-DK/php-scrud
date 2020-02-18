<?php

/**
 * Class for managing the database connection
 * 
 * @originalauthor Alex Garret
 * @link https://www.youtube.com/playlist?list=PLfdtiltiRHWF5Rhuk7k4UAU1yLAZzhWc 
 * Tutorial videos from Alex Garret (Code Course)
 * @author Thomas Elvin <thom855j@cvkweb.dk>
 */

namespace PHP\CRUD;

use PDO;
use PDOException;

class Database
{

    protected static $instance = null;

    private
            $driver,
            $host,
            $database,
            $username,
            $password;

    public
            $pdo,
            $query,
            $error = false,
            $results,
            $first,
            $count = 0,
            $records,
            $perPage,
            $pages;

    public function __construct($connection = array())
    {
        $this->driver     = $connection['driver'];
        $this->host   = $connection['host'];
        $this->database = $connection['database'];
        $this->username     = $connection['username'];
        $this->password = $connection['password'];

        try {
            $this->pdo = new PDO(
                $this->driver . ':host=' . 
                $this->host . ';dbname=' . 
                $this->database, 
                $this->username, 
                $this->password
            );

        $this->pdo->exec('set names ' . $connection['charset']);

        } catch (PDOException $e) {
            die($e->getMessage());
        }
    }

    public static function singleton($connection = array())
    {
        if (!isset(self::$instance)) {
            self::$instance = new Database($connection = array());
        }

        return self::$instance;
    }

    public function setPDO($name, $value)
    {
        $this->$name = $value;
    }

    public function lastInsertId()
    {
        // Return last inserted ID from DB 
        return $this->pdo->lastInsertId();
    }

    public function query($sql, $params = array(), $table = '', $paging = array())
    {

        if (!empty($paging)) {
            $this->paging($table, $paging);
            $sql .= " LIMIT $this->pages,$this->perPage";
            $this->query($sql, $params);
        }

        $this->error = false;

        $prepare = $this->query = $this->pdo->prepare($sql);

        if (isset($prepare)) {

            $datatype = 2;
            $x         = 1;

            if (count($params)) {
                foreach ($params as $param) {

                    if (is_numeric($param)) {
                        $datatype = 1;
                    }

                    $this->query->bindValue($x, $param, $datatype);
                    $x++;
                }
            }

            if ($this->query->execute()) {
                $this->results = $this->query->fetchAll(PDO::FETCH_OBJ);
                $this->count   = $this->query->rowCount();
            } else {
                $this->error = true;
            }
        }
        return (object) $this;
    }

    /**
     * Method for dynamically generating SQL queries
     * 
     * @param string $action SQL statement
     * @param string $table Database table
     * @param array $where Multi-dimensional array for multiple WHERE statements
     * @param array $options Array with miscellaneous satements like ORDER BY
     * @return boolean|object
     */
    public function action($action, $table, $where = array(), $options = array())
    {
        $sql   = "{$action} FROM {$table}";
        $value = array();

        if (!empty($where)) {

            $sql .= " WHERE ";

            foreach ($where as $clause) {
                if (count($clause) === 3) {

                    $operators = array('=', '>', '<', ' >=', '<=', '<>');

                    if (isset($clause)) {
                        $field     = $clause[0];
                        $operator  = $clause[1];
                        $value[]   = $clause[2];
                        $bindValue = '?';
                    }

                    if (in_array($operator, $operators)) {
                        $sql .= "{$field} {$operator} {$bindValue}";
                        $sql .= " AND ";
                    }
                }
            }
            $sql = rtrim($sql, " AND ");
        }

        if (!empty($options)) {
            foreach ($options as $optionKey => $optionValue) {
                $sql .= " {$optionKey} {$optionValue}";
            }
        }

        if (!$this->query($sql, $value)->error()) {
            return (object) $this;
        }

        return false;
    }

    public function select($select = array(), $table, $paging = array(), $where = array(), $options = array())
    {
        if (empty($paging)) {
            return $this->action('SELECT ' . implode($select, ', '), $table, $where, $options);
        } else {
            $this->paging($table, $paging, $where);
            $options = array_merge($options, array('LIMIT' => "$this->pages,$this->perPage"));
            return $this->action('SELECT ' . implode($select, ', '), $table, $where, $options);
        }
    }

    private function paging($table, $limit = array(), $where = array())
    {
        $page           = isset($limit['start']) ? (int) $limit['start'] : 1;
        $this->perPage = isset($limit['end']) && $limit['end'] <= $limit['max'] ? (int) $limit['end'] : 5;

        $this->pages   = ($page > 1) ? ($page * $this->perPage ) - $this->perPage : 0;
        $this->getRecords($table, $where);
        $this->records = (ceil($this->records[0] / $this->perPage));
    }

    public function search($table, $attributes = array(), $searchQuery, $options = null)
    {

        if (!empty($searchQuery) && !empty($attributes)) {

            $query = "";

            foreach ($attributes as $term) {
                foreach ($searchQuery as $search) {
                    $query .= "{$term} LIKE ? OR ";
                }
            }

            $search = trim($query, "OR ");
            $sql = "SELECT " . implode($attributes, ', ') . " FROM {$table} WHERE {$search}";
            $z = 1;

            for ($x = 0; $x < count($attributes); $x++)
            {
                for ($y = 0; $y < count($searchQuery); $y++)
                {
                    $params[$z++] = $searchQuery[$y];
                }
            }

            if (!$this->query($sql, $params)->error())
            {
                return (object) $this;
            }
        }
    }

    public function insert($table, $fields = array())
    {
        $keys   = array_keys($fields);
        $values = '';
        $x      = 1;

        foreach ($fields as $field)
        {
            $values .= '?';
            if ($x < count($fields))
            {
                $values .= ', ';
            }
            $x++;
        }

        $sql = "INSERT INTO {$table} (`" . implode('`,`', $keys) . "`) VALUES ({$values})";

        if (!$this->query($sql, $fields)->error())
        {
            return true;
        }
        return false;
    }

    public function delete($table, $where = array())
    {
        return $this->action('DELETE', $table, $where);
    }

    public function update($table, $attribute, $ID, $fields = array())
    {
        $set = '';
        $x   = 1;

        foreach ($fields as $name => $value)
        {
            $set .= "{$name} = ?";
            if ($x < count($fields))
            {
                $set .= ', ';
            }
            $x++;
        }

        $sql = "UPDATE {$table} SET {$set} WHERE {$attribute} = {$ID}";

        if (!$this->query($sql, $fields)->error())
        {
            return true;
        }
        return false;
    }

    public function alter($table, $column, $info)
    {
        $sql = "ALTER TABLE ? ADD ? ?";
        return $this->query($sql, array($table, $column, $info));
    }

    public function results()
    {
        return $this->results;
    }

    public function first()
    {
        $this->first = $this->results();
        return $this->first[0];
    }

    public function error()
    {
        return $this->error;
    }

    public function getRecords($table, $where =array())
    {
        $this->select(array("count(*) AS records"), $table, null, $where);
        return $this->records = $this->results()[0]->records;
    }

    public function count()
    {
        return $this->count;
    }
}
