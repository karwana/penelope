<?php

use Karwana\Penelope\Types\Image;

if ($property->getSchema()->isMultiValue()) {

	foreach ((array) $property->getValue() as $value) {

		// Nothing useful can be shown for invalid images, not even the original input.
		// The user will just have to attach the image again.
		if (!Image::isValid($value)) {
			continue;
		}

		$path = $value[Image::PATH_KEY];
		$size = getimagesize(Image::getSystemPath($path))[3];

?>
<img src="/uploads/<?php __($path); ?>" alt="" <?php __($size); ?>>
<input type="checkbox" name="<?php __($property->getName()); ?>[]" value="<?php __(Image::serialize($value)); ?>" checked>
<?php

	}

?>
<input type="file" id="<?php __($property_id); ?>" name="<?php __($property->getName()); ?>[]" class="new">
<?php

} else if ($property->hasValue() and Image::isValid($value = $property->getValue())) {

	$path = $value[Image::PATH_KEY];
	$size = getimagesize(Image::getSystemPath($path))[3];

?>
<img src="/uploads/<?php __($path); ?>" alt="" <?php __($size); ?>>
<input id="<?php __($property_id); ?>" type="checkbox" name="<?php __($property->getName()); ?>" value="<?php __(Image::serialize($value)); ?>" checked>
<?php

} else {

?>
<input id="<?php __($property_id); ?>" type="file" id="<?php __($property_id); ?>" name="<?php __($property->getName()); ?>" class="new">
<?php

}

?>