<?php

/**
 * Replace spaces.
 */
function remove_spaces( string $string ): string {
	return preg_replace( '/\s+/', '', $string );
}
