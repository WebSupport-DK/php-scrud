<?php

/**
 * Class for managing the database connection
 * 
 * @original_author Alex Garret
 * @link https://www.youtube.com/playlist?list=PLfdtiltiRHWF5Rhuk7k4UAU1_yLAZzhWc Tutorial videos from Alex Garret (Code Course)
 * @author Thomas Elvin <thom855j@cvkweb.dk>
 */

namespace thom855j\PHPScrud;

use PDO;

class DB
{

    protected static
            $_instance = null;
    private
            $type,
            $server,
            $database,
            $user,
            $password;
    public
            $_pdo,
            $_query,
            $_error              = false,
            $_results,
            $_first,
            $_count              = 0,
            $_records,
            $_perPage,
            $_pages;

    public
            function __construct($type, $server, $database, $user, $password)
    {
        $this->type     = $type;
        $this->server   = $server;
        $this->database = $database;
        $this->user     = $user;
        $this->password = $password;

        try
        {
            $this->_pdo = new PDO($this->type . ':host=' . $this->server . ';dbname=' . $this->database, $this->user, $this->password);
            $this->_pdo->exec('set names utf8');
        }
        catch (PDOException $e)
        {
            die($e->getMessage());
        }
    }

    public static
            function load($type = null, $server = null, $database = null, $user = null, $password = null)
    {
        if (!isset(self::$_instance))
        {
            self::$_instance = new DB($type, $server, $database, $user, $password);
        }
        return self::$_instance;
    }

    public
            function setPDO($name, $value)
    {
        $this->$name = $value;
    }

    public
            function lastInsertId()
    {
        // Return last inserted ID from DB 
        return $this->_pdo->lastInsertId();
    }

    public
            function query($sql, $params = array(), $table = '', $paging = array())
    {


        if (!empty($paging))
        {

            $this->paging($table, $paging);
            $sql .= " LIMIT $this->_pages,$this->_perPage";
            $this->query($sql, $params);
        }


        $this->_error = false;
        $prepare      = $this->_query = $this->_pdo->prepare($sql);

        if (isset($prepare))
        {
            $data_type = 2;
            $x         = 1;
            if (count($params))
            {
                foreach ($params as $param)
                {
                    if (is_numeric($param))
                    {
                        $data_type = 1;
                    }
                    $this->_query->bindValue($x, $param, $data_type);
                    $x++;
                }
            }

            if ($this->_query->execute())
            {
                $this->_results = $this->_query->fetchAll(PDO::FETCH_OBJ);
                $this->_count   = $this->_query->rowCount();
            }
            else
            {
                $this->_error = true;
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
    public
            function action($action, $table, $where = array(), $options = array())
    {
        $sql   = "{$action} FROM {$table}";
        $value = array();

        if (!empty($where))
        {
            $sql .= " WHERE ";
            foreach ($where as $clause)
            {
                if (count($clause) === 3)
                {
                    $operators = array('=', '>', '<', ' >=', '<=', '<>');

                    if (isset($clause))
                    {
                        $field     = $clause[0];
                        $operator  = $clause[1];
                        $value[]   = $clause[2];
                        $bindValue = '?';
                    }

                    if (in_array($operator, $operators))
                    {
                        $sql .= "{$field} {$operator} {$bindValue}";
                        $sql .= " AND ";
                    }
                }
            }
            $sql = rtrim($sql, " AND ");
        }

        if (!empty($options))
        {
            foreach ($options as $optionKey => $optionValue)
            {
                $sql .= " {$optionKey} {$optionValue}";
            }
        }

        if (!$this->query($sql, $value)->error())
        {
            return (object) $this;
        }
        return false;
    }

    public
            function select($select = array(), $table, $paging = array(), $where = array(), $options = array())
    {
        if (empty($paging))
        {
            return $this->action('SELECT ' . implode($select, ', '), $table, $where, $options);
        }
        else
        {
            $this->paging($table, $paging, $where);
            $options = array_merge($options, array('LIMIT' => "$this->_pages,$this->_perPage"));
            return $this->action('SELECT ' . implode($select, ', '), $table, $where, $options);
        }
    }

    private
            function paging($table, $limit = array(), $where = array())
    {
        $page           = isset($limit['start']) ? (int) $limit['start'] : 1;
        $this->_perPage = isset($limit['end']) && $limit['end'] <= $limit['max'] ? (int) $limit['end'] : 5;

        $this->_pages   = ($page > 1) ? ($page * $this->_perPage ) - $this->_perPage : 0;
        $this->getRecords($table, $where);
        $this->_records = (ceil($this->_records[0] / $this->_perPage));
    }

    public
            function search($table, $attributes = array(), $searchQuery, $options = null)
    {

        if (!empty($searchQuery) && !empty($attributes))
        {

            $query = "";

            foreach ($attributes as $term)
            {
                foreach ($searchQuery as $search)
                {
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

    public
            function insert($table, $fields = array())
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

    public
            function delete($table, $where = array())
    {
        return $this->action('DELETE', $table, $where);
    }

    public
            function update($table, $attribute, $ID, $fields = array())
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

    public
            function alter($table, $column, $info)
    {
        $sql = "ALTER TABLE ? ADD ? ?";
        return $this->query($sql, array($table, $column, $info));
    }

    public
            function results()
    {
        return $this->_results;
    }

    public
            function first()
    {
        $this->_first = $this->results();
        return $this->_first[0];
    }

    public
            function error()
    {
        return $this->_error;
    }

    public
            function getRecords($table, $where =array())
    {
        $this->select(array("count(*) AS records"), $table, null, $where);
        return $this->_records = $this->results()[0]->records;
    }

    public
            function count()
    {
        return $this->_count;
    }

}