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

namespace Karwana\Penelope;

abstract class ObjectSchema {

	protected $name, $slug, $options, $property_schemas = array();

	public function __construct($name, $slug, array $properties, array $options = null) {
		$this->name = $name;
		$this->slug = $slug;
		$this->options = $options;

		// Syntax for properties:
		// array('my_property_1', 'my_property_2' => 'date', 'my_property_3' => array('type' => 'country', 'mode' => 'alpha-2'))
		foreach ($properties as $name => $property) {
			$this->defineProperty($name, $property);
		}
	}

	public function getOption($name) {
		if (isset($this->options[$name])) {
			return $this->options[$name];
		}
	}

	public function defineProperty($name, $property) {

		// Default to 'text' type.
		$type = 'text';
		$is_multi_value = false;
		$options = array();

		// Check for property with no type definition.
		if (is_int($name)) {
			$name = $property;
		} else if (is_string($property)) {
			$type = $property;
		} else if (is_array($property)) {
			if (!empty($property['type'])) {
				$type = $property['type'];
			}

			// If there are any other keys besides the type, set them as options.
			unset($property['type']);
			$options = $property;
		} else {
			throw new \InvalidArgumentException('Invalid property definition at index "' . $name . '".');
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
