<?php

/**
 * 
 * MySQL managment Class
 * Managing select, update, drop, delete, truncate, create ..etc
 * Part of Onyx framework
 * 
 * @author Mohd Mansour Seraj <mmaseraj@gmail.com>, <mmaseraj.com>
 * @copyright ( c ) 2014, Mohammed Seraj
 * @version 1.0
 * 
 */
class MySQLManager {

    /**
     * @access private
     * @var string last select string 
     */
    private $_lastSelect;

    /**
     * @access private
     * @var string last specified table
     */
    private $_lastTable;

    /**
     * @access private
     * @var string last joining table
     */
    private $_lastJoin;

    /**
     * @access private
     * @var string last on statement of join
     */
    private $_lastOn;

    /**
     * @access private
     * @var where last where
     */
    private $_lastWhere;

    /**
     * @access private
     * @var string last limit
     */
    private $_lastLimit;

    /**
     * @access private
     * @var string last order
     */
    private $_lastOrder;

    /**
     * @access private
     * @var string last update stored array fileds and values
     */
    private $_lastUpdate;

    /**
     * @access private
     * @var string last insert stored array fields and values
     */
    private $_lastInsert;

    /**
     * @access private
     * @var bool
     */
    private $_lastCreate;

    /**
     * @access private
     * @var string
     */
    private $_lastDatabase;

    /**
     * @access private
     * @var string last arrow to delete, drop, add
     */
    private $_lastRow;

    /**
     * @access private
     * @var bool
     */
    private $_lastDrop;

    /**
     * @access private
     * @var array, stored data as field => value
     */
    private $_lastRowData;

    /**
     * @access private
     * @var string
     */
    private $_currentImplement = '';

    /**
     * @access private
     * @var string used in select function to determine the string used to 
     *              split selected fields
     */
    private $_arraySplitChar = ',';

    /**
     * @access private
     * @var string default database engine for creating tables
     */
    private $_mysqlEngine = 'InnoDB';

    /**
     * @access private
     * @var string default charset of mysql engine
     */
    private $_mysqlCharset = 'latin1';

    /**
    * @access private
    * @var boolean truncate table?
    */
    private $_lastTruncate;

    /**
     * Select fields from table, splitted by _arraySplitChar
     * 
     * @param string|array $rows
     * @return \MySQLManager
     * @throws Exception if $rows type NULL
     */
    public function select( $rows = NULL ) {
        if ( $rows == NULL ) {
            throw new Exception( "Invalid argument [rows] Type, excepted types are [array|string]" );
        }

        if ( is_array($rows) ) {
            $this->_lastSelect = $rows;
        } else if ( $rows == '*' ) {
            $this->_lastSelect = array( '*' );
        } else if ( is_string($rows) ) {
            $this->_lastSelect = explode( $this->_arraySplitChar, $rows );
        }

        if ( !empty($this->_lastJoin) ) {
            $this->_currentImplement['select'][] = join( array_map(function($item ) {
                        if ( $item !== '*' ) {
                            return '`' . $this->_lastJoin . '`.' . '`' . $item . '`';
                        } else {
                            return '`' . $this->_lastJoin . '`.' . '' . $item . '';
                        }
                    }, $this->_lastSelect), ', ');
        }

        return $this;
    }

    /**
     * Set the last table property, to select, drop ..etc.
     * 
     * @param string $table table name
     * @param type $options options in creating tables
     * @return \MySQLManager
     * @throws Exception if invalid type of $table
     */
    public function table( $table, $options = array() ) {
        if ( !is_string($table) ) {
            throw new Exception( "Invalid argument type, [table] must be string type", 1 );
        }

        $this->_lastTable = $table;
        $this->_currentImplement['table'][] = $table;

        if ( !empty($this->_lastSelect) ) {
            $this->_currentImplement['select'][] = join( array_map(function($item ) {
                        if ( $item !== '*' ) {
                            return '`' . $this->_lastTable . '`.' . '`' . $item . '`';
                        } else {
                            return '`' . $this->_lastTable . '`.' . '' . $item . '';
                        }
                    }, $this->_lastSelect), ', ');

        } else if ( !empty($this->_lastInsert) ) {
            $fields = array();
            $values = array();

            foreach ( $this->_lastInsert as $key => $value ) {
                $fields[] = '`' . $this->_lastTable . '`' . '.`' . $key . '`';
                $values[] = '"' . $value . '"';
            }
            $this->_lastInsert = '( ' . join($fields, ', ') . ') VALUES (' . join($values, ', ') . ' )';

        } else if ( !empty($this->_lastCreate) && empty($this->_lastRow) ) {
            if ( empty($options) || !is_array($options) ) {
                throw new Exception( "options argument must be an array", 1 );
            }

            $curedFields = array();
            foreach ( $options as $key => $option ) {
                $option = preg_replace( '/(\w+)\|(\d+)/', '$1($2)', $option );
                $option = is_array( $option) ? join($option, ' ' ) : $option;
                $curedFields[] = "\n   " . '`' . $key . '` ' . str_replace(
                                array( ' primary ', ' auto_inc '), array(' PRIMARY_KEY ', ' AUTO_INCREMENT '), strtoupper($option) );
            }

            $this->_lastCreate = 'CREATE TABLE IF NOT EXISTS `' . $this->_lastTable . '`( ' . join($curedFields, ', ') . "\n" . ' )' .
                    "\n" . 'ENGINE=' . $this->_mysqlEngine . ' DEFAULT CHARSET=' . $this->_mysqlCharset . ';';

        } else if ( !empty( $this->_lastUpdate ) ) {

            $fieldsFixing = array();
            foreach ( $this->_lastUpdate as $key => $value ) {
                $fieldsFixing[] = $key . ' = "' . $value . '"';
            }

            $this->_lastUpdate = join( $fieldsFixing, ' AND ' );

        } else if ( !empty( $this->_lastDrop ) && empty( $this->_lastRow ) ) {
            $this->_lastDrop = 'DROP TABLE IF EXISTS `' . $this->_lastTable . '`;';

        } else if ( !empty( $this->_lastDrop ) && !empty( $this->_lastRow ) ) {
            $this->_lastDrop = 'ALTER TABLE `' . $this->_lastTable . '` DROP `' . $this->_lastRow . '`;';

        } else if ( !empty( $this->_lastCreate ) && !empty( $this->_lastRow ) ) {
            $this->_lastCreate = 'ALTER TABLE  `' . $this->_lastTable . '` ADD  `' . $this->_lastRow . '` ' . join($this->_lastRowData, '');

        }else if( !empty( $this->_lastTruncate ) ){
            $this->_lastTruncate = 'TRUNCATE TABLE `'.$this->_lastTable.'`;';
        }else if( !empty( $this->_lastDelete ) ){
            $this->_lastDelete = 'DELETE FROM `'.$this->_lastTable.'`';
        }

        return $this;
    }

    /**
     * Set $this->_lastDatabase property for create, drop methods
     * 
     * @param string $db_name Database name
     * @return \MySQLManager
     * @throws Exception if invalid param type
     */
    public function database( $db_name ) {
        if ( !is_string($db_name) ) {
            throw new Exception( "Database argument must be string type", 1 );
        }

        $this->_lastDatabase = $db_name;
        if ( !empty( $this->_lastCreate ) ) {
            $this->_lastCreate = 'CREATE DATABASE IF NOT EXISTS `' . strtolower( $this->_lastDatabase ) . '`;';
        } else if ( !empty( $this->_lastDrop ) ) {
            $this->_lastDrop = 'DROP DATABASE IF EXISTS `' . $this->_lastDatabase . '`;';
        }
        return $this;
    }

    public function truncate(){
        $this->_lastTruncate = true;
        return $this;
    }

    /**
     * 
     * @param string $db_row Row name
     * @param array $data Row data
     * @return \MySQLManager
     * @throws Exception if row argument is invalid type
     */
    public function row( $db_row = NULL, $data = NULL ) {
        if ( !is_string($db_row) ) {
            throw new Exception( "Row argument must be string type", 1 );
        }

        $this->_lastRow = $db_row;
        $this->_lastRowData = $data;
        return $this;
    }

    /**
     * Set property of join tables
     * 
     * @param string $table Table name for joining
     * @param string $type Type of joining excepted left|right|join
     * @return \MySQLManager
     * @throws Exception
     */
    public function join( $table, $type = '' ) {
        if ( !is_string($table) ) {
            throw new Exception( "Invalid argument type, [table] must be string type", 1 );
        }

        if ( $type == 'join' ) {
            $type = '';
        }

        $this->_lastJoin = $table;

        $exceptedTypes = array( 'left', 'right', '' );
        if ( !in_array(strtolower($type), $exceptedTypes) ) {
            $type = $exceptedTypes[0];
        }

        $type = strtoupper( $type );

        $this->_currentImplement['join'][] = array( 'table' => $table, 'type' => $type );
        return $this;
    }

    /**
     * Set relation between tables of joining
     * 
     * @param string $on
     * @return \MySQLManager
     */
    public function on( $on ) {
        $this->_lastOn = $on;
        $this->_currentImplement['join'][count( $this->_currentImplement['join']) - 1] += array('on' => $on );
        return $this;
    }

    /**
     * Set where statement of the query
     * 
     * @param string $field Field to compare
     * @param string $exp Expression of comparing, excepected >,<..etc LIKE_START, LIKE_END, LIKE_BOTH
     * @param string $val Value to compare with
     * @return \MySQLManager
     */
    public function where( $field, $exp = NULL, $val = NULL ) {
        if ( is_array($field) ) {
            foreach ( $field as $singleWhere ) {
                $this->where( $singleWhere[0], $singleWhere[1], $singleWhere[2] );
            }
            return $this;
        }

        $field = explode( '.', $field );

        $field = array_map( function($item ) {
            return '`' . $item . '`';
        }, $field);

        $expression = strtolower( $exp );

        switch ( $expression ) {
            case 'like_start':
                $val = '"%' . $val . '"';
                break;

            case 'like_end':
                $val = '"' . $val . '%"';
                ;
                break;

            case 'like_both':
            case 'like':
                $val = '"%' . $val . '%"';
                ;
                break;
        }

        $this->_lastWhere = join( $field, '.' ) . ' ' . $exp . ' ' . '' . $val . '';
        $this->_currentImplement['where'][] = $this->_lastWhere;

        return $this;
    }

    /**
     * Set ( AND ) of where close, its params is the same as $this->where mthod
     * 
     * @param string $field
     * @param string $exp
     * @param string $val
     * @return \MySQLManager
     */
    public function Qand( $field, $exp = NULL, $val = NULL ) {
        $this->where( $field, $exp, $val );

        $current = count( $this->_currentImplement['where'] ) - 1;
        $this->_currentImplement['where'][$current] = ' AND ' . $this->_currentImplement['where'][$current];

        return $this;
    }

    /**
     * Set ( OR ) of where close, its params is the same as $this->where mthod
     * 
     * @param type $field
     * @param type $exp
     * @param type $val
     * @return \MySQLManager
     */
    public function Qor( $field, $exp = NULL, $val = NULL ) {
        $this->where( $field, $exp, $val );

        $current = count( $this->_currentImplement['where'] ) - 1;
        $this->_currentImplement['where'][$current] = ' OR ' . $this->_currentImplement['where'][$current];

        return $this;
    }

    /**
     * Set the limitation of mysql result
     * 
     * @param int $limit
     * @return \MySQLManager
     * @throws Exception if $limit is not integer
     */
    public function limit( $limit ) {
        if ( !is_int($limit) ) {
            throw new Exception( "limit argument must be integer.", 1 );
        }

        $this->_lastLimit = intval( $limit );
        return $this;
    }

    /**
     * Set order of mysql result, and type of results
     * 
     * @param string $order Field of ordering
     * @param string $type expected ( DESC|ASC )
     * @return \MySQLManager
     */
    public function order( $order, $type = 'ASC' ) {
        if ( !is_array($order) ) {
            $default = $order;
            $order = array();
            $order[] = $default;
        }

        foreach ( $order as $row ) {
            $row = explode( '.', $row );

            $row = array_map( function($item ) {
                return '`' . $item . '`';
            }, $row);

            $orderResult[] = join( $row, '.' );
        }

        $arrayType = array( 'desc', 'asc' );

        if ( !in_array(strtolower($type), $arrayType) ) {
            $type = 'asc';
        }

        $this->_lastOrder = join( $orderResult, ', ') . ' ' . strtolower($type );
        return $this;
    }

    /**
     * Updating rows in database, array( field => update )
     * 
     * @param array $updatingArray
     * @return \MySQLManager
     */
    public function update( Array $updatingArray ) {
        $this->_lastUpdate = $updatingArray;
        return $this;
    }

    /**
     * Inserting data to database
     * 
     * @param array $insertingArray
     * @return \MySQLManager
     */
    public function insert( Array $insertingArray ) {
        $this->_lastInsert = $insertingArray;
        return $this;
    }

    /**
     * Set _lastCreate property, for creating database(), table(), row()
     * @return \MySQLManager
     */
    public function create() {
        $this->_lastCreate = true;
        return $this;
    }

    /**
     * Set _lastDelete to delete results from database table
     * @return \MySQLManager
     */
    public function delete() {
        $this->_lastDelete = true;
        return $this;
    }

    /**
     * Set _lastDrop to delete database(), table(), row()
     * @return \MySQLManager
     */
    public function drop() {
        $this->_lastDrop = true;
        return $this;
    }

    /**
     * Unset all property that has values
     * 
     * @access private
     * @return \MySQLManager
     */
    private function unsetAll() {
        $ref = new ReflectionClass( $this );
        foreach ( $ref->getProperties() as $key => $value ) {
            $value = $value->getName();
            if ( in_array($value, array('_mysqlCharset', '_mysqlEngine', '_arraySplitChar', '_dbObject', '_singletonThis', '_dbConfig', '_lastQuery')) ) {
                continue;
            }

            $this->$value = NULL;
        }

        return $this;
    }

    /**
     * Join all statment to a single Query string
     * @access public
     * @return NULL
     */
    public function execute() {
        $lastImplementaion = '';
        $finalWhere = ( count($this->_lastWhere) > 0) ? ' WHERE ' . join($this->_currentImplement['where'], ' ' ) : '';
        $finalLimit = ( strlen($this->_lastLimit) > 0 ) ? ' lIMIT ' . $this->_lastLimit : '';

        if ( !empty($this->_lastSelect) ) {
            $finalSelect = ' SELECT ' . join( $this->_currentImplement['select'], ',' );

            $finalTables = ' FROM ' . join( array_map(function($item ){
                return '`'.$item.'`';
            }, $this->_currentImplement['table']), ',');

            $finalOrder = ( strlen($this->_lastOrder) > 0 ) ? ' ORDER BY ' . $this->_lastOrder : '';

            $finalJoin = '';

            if ( isset($this->_currentImplement['join']) && count($this->_currentImplement['join']) > 0 ) {
                foreach ( $this->_currentImplement['join'] as $key => $value ) {
                    $finalJoin[] = ' ' . $value['type'] . ' JOIN `' . $value['table'] . '`' . ' ON ' . $value['on'];
                }

                $finalJoin = join( $finalJoin, ' ' );
            }

            $lastImplementaion = $finalSelect . $finalTables . $finalJoin . $finalWhere . $finalOrder . $finalLimit;
        } else if ( !empty($this->_lastUpdate) ) {
            if(empty($finalWhere)){
                throw new Exception("If you preform this query, you will update all rows in table", 1);
            }

            $lastImplementaion = 'UPDATE ' . $this->_lastTable . ' SET ' . $this->_lastUpdate . $finalWhere . $finalLimit;
        } else if ( !empty($this->_lastInsert) ) {
            $lastImplementaion = 'INSERT INTO `' . $this->_lastTable . '` ' . $this->_lastInsert;
        } else if ( !empty($this->_lastCreate) ) {
            $lastImplementaion = $this->_lastCreate;
        } else if ( !empty($this->_lastDrop) ) {
            $lastImplementaion = $this->_lastDrop;
        }else if( !empty($this->_lastTruncate) ){
            $lastImplementaion = $this->_lastTruncate;
        }else if( !empty($this->_lastDelete) ) {
            if(empty($finalWhere)){
                throw new Exception("If you preform this query, you will delete all rows in table, if you want so user truncate() instead.", 1);
            }

            $lastImplementaion = $this->_lastDelete . $finalWhere . $finalLimit;
        }

        $lastImplementaion = trim( $lastImplementaion );
        $this->unsetAll();

        unset( $this->_currentImplement );
        unset( $this->_currentImplement );
        unset( $finalSelect );
        unset( $finalTables );
        unset( $finalJoin );
        unset( $finalWhere );
        unset( $finalOrder );
        unset( $finalLimit );
        return $lastImplementaion;
    }

    /**
    * To string method, if execute() didn't call, call the class this way
    * @access public
    * @return execute function of this class
    */
    public function __toString(){
        return $this->execute();
    }

}

/**
* End of file ./mysqlmanager.php
*/
