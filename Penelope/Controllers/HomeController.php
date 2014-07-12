<?php

/**
 * LICENSE: This source code is subject to the license that is available
 * in the LICENSE file distributed along with this package.
 *
 * @package    Penelope
 * @author     Matthew Caruana Galizia <mcg@karwana.com>
 * @copyright  Karwana Ltd
 * @version    1.0.0
 * @since      File available since Release 1.0.0
 */

namespace Karwana\Penelope\Controllers;

use Slim;
use Everyman\Neo4j;

use Karwana\Penelope\Schema;

class HomeController extends Controller {

	protected $schema;

	public function __construct(Neo4j\Client $client, Slim\Slim $app, Schema $schema) {
		parent::__construct($app);
		$this->client = $client;
		$this->schema = $schema;
	}

	public function read() {
		$view_data = array('title' => $this->_m('home_title'));

		$view_data['node_schemas'] = $this->schema->getNodes();

		// Sort by name.
		usort($view_data['node_schemas'], function($a, $b) {
			return strcmp($a->getName(), $b->getName());
		});


		$view_data['node_totals'] = array();

		// Get label totals.
		$query = new Neo4j\Cypher\Query($this->client, 'MATCH n RETURN DISTINCT count(labels(n)), labels(n);');
		foreach ($query->getResultSet() as $row) {
			if (!empty($row[1][0])) {
				$view_data['node_totals'][$row[1][0]] = $row[0];
			}
		}

		// Fill zeroes for schemas with no nodes.
		foreach ($view_data['node_schemas'] as $node_schema) {
			if (!isset($view_data['node_totals'][$node_schema->getName()])) {
				$view_data['node_totals'][$node_schema->getName()] = 0;
			}
		}

		$view_data['total_nodes'] = array_sum($view_data['node_totals']);

		$this->app->render('home', $view_data);
	}
}
