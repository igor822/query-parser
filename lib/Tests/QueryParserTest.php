<?php
namespace QueryParser\Test;

use QueryParser\QueryParser;

require_once '../../vendor/autoload.php';

class QueryParserTest extends \PHPUnit_Framework_TestCase {

	protected $file;

	protected function setUp() {
		$this->file = '../../config/';
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

	/**
	 * @depends testGetQuery
	 */
	public function testRemoveConditionalsOptional(array $objs = array()) {
		$queryParser = $objs['parser'];
		
		$query = $queryParser->findQuery('queries.company.list.query');

		$values = array(
			'id' => 1,
			//'id1' => 2,
		);
		$query = $queryParser->replaceValues($query, $values);

		$this->assertFalse(strpos($query, '['));

	}

	public function testGetQueryPathWithPrefix() {
		$queryParser = new QueryParser($this->file, array('prefix' => 'queries'));

		$queryWith = $queryParser->findQuery('queries.company.list.query');

		$this->assertNotEmpty($queryWith);

		$queryWithout = $queryParser->findQuery('company.list.query');

		$this->assertNotEmpty($queryWithout);

		$this->assertSame($queryWith, $queryWithout);
	}

	public function testGetDifferentResource() {
		$queryParser = new QueryParser($this->file, array('resource' => 'queries2'));

		$this->assertNotEmpty($queryParser->getData());
		$query = $queryParser->findQuery('teste.subteste');
		$this->assertEquals($query, 'SELECT * FROM teste');
	}

	public function testloadOneFile() {
		$queryParser = new QueryParser($this->file.'/queries.yml');
		$this->assertNotEmpty($queryParser->getData());
		$this->assertInternalType('array', $queryParser->getData());

		return $queryParser;
	}

	public function testInArrayValue() {
		$queryParser = new QueryParser($this->file);
		
		$query = $queryParser->findQuery('teste.teste_in_array');
		$values = array(
			'ids' => array(1, 2, 4, 5, 6)
		);
		$query = $queryParser->replaceValues($query, $values);
		var_dump($query);

		return $queryParser;
	}

}