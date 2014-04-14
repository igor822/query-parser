<?php
namespace QueryParser;

use Zend\Config\Reader;

//require_once '../vendor/autoload.php';

class QueryParser {

	private $_filename;

	private $_reader;

	private $_data;

	private static $_instance = null;

	private $prefixPath = '';
	
	/**
	 * Constructor of class
	 * 
	 * @access public
	 * @param {string} $file Path of file
	 * @return void
	 */
	public function __construct($file = '', $prefixPath = '') {
		if ($prefixPath != '') $this->setPrefixPath($prefixPath);
		$this->_reader = new Reader\Yaml(array('Spyc','YAMLLoadString'));
		if ($file != '') {
			$this->configure($file);
		}
	}

	public function setPrefixPath($prefixPath) {
		$this->prefixPath = $prefixPath;
	}

	public function getPrefixPath() {
		return $this->prefixPath;
	}

	/**
	 * Convert yaml file to array and store into $_data
	 *
	 * @access public
	 * @param {string} $file Path of file with all queries
	 * @return {object} $this
	 */
	public function configure($file) {
		$this->_data = $this->_reader->fromFile($file);
		return $this;
	}

	/**
	 * Retrieve all data stored at file
	 *
	 * @access public
	 * @return {array} $_data
	 */
	public function getData() {
		return $this->_data;
	}

	/**
	 * Find and replace the field:type into query
	 * /<(.*):([a-z]+)>/ Get parameters of query
	 *
	 * @access static
	 * @param {string} $query Query to be replaced
	 * @param {array} $values Array with 'key' to find into query and value to be replaced
	 * @return {string} $query 
	 */
	public static function replaceValues(&$query, $values = null) {
		$replaced = false;
		if (!empty($values) && preg_match_all('/<([a-zA-Z0-9_]+):([a-zA-Z0-9_]+)>/', $query, $matches, PREG_SET_ORDER) !== 0) {
			foreach ($matches as $match) {
				if (array_key_exists($match[1], $values)) {
					switch ($match[2]) {
						case 'int': $val = $values[$match[1]]; break;
						case 'like': $val = '\'%'.$values[$match[1]].'%\''; break;
						case 'str': 
						default:
							$val = '\''.$values[$match[1]].'\''; 
						break;
					}
					$query = preg_replace('/<'.$match[1].':([a-zA-Z0-9_]+)>/', $val, $query);
					$replaced = true;
				} else {
					self::removeNamedConditional($query, $match[1]);
				}
			}
		}
		if (false === $replaced) return self::removeConditionals($query);
		else return self::removeConditionalParameter($query);
	}

	/**
	 * Find and remove conditional parts of query in case of none parameters has replaced
	 *
	 * @access static
	 * @param {string} $query Query to be formated
	 * @return {string} $query
	 */
	public static function removeConditionals(&$query) {
		$pattern = '/(\S+:)?\[(.*)|\]/';
		if (preg_match_all($pattern, $query, $matches, PREG_SET_ORDER) !== 0) {
			$query = preg_replace($pattern, '', $query);
		}
		return $query;
	}

	/**
	 * Find and remove specific conditionals with name
	 * 
	 * @access static
	 * @param {string} $query Query to be formated
	 * @param {string} $name Name of conditional
	 * @return {string} $query
	 */
	public static function removeNamedConditional(&$query, $name = '') {
		$pattern = '/'.$name.':\[(.*)|\]/';
		if (preg_match_all($pattern, $query, $matches, PREG_SET_ORDER) !== 0) {
			$query = preg_replace($pattern, '', $query);
		}
		return $query;
	}

	/**
	 * Find and remove conditional characters
	 *
	 * @access static
	 * @param {string} $query Query to be formated
	 * @return {string} $query
	 */
	public static function removeConditionalParameter(&$query) {
		// (aaa:\[[a-zA-Z0-9_\.\s\w\t\<\>\(\)\:\=]+\])
		$pattern = '/(\S+:)?\[|\]/';
		if (preg_match_all($pattern, $query, $matches, PREG_SET_ORDER) !== 0) {
			$query = preg_replace($pattern, '', $query);
		}
		return $query;
	}

	/**
	 * Find query into array, setting the 'path' of array keys
	 * /[^\s]+(\.\w+)/ Match string concatenated by '.'
	 *
	 * @access public
	 * @param {string} $path Path of keys into array
	 * @return {array} $data 
	 */
	public function findQuery($path = '') {
		if (preg_match('/[^\s]+(\.\w+)/', $path, $matches) !== 0) {
			if ($this->getPrefixPath() != '' && strpos($this->getPrefixPath(), $path) === false) {
				$path = $this->getPrefixPath().'.'.$path;
			} 
			$parts = explode('.', $path);
			$data = $this->iterateData($parts, $this->_data);
			return $data;
		} 
		return array();
	}

	/**
	 * Method to iterate all data and retrieve query content with path of keys
	 *
	 * @access private
	 * @param {array} $parts Keys of data to get
	 * @param {array} $data Array with all content
	 * @return {mixed} Get all content with path iterated
	 */
	private function iterateData($parts, $data) {
		$qData = array();
		if (is_array($data) && count($data) > 0 && !empty($parts[0])) {
			$qData = array('source' => $data);
			foreach ($parts as $key) {
				if (!empty($qData['source'][$key])) {
					$qData['source'] = $qData['source'][$key];	
				} 
			}
		}	
		return $qData['source'];
	}

}