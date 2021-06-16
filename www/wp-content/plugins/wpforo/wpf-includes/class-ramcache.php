<?php
// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

class wpForoRamCache {
	/**
	 * @var array
	 */
	private static $sql_cache;

	/**
	 * wpForoSqlCache constructor.
	 */
	public function __construct() {
		$this->reset();
	}

	/**
	 * set empty array to static $sql_cache
	 *
	 * @param mixed $key
	 *
	 * @return void
	 */
	public function reset( $key = null ) {
		if( is_null($key) ){
			self::$sql_cache = array();
		}else{
			unset( self::$sql_cache[ $this->fix_key($key) ] );
		}
	}

	/**
	 * @param mixed $key
	 *
	 * @return string
	 */
	private function fix_key( $key ) {
		if( !is_scalar($key) ) $key = json_encode($key);
		return md5($key);
	}

	/**
	 * checking if this sql query already cached
	 *
	 * @param mixed $key string SQL query
	 *
	 * @return bool
	 */
	public function is_exist( $key ) {
		return array_key_exists( $this->fix_key($key), self::$sql_cache );
	}

	/**
	 * return already cached SQL data
	 *
	 * @param mixed $key string sql query
	 *
	 * @return mixed
	 */
	public function get( $key ) {
		if( $this->is_exist($key) ){
			return self::$sql_cache[ $this->fix_key($key) ];
		}else{
			return null;
		}
	}

	/**
	 * storing a cache of provided SQL data
	 *
	 * @param mixed $key string sql query
	 * @param mixed $data
	 */
	public function set( $key, $data ) {
		self::$sql_cache[ $this->fix_key($key) ] = $data;
	}
}