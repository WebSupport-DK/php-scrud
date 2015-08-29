<?php

namespace thom855j\sql ;

use PDO ;

class DB
{

    protected static
            $_instance = null ;
    private
            $type ,
            $server ,
            $database ,
            $user ,
            $password ;
    public
            $_pdo ,
            $_query ,
            $_error              = false ,
            $_results ,
            $_first ,
            $_count              = 0 ;

    public
            function __construct( $type , $server , $database , $user ,
                                  $password )
    {
        $this->type     = $type ;
        $this->server   = $server ;
        $this->database = $database ;
        $this->user     = $user ;
        $this->password = $password ;
        
        try
        {
            $this->_pdo = new PDO( $this->type . ':host=' . $this->server . ';dbname=' . $this->database ,
                                   $this->user , $this->password ) ;
            $this->_pdo->exec( 'set names utf8' ) ;
        }
        catch ( PDOException $e )
        {
            die( $e->getMessage() ) ;
        }
    }

    public static
            function load( $type = null , $server = null , $database = null ,
                           $user = null , $password = null )
    {
        if ( !isset( self::$_instance ) )
        {
            self::$_instance = new DB( $type , $server , $database , $user ,
                                       $password ) ;
        }
        return self::$_instance ;
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
        return $this->_pdo->lastInsertId() ;
    }

    public
            function query( $sql , $params = array() )
    {
        $this->_error = false ;
        $prepare      = $this->_query = $this->_pdo->prepare( $sql ) ;

        if ( isset( $prepare ) )
        {

            $x = 1 ;
            if ( count( $params ) )
            {
                foreach ( $params as $param )
                {
                    $this->_query->bindValue( $x , $param ) ;
                    $x++ ;
                }
            }

            if ( $this->_query->execute() )
            {
                $this->_results = $this->_query->fetchAll( PDO::FETCH_OBJ ) ;
                $this->_count   = $this->_query->rowCount() ;
            }
            else
            {
                $this->_error = true ;
            }
        }
        return ( object ) $this ;
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
            function action( $action , $table , $where = array() ,
                             $options = array() )
    {
        $sql   = "{$action} FROM {$table}" ;
        $value = array() ;

        if ( !empty( $where ) )
        {
            $sql .= " WHERE " ;
            foreach ( $where as $clause )
            {
                if ( count( $clause ) === 3 )
                {
                    $operators = array( '=' , '>' , '<' , ' >=' , '<=' ) ;

                    if ( isset( $clause ) )
                    {
                        $field     = $clause[ 0 ] ;
                        $operator  = $clause[ 1 ] ;
                        $value[]   = $clause[ 2 ] ;
                        $bindValue = '?' ;
                    }

                    if ( in_array( $operator , $operators ) )
                    {
                        $sql .= "{$field} {$operator} {$bindValue}" ;
                        $sql .= " AND " ;
                    }
                }
                //var_dump($sql);
            }
            $sql = rtrim( $sql , " AND " ) ;
        }

        if ( !empty( $options ) )
        {
            foreach ( $options as $optionKey => $optionValue )
            {
                $sql .= " {$optionKey} {$optionValue}" ;
            }
        }

        if ( !$this->query( $sql , $value )->error() )
        {
            return ( object ) $this ;
        }
        return false ;
    }

    public
            function select( $select = array() , $table , $where = array() ,
                             $options = array() )
    {
        return $this->action( 'SELECT ' . implode( $select , ', ' ) , $table ,
                                                   $where , $options ) ;
    }

    public
            function search( $table , $rows , $searchQuery ,
                             $searchTerms = array() , $options = null )
    {


        if ( !empty( $searchQuery ) && !empty( $searchTerms ) )
        {
            $z = 1 ;
            for ( $x = 0 ; $x < $searchTerms ; $x++ )
            {
                for ( $y = 0 ; $y < count( $searchQuery ) ; $y++ )
                {
                    $this->_query->bindValue( $z++ , $searchQuery[ $y ] ) ;
                }
            }
        }

        $query = "" ;
        if ( isset( $searchQuery ) )
        {
            foreach ( $searchTerms as $term )
            {
                $query .= "{$term} LIKE '%{$searchQuery}%' OR " ;
            }

            $search = trim( $query , "OR " ) ;

            $sql = "SELECT {$rows} FROM {$table} WHERE {$search}" ;

            if ( !$this->query( $sql )->error() )
            {
                return ( object ) $this ;
            }
        }
    }

    public
            function insert( $table , $fields = array() )
    {
        $keys   = array_keys( $fields ) ;
        $values = '' ;
        $x      = 1 ;

        foreach ( $fields as $field )
        {
            $values .= '?' ;
            if ( $x < count( $fields ) )
            {
                $values .= ', ' ;
            }
            $x++ ;
        }

        $sql = "INSERT INTO {$table} (`" . implode( '`,`' , $keys ) . "`) VALUES ({$values})" ;

        if ( !$this->query( $sql , $fields )->error() )
        {
            return true ;
        }
        return false ;
    }

    public
            function delete( $table , $where = array() )
    {
        return $this->action( 'DELETE' , $table , $where ) ;
    }

    public
            function update( $table , $column_ID , $ID , $fields = array() )
    {
        $set = '' ;
        $x   = 1 ;

        foreach ( $fields as $name => $value )
        {
            $set .= "{$name} = ?" ;
            if ( $x < count( $fields ) )
            {
                $set .= ', ' ;
            }
            $x++ ;
        }

        $sql = "UPDATE {$table} SET {$set} WHERE {$column_ID} = {$ID}" ;

        if ( !$this->query( $sql , $fields )->error() )
        {
            return true ;
        }
        return false ;
    }

    public
            function results()
    {
        return $this->_results ;
    }

    public
            function first()
    {
        $this->_first = $this->results() ;
        return $this->_first[ 0 ] ;
    }

    public
            function error()
    {
        return $this->_error ;
    }

    public
            function count()
    {
        return $this->_count ;
    }

}
