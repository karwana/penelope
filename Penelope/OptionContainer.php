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

abstract class OptionContainer {

	protected $options;

	public function __construct(array $options = null) {
		$this->options = $options;
	}

	public function hasOption($name) {
		return isset($this->options[$name]);
	}

	public function getOption($name) {
		if ($this->hasOption($name)) {
			return $this->options[$name];
		}
	}

	public function getOptions() {
		return $this->options;
	}
}