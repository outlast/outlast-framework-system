<?php
/**
 * Connects to the OFW memcache server and provides the Memcache object interface.
 * @author Aron Budinszky <aron@outlast.hu>
 * @version 3.0
 * @package Library
 * @deprecated
 **/

/**
 * Class zajlib_memcache extends Memcache.
 * @link http://us.php.net/manual/en/class.memcache.php
 * @method bool add (string $key, mixed $var, int $flag = null, int $expire = null)
 * @method bool addServer (string $host, int $port = 11211, bool $persistent = null, int $weight = null, int $timeout = null, int $retry_interval = null, bool $status = null, callable $failure_callback = null, int $timeoutms = null)
 * @method bool close
 * @method int decrement (string $key, int $value = 1)
 * @method bool delete (string $key, int $timeout = 0)
 * @method bool flush
 * @method string get (string $key, int &$flags = null)
 * @method array getExtendedStats (string $type = null, int $slabid = null, int $limit = 100)
 * @method int getServerStatus (string $host, int $port = 11211)
 * @method array getStats (string $type = null, int $slabid = null, int $limit = 100)
 * @method string getVersion
 * @method int increment (string $key, int $value = 1)
 * @method mixed pconnect (string $host, int $port = null, int $timeout = null)
 * @method bool replace (string $key , mixed $var, int $flag = null, int $expire = null)
 * @method bool set (string $key , mixed $var, int $flag = null, int $expire = null)
 * @method bool setCompressThreshold (int $threshold, float $min_savings = null)
 * @method bool setServerParams (string $host, int $port = 11211, int $timeout = null, int $retry_interval = false, bool $status = null, callable $failure_callback = null)
 */
class zajlib_memcache extends zajLibExtension {

	/**
	 * @var Memcache|boolean $memcache The actual memcache connection object.
	 */
	private $memcache = false;

	/**
	 * Returns true or false depending on if memcache is enabled system wide.
	 * @return boolean True if enabled, false otherwise.
	 */
	public function is_memcache_enabled(){
		return ($this->zajlib->zajconf['memcache_enabled'] && function_exists('memcache_connect'));
	}

	/**
	 * Connect to memcache server.
	 * @return boolean Returns true if the connection was successful, false otherwise.
	 */
	public function connect(){
		// If already connected, return true
			if($this->memcache) return true;
		// If not, let's try
			if(class_exists('Memcache', false)){
				$this->memcache = new Memcache();
				$success = $this->memcache->connect($this->zajlib->zajconf['memcache_server'], $this->zajlib->zajconf['memcache_port']);
			}
			else $success = false;
		// Success or failure?
			if(!$success){
				$this->zajlib->warning("Failed to connect to Memcache server. Verify that it is installed, started, and the PECL extension is enabled!");
				$this->memcache = false;
			}
		return $success;
	}

	/**
	 * Redirect all requests to the Memcache object.
	 * @param string $name The name of the method to call.
	 * @param array $arguments The arguments to pass.
	 * @return mixed Returns whatever the Memcache object returns. See http://us.php.net/manual/en/class.memcache.php
	 */
	public function __call($name, $arguments){
		// Try to connect automatically if not yet connected
			if(!$this->memcache) $this->connect();
		// Return false if failed
			if(!$this->memcache) return false;
		// Call method on object
			return call_user_func_array(array($this->memcache, $name), $arguments);
	}

	/**
	 * Return the actual Memcache object. You can use this for debug purposes.
	 * @return Memcache|boolean The Memcache object used by OFW.
	 */
	public function get_object(){
		// Try to connect
			if(!$this->memcache) $this->connect();
		// Now return
			return $this->memcache;
	}
}