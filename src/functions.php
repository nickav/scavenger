<?php

/**
 * Returns the first element of the array with
 * @param  array $array   the array to search over
 * @param  array $keys    the array of key values to look for in array
 * @param  mixed $default the default value to return if none of the array keys are defined
 * @return mixed          the first value in array with key in keys or the default value
 */
function array_first_defined($array, $keys, $default = null) {
	foreach ($keys as $key) {
		if (isset($array[$key])) {
			return $array[$key];
		}
	}

	return $default;
}

function array_first_map($array, $map) {
	foreach ($map as $key => $array_keys) {
		$array[$key] = array_first_defined($array, $array_keys);
	}
}