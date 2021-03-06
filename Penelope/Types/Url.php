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

namespace Karwana\Penelope\Types;

class Url extends Type {

	public static function isValid($value, array $options = null, &$message = null) {
		if (static::isEmpty($value)) {
			return true;
		}

		if (!filter_var($value, FILTER_VALIDATE_URL)) {
			$message = 'Invalid URL.';
			return false;
		}

		return true;
	}
}
