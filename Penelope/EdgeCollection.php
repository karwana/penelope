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

namespace Karwana\Penelope;

use Everyman\Neo4j;

class EdgeCollection extends ObjectCollection {

	const IN = Neo4j\Relationship::DirectionIn;
	const OUT = Neo4j\Relationship::DirectionOut;
	const ALL = Neo4j\Relationship::DirectionAll;

	private $node, $direction;

	public function __construct(EdgeSchema $edge_schema, Node $node, $direction = self::ALL, array $properties = null) {
		if (!$node->hasId()) {
			throw new \InvalidArgumentException('Cannot get an edge collection from a node with no ID.');
		}

		$node_schema = $node->getSchema();

		switch ($direction) {
		case self::OUT:
			if (!$edge_schema->permitsStartNode($node_schema)) {
				throw new Exceptions\SchemaException('The schema for edges of type "' . $edge_schema->getName() . '" does not permit edges from nodes of type "' . $node_schema->getName() . '".');
			}

			break;

		case self::IN:
			if (!$edge_schema->permitsEndNode($node_schema)) {
				throw new Exceptions\SchemaException('The schema for edges of type "' . $edge_schema->getName() . '" does not permit edges to nodes of type "' . $node_schema->getName() . '".');
			}

			break;

		case self::ALL:
			if (!$edge_schema->permitsEndNode($node_schema) and !$edge_schema->permitsStartNode($node_schema)) {
				throw new Exceptions\SchemaException('The schema for edges of type "' . $edge_schema->getName() . '" does not permit edges to or from nodes of type "' . $node_schema->getName() . '".');
			}

			break;

		default:
			throw new \RuntimeException('Invalid direction: "' . $direction . '".');
		}

		$this->node = $node;
		$this->direction = $direction;
		parent::__construct($edge_schema, $properties);
	}

	protected function getQueryString($aggregate = null, array &$query_params) {
		switch ($this->direction) {
		case self::ALL:
			$direction = '-[o:%s]-';
			break;

		case self::OUT:
			$direction = '-[o:%s]->';
			break;

		case self::IN:
			$direction = '<-[o:%s]-';
			break;
		}

		$query_string = 'MATCH (a)' . sprintf($direction, $this->object_schema->getName()) . '(b)';

		$i = 0;
		$where_parts = array();
		foreach ((array) $this->properties as $name => $value) {
			$query_params['value_' . $i] = $value;
			$where_parts[] = 'ANY (m IN {value_' . $i . '} WHERE m IN o.' . $name . ')';
			$i++;
		}

		$query_string .= ' WHERE id(a) = ' . $this->node->getId();

		if (!empty($where_parts)) {
			$query_string .= ' ' . join(' AND ', $where_parts);
		}

		if ($aggregate) {
			$query_string .= ' RETURN ' . $aggregate . '(o)';
		} else {
			$query_string .= ' RETURN (o)';
		}

		// Order by only makes sense when not using aggregate.
		if (!$aggregate and ($order_by = $this->getOrderBy())) {
			$query_string .= ' ORDER BY b.' . join(', b.', (array) $order_by);
		}

		return $query_string;
	}
}
