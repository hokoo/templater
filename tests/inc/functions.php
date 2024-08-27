<?php

/**
 * Replace spaces.
 */
function remove_spaces( string $string ): string {
	return preg_replace( '/\s+/', '', $string );
}

function remove_spaces_recursively( array $array ): array {
	array_walk_recursive( $array, function( &$value ) {
		$value = remove_spaces( $value );
	} );

	return $array;
}
