<?php

/**
 * LICENSE: This source code is subject to the license that is available
 * in the LICENSE file distributed along with this package.
 *
 * @package    Penelope
 * @author     Matthew Caruana Galizia <mcg@karwana.com>
 * @copyright  Karwana Ltd
 * @since      File available since Release 1.0.0
 */

namespace Karwana\Penelope\Tests;

use Everyman\Neo4j;

use Karwana\Penelope\Schema;
use Karwana\Penelope\NodeSchema;
use Karwana\Penelope\EdgeSchema;

class EdgeSchemaTest extends \PHPUnit_Framework_TestCase {

	public function getSchema() {
		static $schema;

		if ($schema) {
			return $schema;
		}

		$transport = new MockTransport();
		$schema = new Schema(new Neo4j\Client($transport));
		$schema->addNode('Person', 'people');
		$schema->addNode('Car', 'cars');
		$schema->addEdge('OWNER', 'owns', 'Person', 'Car');

		return $schema;
	}

	public function edgeSchemaProvider() {
		return array(array($this->getSchema()->getEdge('OWNER')));
	}


	/**
	 * @dataProvider edgeSchemaProvider
	 */
	public function testCreate_createsEdge($edge_schema) {
		$edge = $edge_schema->create();
		$this->assertInstanceOf('Karwana\Penelope\Edge', $edge);
		$this->assertEquals('OWNER', $edge->getSchema()->getName());
		$this->assertFalse($edge->hasId());
		$this->assertNull($edge->getId());
	}


	/**
	 * @dataProvider edgeSchemaProvider
	 */
	public function testGet_returnsEdge($edge_schema) {
		$transport = $edge_schema->getClient()->getTransport();

		$transport->pushResponse(200, array(), array('Person'));
		$transport->pushResponse(200, array(), array('Car'));
		$transport->pushResponse(200, array(), array(
			'start' => 'http://localhost:7474/db/data/node/1',
			'self' => 'http://localhost:7474/db/data/relationship/1',
			'type' => 'OWNER',
			'end' => 'http://localhost:7474/db/data/node/2',
			'metadata' => array(
				'id' => 1,
				'type' => 'OWNER'
			),
			'data' => array()
		));

		$edge = $edge_schema->get(1);

		$this->assertInstanceOf('Karwana\Penelope\Edge', $edge);
		$this->assertEquals(1, $edge->getId());

		$this->assertEquals(array(
			'method' => 'GET',
			'path' => '/node/1/labels',
			'data' => null), $transport->popRequest());

		$this->assertEquals(array(
			'method' => 'GET',
			'path' => '/node/2/labels',
			'data' => null), $transport->popRequest());

		$this->assertEquals(array(
			'method' => 'GET',
			'path' => '/relationship/1',
			'data' => null), $transport->popRequest());

		// No more requests.
		$this->assertNull($transport->popRequest());
	}


	/**
	 * @dataProvider edgeSchemaProvider
	 */
	public function testGet_throwsExceptionForInvalidId($edge_schema) {
		$this->setExpectedException('InvalidArgumentException', 'Invalid ID.');
		$edge_schema->get('hi');
	}


	/**
	 * @dataProvider edgeSchemaProvider
	 */
	public function testPermits_checksAllowedStartAndEndNodes($edge_schema) {
		$person_schema = $this->getSchema()->getNode('Person');
		$car_schema = $this->getSchema()->getNode('Car');

		$this->assertFalse($edge_schema->permits($person_schema, $person_schema));
		$this->assertFalse($edge_schema->permits($car_schema, $person_schema));
		$this->assertFalse($edge_schema->permits($car_schema, $car_schema));
		$this->assertTrue($edge_schema->permits($person_schema, $car_schema));
	}
}
