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

abstract class ObjectSchema {

	use OptionContainer;

	protected $client, $name, $slug, $property_schemas = array();

	public function __construct(Neo4j\Client $client, $name, $slug, array $properties = null, array $options = null) {
		$this->setOptions($options);

		$this->name = $name;
		$this->slug = $slug;
		$this->client = $client;

		if ($properties) {
			$this->defineProperties($properties);
		}
	}

	public function getClient() {
		return $this->client;
	}

	public function defineProperties(array $properties) {

		// Syntax for properties:
		// array('my_property_1', 'my_property_2' => 'date', 'my_property_3' => array('type' => 'country', 'mode' => 'alpha-2'))
		foreach ($properties as $name => $property) {

			// Properties with no name key, where the value is the name.
			if (is_int($name)) {
				$name = $property;
				$property = null;
			}

			$this->defineProperty($name, $property);
		}
	}

	public function defineProperty($name, $property = null) {

		// Default to 'text' type.
		$type = 'text';
		$is_multi_value = false;
		$options = array();

		if (!is_string($name)) {
			throw new \InvalidArgumentException('Invalid property definition.');
		}

		// Check for property with no type definition.
		if (is_string($property)) {
			$type = $property;
		} else if (is_array($property)) {
			if (!empty($property['type'])) {
				$type = $property['type'];
			}

			// If there are any other keys besides the type, set them as options.
			unset($property['type']);
			$options = $property;
		}

		if (substr($type, -2) === '[]') {
			$is_multi_value = true;
			$type = substr($type, 0, -2);
		}

		$this->property_schemas[$name] = new PropertySchema($name, $type, $is_multi_value, $options);
	}

	public function getSlug() {
		return $this->slug;
	}

	public function getName() {
		return $this->name;
	}

	public function getDisplayName($quantity = 1) {
		$option = $this->getOption('format.name');
		if (!$option) {
			return $this->getName();
		}

		if (!is_callable($option)) {
			return $option;
		}

		return $option($quantity);
	}

	public function hasProperty($name) {
		return isset($this->property_schemas[$name]);
	}

	public function getProperties() {
		return array_values($this->property_schemas);
	}

	public function getProperty($name) {
		if (!$this->hasProperty($name)) {
			throw new \InvalidArgumentException('Unknown property "' . $name . '".');
		}

		return $this->property_schemas[$name];
	}
}
