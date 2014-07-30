<?php
/**
* MYSQLI Adapter class for managing mysql query requests
* Part of Onyx framework
*
* @copyright (c) 2014 Mohammed Seraj, <mmaseraj.com>, <mmaseraj@gmail.com>
* @author Mohd Mansour Seraj
* @license CC license, do whatever you want
* 
*/
class MySQLIAdapter extends MySQLManager{
	/**
	* mysqli database object
	* @access private
	*/
	private static $_dbObject;

	/**
	* Singleton static method for storeing this class instance
	* @access private
	*/
	private static $_singletonThis;

	/**
	* Mysqli database configuration, excepected array keys: db_server, db_user, db_password, db_name
	* @access private
	*
	*/
	private static $_dbConfig = array();

	/**
	* Storing last query of mysqli resource
	* @access private
	*/
	private $_lastQuery;

	/**
	* Singleton method for getting this class instance
	* @access public
	* @return MYSQLI
	* 
	**/
	public static function getConnection(){
		if(  !isset(self::$_singletonThis)  ){
			$class = __CLASS__;
			self::$_singletonThis = new $class;
		}

		return self::$_singletonThis;
	}

	/**
	* Constructor but in a private method, to prevent direct calling
	* @access private
	* @return this class
	*/
	private function __construct(){
		$this->connect();
		return $this;
	}

	// do nothing in clonning this class
	public function __clone(){}

	/**
	* Setting configuration of mysqli database
	* @access public
	* @return NULL
	* 
	*/
	public static function config( $config = NULL ){
		// db_server, db_user, db_password, db_name
		if( is_null($config ) || 
			!array_key_exists( 'db_server', $config ) || 
			!array_key_exists( 'db_user', $config ) || 
			!array_key_exists( 'db_password', $config )|| 
			!array_key_exists( 'db_name', $config )
		){
			throw new Exception( "Database configuration is not correct.", 1 );
		}
		self::$_dbConfig = $config;
	}

	/**
	* Connect to mysqli database using configuration setted by config() method
	* @access private
	* @return NULL
	*/
	private static function connect(){
		self::$_dbObject = new MYSQLI;
		call_user_func_array( array(self::$_dbObject, 'connect'), self::$_dbConfig );
	}

	/**
	* Getting database object
	* @access private
	* @return database object
	*/
	private function dbo(){
		return self::$_dbObject;
	}

	/**
	* Get number of rows of the last query
	* @access public
	* @return int
	*/
	public function rows(){
		return $this->_lastQuery->num_rows;
	}

	/**
	* Get number of affected rows of the last query
	* @access public
	* @return int
	*/
	public function affected(){
		return $this->dbo()->affected_rows;
	}

	/**
	* Get last insert id of, AUTO_INCREMENT fields in database
	* @access public
	* @return int
	*/
	public function inserted(){
		return $this->dbo()->insert_id;
	}

	/**
	* Return fetched array of mysql result
	* @param string (row, '') if row is the parameter this method will return a single array
	* @access public
	* @return array Array of results
	*/
	public function result( $param = NULL ){
		if( $param == 'row' ){
			return $this->_lastQuery->fetch_array( MYSQLI_ASSOC );
		}else{
			$result = array();
			while(  $row = $this->_lastQuery->fetch_array( MYSQLI_ASSOC )  ){
				$result[] = $row;
			}

			return $result;
		}
	}

	/**
	* Execute query, or gathering all junks of mysql statment
	* @access public 
	* @return MYSQLI
	*/
	public function execute(){
		$this->_lastQuery = self::$_dbObject->query( parent::execute() );
		if(!$this->_lastQuery){
			throw new Exception(self::$_dbObject->error, 1);
		}
		return $this;
	}
}
