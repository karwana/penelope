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

abstract class Object {

	protected $client, $id, $schema, $client_object, $properties = array();

	private $got_properties;

	public function __construct(ObjectSchema $object_schema, $id = null, Neo4j\PropertyContainer $client_object = null) {
		$this->schema = $object_schema;
		$this->client = $object_schema->getClient();

		if ($client_object) {
			if ($client_object->getId() !== $id) {
				throw new \InvalidArgumentException('IDs do not match.');
			}

			$this->client_object = $client_object;
			$this->id = $id;
		} else if (is_int($id) or ctype_digit($id)) {
			$this->id = (int) $id;
		} else if ($id) {
			throw new \InvalidArgumentException('Invalid ID.');
		}
	}

	public function getId() {
		return $this->id;
	}

	public function hasId() {
		return !is_null($this->id);
	}

	public function getSchema() {
		return $this->schema;
	}

	public function getClient() {
		return $this->client;
	}

	public function getClientObject() {
		if (!$this->client_object) {
			$this->fetch();
		}

		return $this->client_object;
	}

	public function getTitle() {
		if (!$this->hasId()) {
			return $this->getDefaultTitle();
		}

		$option = $this->schema->getOption('format.title');
		if (!$option) {
			return $this->getDefaultTitle();
		}

		if (!is_callable($option)) {
			throw new \InvalidArgumentException('Option for "title" must be callable.');
		}

		$title = $option($this);
		if (!$title) {
			return $this->getDefaultTitle();
		}

		return $title;
	}

	public function getDefaultTitle() {
		if ($this->hasId()) {
			return $this->schema->getDisplayName() . ' #' . $this->getId();
		}

		return '';
	}

	public function getProperty($name) {
		if (!$this->schema->hasProperty($name)) {
			throw new \InvalidArgumentException('Unknown property "' . $name . '".');
		}

		if (!$this->got_properties) {
			$this->loadProperties();
		}

		return $this->properties[$name];
	}

	public function getProperties() {
		if (!$this->got_properties) {
			$this->loadProperties();
		}

		// Only return properties with values.
		// Use array_values because array_filter preserves keys.
		return array_values(array_filter($this->properties, function($property) {
			return $property->hasValue();
		}));
	}

	public function save() {

		// Fetch the object if it hasn't been fetched yet.
		$client_object = $this->getClientObject();
		foreach ($this->properties as $property) {
			$client_object->setProperty($property->getName(), $property->getSerializedValue());
		}

		$client_object->save();
		$this->id = $client_object->getId();
	}

	public function delete() {
		$client_object = $this->getClientObject();

		// TODO: Handle error when the object no longer exists because of a race condition.
		$client_object->delete();
		$this->id = null;
		$this->client_object = null;
	}

	private function loadProperties() {
		$this->got_properties = true;

		// Prefill with values from the server if available.
		if ($this->hasId() and !$this->client_object) {
			$this->fetch();
		}

		// Look up each property separately instead of using $object#getProperties.
		// That way the order of properties as defined on the schema is maintained :).
		foreach ($this->schema->getProperties() as $property_schema) {
			$property_name = $property_schema->getName();
			$property = new Property($property_schema);
			$this->properties[$property_name] = $property;

			if ($this->client_object and !is_null($value = $this->client_object->getProperty($property_name))) {
				$property->setSerializedValue($value);
			}
		}
	}
}
