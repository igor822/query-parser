<?php
namespace QueryParser\Test;

use QueryParser\QueryParser;

require_once '../../vendor/autoload.php';

class QueryParserTest extends \PHPUnit_Framework_TestCase {

	protected $file;

	protected function setUp() {
		$this->file = '../../config/queries.yml';
	}

	public function testLoadFile() {
		$queryParser = new QueryParser($this->file);
		$this->assertNotEmpty($queryParser->getData());
		$this->assertInternalType('array', $queryParser->getData());

		return $queryParser;
	}

	/**
	 * @depends testLoadFile
	 */
	public function testGetQuery(QueryParser $queryParser = null) {
		$path = 'queries.user.login';
		$query = $queryParser->findQuery($path);

		$this->assertNotEmpty($query);
		$this->assertInternalType('array', $query);
		$this->assertNotEmpty($query['query']);
		$this->assertInternalType('string', $query['query']);

		return array('query' => $query['query'], 'parser' => $queryParser);
	}

	/**
	 * @depends testGetQuery
	 */
	public function testReplacementValuesInQuery(array $objs = array()) {
		$query = $objs['parser']->replaceValues($objs['query'], array('login' => 'teste'));
		var_dump($query);
		$this->assertNotEmpty($query);
		$this->assertFalse(strpos($query, '<login:str>'));
	}

	/**
	 * @depends testGetQuery
	 */
	public function testRemoveConditionals(array $objs = array()) {
		$query = $objs['parser']->replaceValues($objs['query']);
		
		$this->assertNotEmpty($query);
		$this->assertFalse(strpos($query, '['));
	}

}