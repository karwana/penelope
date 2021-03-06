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
use Karwana\Penelope\Node;

class NodeTest extends \PHPUnit_Framework_TestCase {

	public function schemaProvider($schema) {
		$transport = new MockTransport();

		$client = new Neo4j\Client($transport);
		$schema = new Schema($client);

		$schema->addNode('Person', 'people');
		$schema->addEdge('Friend', 'friends', 'Person', 'Person');

		$schema->addNode('Car', 'cars');
		$schema->addEdge('Owner', 'owners', 'Car', 'Person');

		return array(array($schema));
	}


	/**
	 * @dataProvider schemaProvider
	 */
	public function testGetClient_returnsClient($schema) {
		$node = new Node($schema->getNode('Person'));
		$this->assertInstanceOf('Everyman\Neo4j\Client', $node->getClient());
	}


	/**
	 * @dataProvider schemaProvider
	 */
	public function testGetDefaultTitle_returnsEmptyStringForNodeWithNoId($schema) {
		$node = new Node($schema->getNode('Person'));
		$this->assertEquals('', $node->getDefaultTitle());
		$this->assertEquals('', $node->getTitle());
	}


	/**
	 * @dataProvider schemaProvider
	 */
	public function testGetDefaultTitle_returnsTitleForNodeWithId($schema) {
		$node = new Node($schema->getNode('Person'), 1);
		$this->assertEquals('Person #1', $node->getDefaultTitle());
		$this->assertEquals('Person #1', $node->getTitle());
	}


	/**
	 * @dataProvider schemaProvider
	 */
	public function testGetTitle_returnsOptionalTitle($schema) {
		$node = new Node($schema->getNode('Person'), 1);
		$node->getSchema()->setOption('format.title', function(Node $node) {
			return 'Leila Guerriero';
		});
		
		$this->assertEquals('Person #1', $node->getDefaultTitle());
		$this->assertEquals('Leila Guerriero', $node->getTitle());
	}


	/**
	 * @dataProvider schemaProvider
	 */
	public function testGetTitle_returnsDefaultTitleIfOptionalTitleReturnsNothing($schema) {
		$node = new Node($schema->getNode('Person'), 1);
		$node->getSchema()->setOption('format.title', function(Node $node) {
			return false;
		});
		
		$this->assertEquals('Person #1', $node->getDefaultTitle());
		$this->assertEquals('Person #1', $node->getTitle());
	}


	/**
	 * @dataProvider schemaProvider
	 */
	public function testGetTitle_throwsIfOptionalTitleIsNotCallable($schema) {
		$node = new Node($schema->getNode('Person'), 1);
		$node->getSchema()->setOption('format.title', 'Leila Guerriero');

		$this->setExpectedException('InvalidArgumentException', 'Option for "title" must be callable.');
		$node->getTitle();
	}


	/**
	 * @dataProvider schemaProvider
	 */
	public function testGetPath_throwsForNodeWithNoId($schema) {
		$this->setExpectedException('LogicException', 'Cannot create path for node with no ID.');
		$node = $schema->getNode('Person')->create();
		$node->getPath();
	}


	/**
	 * @dataProvider schemaProvider
	 */
	public function testGetPath_returnsPath($schema) {
		$node_schema = $schema->getNode('Person');
		$node = new Node($node_schema, 1);
		$this->assertEquals('/people/1', $node->getPath());

		// Assert that no requests were made.
		$this->assertNull($schema->getClient()->getTransport()->popRequest());
	}


	/**
	 * @dataProvider schemaProvider
	 */
	public function testGetEditPath_throwsForNodeWithNoId($schema) {
		$this->setExpectedException('LogicException', 'Cannot create edit path for node with no ID.');
		$node = $schema->getNode('Person')->create();
		$node->getEditPath();
	}


	/**
	 * @dataProvider schemaProvider
	 */
	public function testGetEditPath_returnsPath($schema) {
		$node_schema = $schema->getNode('Person');
		$node = new Node($node_schema, 1);
		$this->assertEquals('/people/1/edit', $node->getEditPath());

		// Assert that no requests were made.
		$this->assertNull($schema->getClient()->getTransport()->popRequest());
	}


	/**
	 * @dataProvider schemaProvider
	 */
	public function testGetNewEdgePath_throwsForNodeWithNoId($schema) {
		$this->setExpectedException('LogicException', 'Cannot create new edge path for node with no ID.');
		$node = $schema->getNode('Person')->create();
		$node->getNewEdgePath($schema->getEdge('Friend'));
	}


	/**
	 * @dataProvider schemaProvider
	 */
	public function testGetNewEdgePath_throwsForUnrelatableSchema($schema) {
		$this->setExpectedException('LogicException', 'Cannot create new edge path for unrelatable node.');
		$node_schema = $schema->getNode('Person');
		$node = new Node($node_schema, 1);
		$node->getNewEdgePath($schema->getEdge('Owner'));
	}


	/**
	 * @dataProvider schemaProvider
	 */
	public function testGetNewEdgePath_returnsPath($schema) {
		$node_schema = $schema->getNode('Person');
		$node = new Node($node_schema, 1);
		$this->assertEquals('/people/1/friends/new', $node->getNewEdgePath($schema->getEdge('Friend')));

		// Assert that no requests were made.
		$this->assertNull($schema->getClient()->getTransport()->popRequest());
	}


	/**
	 * @dataProvider schemaProvider
	 */
	public function testGetEdgeCollectionPath_throwsForNodeWithNoId($schema) {
		$this->setExpectedException('LogicException', 'Cannot create edge collection path for node with no ID.');
		$node = $schema->getNode('Person')->create();
		$node->getEdgeCollectionPath($schema->getEdge('Friend'));
	}


	/**
	 * @dataProvider schemaProvider
	 */
	public function testGetEdgeCollectionPath_throwsForUnrelatableSchema($schema) {
		$this->setExpectedException('LogicException', 'Cannot create edge collection path for unrelatable node.');
		$node_schema = $schema->getNode('Person');
		$node = new Node($node_schema, 1);
		$node->getEdgeCollectionPath($schema->getEdge('Owner'));
	}


	/**
	 * @dataProvider schemaProvider
	 */
	public function testGetEdgeCollectionPath_returnsPath($schema) {
		$node_schema = $schema->getNode('Person');
		$node = new Node($node_schema, 1);
		$this->assertEquals('/people/1/friends/', $node->getEdgeCollectionPath($schema->getEdge('Friend')));

		// Assert that no requests were made.
		$this->assertNull($schema->getClient()->getTransport()->popRequest());
	}


	/**
	 * @dataProvider schemaProvider
	 */
	public function testGetProperty_throwsForUnknownProperty($schema) {
		$node_schema = $schema->getNode('Person');
		$node = new Node($node_schema, 1);
		$this->setExpectedException('InvalidArgumentException', 'Unknown property "name".');
		$node->getProperty('name');
	}


	/**
	 * @dataProvider schemaProvider
	 */
	public function testGetProperty_returnsProperty($schema) {
		$transport = $schema->getClient()->getTransport();
		$transport->pushResponse(200, array(), array('Person'));
		$transport->pushResponse(200, array(), array(
			'self' => 'http://localhost:7474/db/data/node/1',
			'metadata' => array('id' => 1, 'labels' => array('Person')),
			'data' => array('born' => 1964, 'name' => 'Keanu Reeves')
		));

		$node_schema = $schema->getNode('Person');
		$node_schema->defineProperty('name');
		$node = new Node($node_schema, 1);
		$this->assertEquals('Keanu Reeves', $node->getProperty('name')->getValue());

		$this->assertEquals(array(
			'method' => 'GET',
			'path' => '/node/1/labels',
			'data' => null
		), $transport->popRequest());

		$this->assertEquals(array(
			'method' => 'GET',
			'path' => '/node/1',
			'data' => null
		), $transport->popRequest());

		// No more requests.
		$this->assertNull($transport->popRequest());
	}


	/**
	 * @dataProvider schemaProvider
	 */
	public function testGetProperties_returnsProperties($schema) {
		$transport = $schema->getClient()->getTransport();
		$transport->pushResponse(200, array(), array('Person'));
		$transport->pushResponse(200, array(), array(
			'self' => 'http://localhost:7474/db/data/node/1',
			'metadata' => array('id' => 1, 'labels' => array('Person')),
			'data' => array('born' => '1964', 'name' => 'Keanu Reeves')
		));

		$node_schema = $schema->getNode('Person');
		$node_schema->defineProperty('name');
		$node_schema->defineProperty('born');
		$node = new Node($node_schema, 1);

		$properties = $node->getProperties();
		$this->assertCount(2, $properties);
		$this->assertEquals('1964', $properties[1]->getValue());
		$this->assertEquals('Keanu Reeves', $properties[0]->getValue());

		$this->assertEquals(array(
			'method' => 'GET',
			'path' => '/node/1/labels',
			'data' => null
		), $transport->popRequest());

		$this->assertEquals(array(
			'method' => 'GET',
			'path' => '/node/1',
			'data' => null
		), $transport->popRequest());

		// No more requests.
		$this->assertNull($transport->popRequest());
	}


	/**
	 * @dataProvider schemaProvider
	 */
	public function testFetch_throwsForNodeWithNoId($schema) {
		$this->setExpectedException('LogicException', 'Cannot fetch without ID.');
		$node = $schema->getNode('Person')->create();
		$node->fetch();
	}


	/**
	 * @dataProvider schemaProvider
	 */
	public function testFetch_throwsForNodeWithUnknownId($schema) {
		$transport = $schema->getClient()->getTransport();
		$transport->pushResponse(404, array(), array(
			'message' => 'Cannot find node with id [1] in database.',
			'exception' => 'NodeNotFoundException',
			'fullname' => 'org.neo4j.server.rest.web.NodeNotFoundException',
			'stacktrace' => array()
		));

		$node_schema = $schema->getNode('Person');

		$this->setExpectedException('Karwana\Penelope\Exceptions\NotFoundException', 'No node with ID "1"');
		$node_schema->get(1);
	}


	/**
	 * @dataProvider schemaProvider
	 */
	public function testFetch_throwsForNodeWithMismatchingId($schema) {
		$transport = $schema->getClient()->getTransport();
		$transport->pushResponse(200, array(), array('Person'));
		$transport->pushResponse(200, array(), array(
			'columns' => array('n'),
			'data' => array(
				array(array(
					'self' => 'http://localhost:7474/db/data/node/1',
					'metadata' => array('id' => 1, 'labels' => array('Person')),
					'data' => array('born' => 1964, 'name' => 'Keanu Reeves')
				))
			))
		);

		$node_schema = $schema->getNode('Car');

		$this->setExpectedException('Karwana\Penelope\Exceptions\SchemaException', 'Node with ID "1" does not match schema "Car".');
		$node_schema->get(1);
	}


	/**
	 * @dataProvider schemaProvider
	 */
	public function testFetch_returnsClientNode($schema) {
		$transport = $schema->getClient()->getTransport();
		$transport->pushResponse(200, array(), array('Person'));
		$transport->pushResponse(200, array(), array(
			'columns' => array('n'),
			'data' => array(
				array(array(
					'self' => 'http://localhost:7474/db/data/node/1',
					'metadata' => array('id' => 1, 'labels' => array('Person')),
					'data' => array('born' => 1964, 'name' => 'Keanu Reeves')
				))
			))
		);

		$node = new Node($schema->getNode('Person'), 1);
		$client_object = $node->fetch();

		$this->assertInstanceOf('Everyman\\Neo4j\Node', $client_object);
		$this->assertEquals($node->getId(), $client_object->getId());

		$this->assertEquals(array(
			'method' => 'GET',
			'path' => '/node/1/labels',
			'data' => null
		), $transport->popRequest());

		$this->assertEquals(array(
			'method' => 'GET',
			'path' => '/node/1',
			'data' => null
		), $transport->popRequest());

		// No more requests.
		$this->assertNull($transport->popRequest());
	}


	/**
	 * @dataProvider schemaProvider
	 */
	public function testConstructor_throwsWhenPassedClientObjectWithMismatchingId($schema) {
		$transport = $schema->getClient()->getTransport();
		$transport->pushResponse(200, array(), array('Person'));
		$transport->pushResponse(200, array(), array(
			'columns' => array('n'),
			'data' => array(
				array(array(
					'self' => 'http://localhost:7474/db/data/node/1',
					'metadata' => array('id' => 1, 'labels' => array('Person')),
					'data' => array('born' => 1964, 'name' => 'Keanu Reeves')
				))
			))
		);

		$node = new Node($schema->getNode('Person'), 1);
		$client_object = $node->fetch();

		$this->assertInstanceOf('Everyman\\Neo4j\Node', $client_object);
		$this->assertEquals($node->getId(), $client_object->getId());

		$this->assertEquals(array(
			'method' => 'GET',
			'path' => '/node/1/labels',
			'data' => null
		), $transport->popRequest());

		$this->assertEquals(array(
			'method' => 'GET',
			'path' => '/node/1',
			'data' => null
		), $transport->popRequest());

		// No more requests.
		$this->assertNull($transport->popRequest());

		$this->setExpectedException('InvalidArgumentException', 'IDs do not match.');
		new Node($schema->getNode('Person'), 2, $client_object);
	}


	/**
	 * @dataProvider schemaProvider
	 */
	public function testConstructor_throwsWhenPassedInvalidId($schema) {
		$this->setExpectedException('InvalidArgumentException', 'Invalid ID.');
		new Node($schema->getNode('Person'), 'hi');
	}


	/**
	 * @dataProvider schemaProvider
	 */
	public function testConstructor_throwsWhenPassedClientNodeWithMismatchingLabels($schema) {
		$transport = $schema->getClient()->getTransport();

		// Response for labels.
		$transport->pushResponse(200, array(), array('Person'));

		// Response for node.
		$transport->pushResponse(200, array(), array(
			'columns' => array('n'),
			'data' => array(
				array(array(
					'self' => 'http://localhost:7474/db/data/node/1',
					'metadata' => array('id' => 1, 'labels' => array('Person')),
					'data' => array('born' => 1964, 'name' => 'Keanu Reeves')
				))
			))
		);

		$node = new Node($schema->getNode('Person'), 1);
		$client_object = $node->fetch();

		$this->setExpectedException('Karwana\Penelope\Exceptions\SchemaException', 'Node does not match schema "Car".');
		new Node($schema->getNode('Car'), 1, $client_object);

		// Request for labels.
		$this->assertEquals(array(
			'method' => 'GET',
			'path' => '/node/1/labels',
			'data' => null
		), $transport->popRequest());

		// Request for node.
		$this->assertEquals(array(
			'method' => 'GET',
			'path' => '/node/1',
			'data' => null
		), $transport->popRequest());
	}


	/**
	 * @dataProvider schemaProvider
	 */
	public function testGetOutEdges_throwsForInvalidSchema($schema) {
		$transport = $schema->getClient()->getTransport();

		// Response for labels.
		$transport->pushResponse(200, array(), array('Car'));

		// Response for node.
		$transport->pushResponse(200, array(), array(
			'columns' => array('n'),
			'data' => array(
				array(array(
					'self' => 'http://localhost:7474/db/data/node/1',
					'metadata' => array('id' => 1, 'labels' => array('Car')),
					'data' => array()
				))
			))
		);

		$car_node = new Node($schema->getNode('Car'), 1);
		$car_node->fetch();

		// Request for labels.
		$this->assertEquals(array(
			'method' => 'GET',
			'path' => '/node/1/labels',
			'data' => null
		), $transport->popRequest());

		// Request for node.
		$this->assertEquals(array(
			'method' => 'GET',
			'path' => '/node/1',
			'data' => null
		), $transport->popRequest());

		$this->assertNull($transport->popRequest());

		$friend_edge_schema = $schema->getEdge('Friend');

		$this->setExpectedException('Karwana\Penelope\Exceptions\SchemaException', 'The schema for edges of type "Friend" does not permit edges from nodes of type "Car".');

		$car_node->getOutEdges($friend_edge_schema);
	}


	/**
	 * @dataProvider schemaProvider
	 */
	public function testGetInEdges_throwsForInvalidSchema($schema) {
		$transport = $schema->getClient()->getTransport();

		// Response for labels.
		$transport->pushResponse(200, array(), array('Car'));

		// Response for node.
		$transport->pushResponse(200, array(), array(
			'columns' => array('n'),
			'data' => array(
				array(array(
					'self' => 'http://localhost:7474/db/data/node/1',
					'metadata' => array('id' => 1, 'labels' => array('Car')),
					'data' => array()
				))
			))
		);

		$car_node = new Node($schema->getNode('Car'), 1);
		$car_node->fetch();

		// Request for labels.
		$this->assertEquals(array(
			'method' => 'GET',
			'path' => '/node/1/labels',
			'data' => null
		), $transport->popRequest());

		// Request for node.
		$this->assertEquals(array(
			'method' => 'GET',
			'path' => '/node/1',
			'data' => null
		), $transport->popRequest());

		$this->assertNull($transport->popRequest());

		$friend_edge_schema = $schema->getEdge('Friend');

		$this->setExpectedException('Karwana\Penelope\Exceptions\SchemaException', 'The schema for edges of type "Friend" does not permit edges to nodes of type "Car".');

		$car_node->getInEdges($friend_edge_schema);
	}


	/**
	 * @dataProvider schemaProvider
	 */
	public function testGetEdges_throwsForInvalidSchema($schema) {
		$transport = $schema->getClient()->getTransport();

		// Response for labels.
		$transport->pushResponse(200, array(), array('Car'));

		// Response for node.
		$transport->pushResponse(200, array(), array(
			'columns' => array('n'),
			'data' => array(
				array(array(
					'self' => 'http://localhost:7474/db/data/node/1',
					'metadata' => array('id' => 1, 'labels' => array('Car')),
					'data' => array()
				))
			))
		);

		$car_node = new Node($schema->getNode('Car'), 1);
		$car_node->fetch();

		// Request for labels.
		$this->assertEquals(array(
			'method' => 'GET',
			'path' => '/node/1/labels',
			'data' => null
		), $transport->popRequest());

		// Request for node.
		$this->assertEquals(array(
			'method' => 'GET',
			'path' => '/node/1',
			'data' => null
		), $transport->popRequest());

		$this->assertNull($transport->popRequest());

		$friend_edge_schema = $schema->getEdge('Friend');

		$this->setExpectedException('Karwana\Penelope\Exceptions\SchemaException', 'The schema for edges of type "Friend" does not permit edges to or from nodes of type "Car".');

		$car_node->getEdges($friend_edge_schema);
	}


	/**
	 * @dataProvider schemaProvider
	 */
	public function testGetEdges_returnsEdges($schema) {
		$transport = $schema->getClient()->getTransport();
		$person_schema = $schema->getNode('Person');
		$friend_edge_schema = $schema->getEdge('Friend');

		// Recreate the object from scratch.
		$node = new Node($person_schema, 1);

		// Response for labels.
		$transport->pushResponse(200, array(), array('Person'));
		$transport->pushResponse(200, array(), array('Person'));

		// Response for relationship.
		$transport->pushResponse(200, array(), array(
			'columns' => array('o'),
			'data' => array(
				array(array(
					'self' => 'http://localhost:7474/db/data/relationship/1',
					'property' => 'http://localhost:7474/db/data/relationship/1/properties/{key}',
					'properties' => 'http://localhost:7474/db/data/relationship/1/properties',
					'start' => 'http://localhost:7474/db/data/node/1',
					'end' => 'http://localhost:7474/db/data/node/2',
					'extensions' => array(),
					'type' => 'Friend',
					'metadata' => array('id' => 1, 'type' =>'Friend'),
					'data' => array()
				))
			))
		);

		$edges = $node->getEdges($friend_edge_schema);

		$this->assertEquals(array(
			'method' => 'POST',
			'path' => 'cypher',
			'data' => array(
				'query' => 'MATCH (a)-[o:Friend]-(b) WHERE id(a) = 1 RETURN (o)'
			)
		), $transport->popRequest());

		// No more requests.
		$this->assertNull($transport->popRequest());

		$this->assertCount(1, $edges);

		$edge = $edges[0];

		// Requests for labels.
		$this->assertEquals(array(
			'method' => 'GET',
			'path' => '/node/1/labels',
			'data' => null
		), $transport->popRequest());

		$this->assertEquals(array(
			'method' => 'GET',
			'path' => '/node/2/labels',
			'data' => null
		), $transport->popRequest());

		$this->assertEquals(1, $edge->getId());
		$this->assertEquals('Friend', $edge->getSchema()->getName());
		$this->assertEquals(1, $edge->getStartNode()->getId());
		$this->assertEquals(2, $edge->getEndNode()->getId());

		// No more requests.
		$this->assertNull($transport->popRequest());
	}


	/**
	 * @dataProvider schemaProvider
	 */
	public function testGetEdges_throwsOnInvalidDirection($schema) {
		$transport = $schema->getClient()->getTransport();

		// Response for labels.
		$transport->pushResponse(200, array(), array('Person'));

		// Response for node.
		$transport->pushResponse(200, array(), array(
			'columns' => array('n'),
			'data' => array(
				array(array(
					'self' => 'http://localhost:7474/db/data/node/1',
					'metadata' => array('id' => 1, 'labels' => array('Person')),
					'data' => array()
				))
			))
		);

		$person_node = new Node($schema->getNode('Person'), 1);
		$person_node->fetch();

		// Request for labels.
		$this->assertEquals(array(
			'method' => 'GET',
			'path' => '/node/1/labels',
			'data' => null
		), $transport->popRequest());

		// Request for node.
		$this->assertEquals(array(
			'method' => 'GET',
			'path' => '/node/1',
			'data' => null
		), $transport->popRequest());

		$this->assertNull($transport->popRequest());

		$edge_schema = $schema->getEdge('Friend');

		$direction = 'nothing';
		$this->setExpectedException('RuntimeException', 'Invalid direction: "' . $direction . '".');

		$person_node->getEdges($edge_schema, $direction);
	}


	/**
	 * @dataProvider schemaProvider
	 */
	public function testSave_savesNode($schema) {
		$transport = $schema->getClient()->getTransport();

		$node_schema = $schema->getNode('Person');
		$node = $node_schema->create();
		$this->assertNull($node->getId());

		$node_schema->defineProperties(array('born', 'name', 'addresses' => array('type' => 'text[]')));

		// Add a property with indexing disabled.
		$node_schema->defineProperty('password', array('index.ignore' => true));

		// Response for saving the updated index.
		$transport->pushResponse(201, array(), array(
			'template' => 'http://localhost:7474/db/data/index/node/full_text/{key}/{value}',
			'type' => 'fulltext',
			'provider' => 'lucene'
		));

		// Response for adding data to the index.
		$transport->pushResponse(201, array(), array(
			'self' => 'http://localhost:7474/db/data/node/1',
			'metadata' => array(
				'id' => 1,
				'labels' => ['Person']
			),
			'data' => array(),
			'indexed' => "http://localhost:7474/db/data/index/node/full_text/full_text/hi/1"
		));

		// Response for creating the index.
		$transport->pushResponse(201, array(), array(
			'template' => 'http://localhost:7474/db/data/index/node/full_text/{key}/{value}',
			'type' => 'fulltext',
			'provider' => 'lucene'
		));

		// Response for addLabels call.
		$transport->pushResponse(200, array(), array(
			'columns' => array('labels'),
			'data' => array(array(array('Person')))
		));

		// Response for creating the node.
		$transport->pushResponse(200, array('Location' => '/node/1'), array(
			'columns' => array('n'),
			'data' => array(
				array(array(
					'self' => 'http://localhost:7474/db/data/node/1',
					'metadata' => array('id' => 1, 'labels' => array()),
					'data' => array('born' => 1964, 'name' => 'Keanu Reeves')
				))
			))
		);

		$node->getProperty('born')->setValue('1964');
		$node->getProperty('name')->setValue('Keanu Reeves');

		// Add one multivalue property to test logic for saving this to the index.
		$node->getProperty('addresses')->setValue(array('Malta', 'Costa Rica'));
		$node->save();

		// Request for saving the update index.
		$this->assertEquals(array(
			'method' => 'POST',
			'path' => '/index/node',
			'data' => array(
				'name' => 'full_text',
				'config' => array('type' => 'fulltext', 'provider' => 'lucene')
			)
		), $transport->popRequest());

		// Request for adding data to the index.
		$this->assertEquals(array(
			'method' => 'POST',
			'path' => '/index/node/full_text',
			'data' => array(
				'uri' => 'http://localhost:7474/db/data/node/1',
				'key' => 'full_text',
				'value' => '1964 Keanu Reeves Malta Costa Rica'
			)
		), $transport->popRequest());

		// Request for creating the index.
		$this->assertEquals(array(
			'method' => 'POST',
			'path' => '/index/node',
			'data' => array(
				'name' => 'full_text',
				'config' => array('type' => 'fulltext', 'provider' => 'lucene'
			)
		)), $transport->popRequest());

		// Request for adding the label.
		$this->assertEquals(array(
			'method' => 'POST',
			'path' => 'cypher',
			'data' => array(
				'query' => 'START n=node({nodeId}) SET n:`Person` RETURN labels(n) AS labels',
				'params' => array(
					'nodeId' => 1
				)
			)
		), $transport->popRequest());

		// Request for creating the node.
		$this->assertEquals(array(
			'method' => 'POST',
			'path' => '/node',
			'data' => array(
				'born' => '1964',
				'name' => 'Keanu Reeves',
				'addresses' => array('Malta', 'Costa Rica')
			)
		), $transport->popRequest());

		// No more requests.
		$this->assertNull($transport->popRequest());
		$this->assertNotNull($node->getId());
	}


	/**
	 * @dataProvider schemaProvider
	 */
	public function testDelete_deletesNode($schema) {
		$node_schema = $schema->getNode('Person');
		$node = new Node($node_schema, 1);
		$this->assertEquals(1, $node->getId());

		$transport = $node_schema->getClient()->getTransport();

		// Response for deleting the node.
		$transport->pushResponse(204);

		// Response for deleting the relationship.
		$transport->pushResponse(204);

		// Response for getting relationships.
		$transport->pushResponse(200, array(), array(
			array(
				'start' => 'http://localhost:7474/db/data/node/1',
				'self' => 'http://localhost:7474/db/data/relationship/1',
				'type' => 'DIRECTED',
				'end' => 'http://localhost:7474/db/data/node/2',
				'metadata' => array(
					'id' => 1,
					'type' => 'DIRECTED'
				),
				'data' => array()
			)
		));

		// Response for saving the index.
		$transport->pushResponse(201, array('Location' => '/index/node/full_text/'), array(
			'template' => 'http://localhost:7474/db/data/index/node/full_text/{key}/{value}',
			'type' => 'fulltext',
			'provider' => 'lucene'
		));

		// Response for deleting the node from the index.
		$transport->pushResponse(204);

		// Response for getting the node labels.
		$transport->pushResponse(200, array(), array('Person'));

		// Response for getting the node.
		$transport->pushResponse(200, array(), array(
			'columns' => array('n'),
			'data' => array(
				array(array(
					'self' => 'http://localhost:7474/db/data/node/1',
					'metadata' => array('id' => 1, 'labels' => array('Person')),
					'data' => array('born' => 1964, 'name' => 'Keanu Reeves')
				))
			))
		);

		$node->delete();
		$this->assertNull($node->getId());

		// Request for deleting the node.
		$this->assertEquals(array(
			'method' => 'DELETE',
			'path' => '/node/1',
			'data' => array()
		), $transport->popRequest());

		// Request for deleting the relationship.
		$this->assertEquals(array(
			'method' => 'DELETE',
			'path' => '/relationship/1',
			'data' => array()
		), $transport->popRequest());

		// Request for getting relationships.
		$this->assertEquals(array(
			'method' => 'GET',
			'path' => '/node/1/relationships/all',
			'data' => null
		), $transport->popRequest());

		// Request for saving the index.
		$this->assertEquals(array(
			'method' => 'POST',
			'path' => '/index/node',
			'data' => array(
				'name' => 'full_text',
				'config' => array(
					'type' => 'fulltext',
					'provider' => 'lucene'
				)
			)
		), $transport->popRequest());

		// Request for deleting the node from the index.
		$this->assertEquals(array(
			'method' => 'DELETE',
			'path' => '/index/node/full_text/1',
			'data' => array()
		), $transport->popRequest());

		// Request for getting the node labels.
		$this->assertEquals(array(
			'method' => 'GET',
			'path' => '/node/1/labels',
			'data' => null
		), $transport->popRequest());

		// Request for getting the node.
		$this->assertEquals(array(
			'method' => 'GET',
			'path' => '/node/1',
			'data' => null
		), $transport->popRequest());

		// No more requests.
		$this->assertNull($transport->popRequest());
	}
}
