<?php

if ($property->getSchema()->isMultiValue()) {
	foreach (array_merge($property->getValue(), array(null)) as $value) {

?>
<input id="<?php __($property_id); ?>" name="<?php __($property->getName()); ?>"<?php if ($value) { ?> value="<?php __datetime($value); ?>"<?php } ?>>
<?php

	}
} else {

?>
<input id="<?php __($property_id); ?>" name="<?php __($property->getName()); ?>"<?php if ($property->getValue()) { ?> value="<?php __datetime($property->getValue()); ?>"<?php } ?>>
<?php

}

?>
