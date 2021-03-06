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

namespace Karwana\Penelope\Controllers;

use Karwana\Penelope\Edge;
use Karwana\Penelope\EdgeCollection;
use Karwana\Penelope\Exceptions;
use Karwana\Penelope\TransientProperty;

class EdgeCollectionController extends ObjectCollectionController {

	public function getEdgesByParams($node_schema_slug, $node_id, $edge_schema_slug) {
		$node = $this->getNodeByParams($node_schema_slug, $node_id);

		// Check that:
		// - the edge schema with the given slug exists
		// - the edge schema defines relationships with nodes of the given node schema
		$edge_schema = $this->getEdgeSchemaBySlugs($node_schema_slug, $edge_schema_slug);

		try {

			// Don't use EdgeSchema#getCollection as we don't want to fetch as yet.
			$edges = new EdgeCollection($edge_schema, $node, EdgeCollection::OUT);

		// If the edge schema does not define relationships from nodes of the given type.
		} catch (Exceptions\SchemaException $e) {
			$this->app->notFound($e);
			$this->app->stop();
		}

		return $edges;
	}

	public function create($node_schema_slug, $node_id, $edge_schema_slug) {
		$app = $this->app;

		$edge_schema = $this->getEdgeSchemaBySlugs($node_schema_slug, $edge_schema_slug);
		$edge = $edge_schema->create();

		$transient_properties = array();
		$has_errors = false;

		$start_node = $this->getNodeByParams($node_schema_slug, $node_id);
		$end_node = $this->getNodeByParams($edge_schema->getEndNodeSchema()->getSlug(), $app->request->post('end_node'));

		try {
			$edge->setRelationShip($start_node, $end_node);
		} catch (Exceptions\SchemaException $e) {
			$this->renderNewForm($node_schema_slug, $node_id, $edge_schema_slug, $transient_properties, $e);
			return;
		}

		$this->processProperties($edge, $app->request->post(), $transient_properties, $has_errors);

		if ($has_errors) {
			$this->renderNewForm($node_schema_slug, $node_id, $edge_schema_slug, $transient_properties);
			return;
		}

		try {
			$edge->save();
		} catch (\Exception $e) {
			$this->renderNewForm($node_schema_slug, $node_id, $edge_schema_slug, $transient_properties, $e);
			return;
		}

		$view_data = array('title' => $this->_m('edge_created_title', $edge->getTitle()), 'edge' => $edge, 'node' => $start_node);
		$app->response->setStatus(201);
		$app->response->headers->set('Location', $edge->getPath());
		$app->render('edge_created', $view_data);
	}

	public function read($node_schema_slug, $node_id, $edge_schema_slug) {
		$node = $this->getNodeByParams($node_schema_slug, $node_id);

		$edge_schema = $this->getEdgeSchemaBySlugs($node_schema_slug, $edge_schema_slug);
		$edge_collection = $this->getEdgesByParams($node_schema_slug, $node_id, $edge_schema_slug);

		$view_data = $this->readPagedCollection($edge_collection);

		$view_data['title'] = $this->_m('edge_collection_title', $edge_schema->getDisplayName(), $node->getTitle());
		$view_data['node'] = $node;
		$view_data['edge_schema'] = $edge_schema;
		$view_data['edges'] = $edge_collection;

		$this->app->render('edges', $view_data);
	}

	public function renderNewForm($node_schema_slug, $node_id, $edge_schema_slug, array $transient_properties = null, \Exception $e = null) {
		$edge_schema = $this->getEdgeSchemaBySlugs($node_schema_slug, $edge_schema_slug);
		$node = $this->getNodeByParams($node_schema_slug, $node_id);

		$view_data = array('title' => $this->_m('new_edge_title', $edge_schema->getDisplayName(), $node->getTitle()), 'error' => $e);
		$view_data['node'] = $node;
		$view_data['properties'] = array();

		foreach ($edge_schema->getProperties() as $property_schema) {
			$property_name = $property_schema->getName();

			if (isset($transient_properties[$property_name])) {
				$transient_property = $transient_properties[$property_name];
			} else {
				$transient_property = new TransientProperty($property_schema);
			}

			$view_data['properties'][] = $transient_property;
		}

		$view_data['edge_schema'] = $edge_schema;

		// Get all the end nodes, without paging.
		// TODO: A more intelligent interface that doesn't involve searching potentially millions of nodes. Maybe a search that posts back to the form?
		$view_data['end_nodes'] = $edge_schema->getEndNodeSchema()->getCollection();

		if ($e) {
			$this->app->response->setStatus(500);
		} else if (!empty($transient_properties)) {
			$this->app->response->setStatus(422);
		}

		$this->app->render('edge_new', $view_data);
	}
}
