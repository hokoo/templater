<?php
/**
 * iTRON Templater.
 *
 * This class is to provide a back compat for the old templating system (up to v3.x);
 * is not used in the new version;
 * does not support the new templating system;
 * is not maintained and will be removed in the future.
 */

namespace iTRON\Templater;

class Templater {

	private string $regex = '/\[\[(?P<tag>.+)\]\](?P<content>.+)\[\[\/(?P=tag)\]\]/mUs';
	private string $preselected_regex = '/\[\[(?P<values>.+)\/\]\](?P<index>(?-U)\d+)/mUs';
	private string $preselected_separator = '|';

	public function set_preselected_regex( string $_preselected_regex ): Templater {
		$this->preselected_regex = $_preselected_regex;

		return $this;
	}

	public function set_regex( string $regex ): Templater {
		$this->regex = $regex;

		return $this;
	}

	public function set_preselected_separator( string $separator ): Templater {
		$this->preselected_separator = $separator;

		return $this;
	}


	/**
	 * Loading an HTML template with named repeaters.
	 * Repeaters format:
	 *
	 * [[REPEATER_NAME]]repeater content with %s substitution arguments[[/REPEATER_NAME]]
	 *
	 * Function arguments:
	 *
	 * @param string $subject template
	 * @param array $args multidimensional indexed array of data for automatic replacement,
	 *                              where each element ($n => $data) corresponds to the order of substitution arguments
	 *                              from the original template (%[$n+1]$s => $data).
	 *
	 *                              The elements themselves are multidimensional arrays where
	 *                                  columns -
	 *                                      `tag`       =>  placeholder names
	 *                                      `content`   =>  indexed one-dimensional array of data for automatic
	 *                                                      replacement using the vsprintf()
	 *                                                      function inside the placeholder.
	 *                                  rows - the order of repeaters output.
	 * @param boolean $invert return processing only for the repeater specified in the first element of $args,
	 *                              ignoring all other repeaters and substitution arguments.
	 *
	 * @return string
	 */
	public function render( $subject, $args = [], $invert = false ) {
		if ( empty( $args ) ) {
			return $subject;
		}

		// Find all repeaters in the template.
		$repeaters = $this->get_repeaters( $subject );
		if ( empty( $repeaters ) ) {
			return $invert ? $subject : $this->format( $subject, $args );
		}

		// Cut repeater tags off from the template.
		$pre_result = str_replace( $repeaters['data'][0], '', $subject );

		$replace = [];
		foreach ( $args as $data_item ) :

			$replace [] = $this->get_repeaters_data( $data_item, $repeaters );

			if ( $invert ) {
				break;
			}
		endforeach;

		return $invert ? $replace[0] : $this->format( $pre_result, $replace );
	}

	private function format( $data, $args ): string {
		if ( ! is_array( $args ) ) {
			$args = [ $args ];
		}

		return $this->format_preselected_values( vsprintf( $data, $args ) );
	}

	private function format_preselected_values( string $subject ): string {
		$m      = [];
		$result = $subject;

		preg_match_all( $this->preselected_regex, $subject, $m );

		if ( empty( $m[0] ) ) {
			return $result;
		}

		foreach ( $m[0] as $i => $found ) {
			$values     = explode( $this->preselected_separator, $m['values'][ $i ] );
			$calculated = $values[ (int) $m['index'][ $i ] ] ?? $values[0];

			// Replace the preselected value with the calculated one. Replace the first occurrence only.
			$result = preg_replace( '/' . preg_quote( $found, '/' ) . '/', $calculated, $result, 1 );
		}

		return $result;
	}

	private static function format_esc( $format ) {
		return str_replace( '%', '%%', $format );
	}

	/**
	 * Recursive function for finding nested repeaters.
	 * tag        - names of found repeaters
	 * content    - list of repeater content, including nested ones
	 * clear    - list of repeater content without nested ones
	 */
	private function get_repeaters( $subject ) {
		$m = [];
		preg_match_all( $this->regex, $subject, $m );
		if ( empty( $m[0] ) ) {
			return false;
		}

		$result = [
			'tag'     => $m['tag'],
			'content' => $m['content'],
			'clear'   => [],
		];

		foreach ( $m['content'] as $index => $found ):
			$inner = $this->get_repeaters( $found );
			if ( is_array( $inner ) ) :
				$result['tag']     = array_merge( $result['tag'], $inner['result']['tag'] );
				$result['content'] = array_merge( $result['content'], $inner['result']['content'] );
				$result['clear']   = array_merge(
					$result['clear'],
					[ $m['tag'][$index] => str_replace( $inner['data'][0], '', $found ) ],
					$inner['result']['clear'],
				);
			else :
				$result['clear'][ $m['tag'][$index] ] = $found;
			endif;
		endforeach;

		return [ 'result' => $result, 'data' => $m ];
	}

	/**
	 * Renders a repeater content.
	 *
	 * @param $data
	 * @param $context
	 *
	 * @return string
	 */
	private function get_repeaters_data( $data, $context ) : string {
		if ( ! is_array( $data ) ) {
			return (string) $data;
		}

		$result = '';

		foreach ( $data as $row ) {
			$content = $row['content'] ?? @$row['data'];
			if ( isset( $row['tag'] ) && ! empty( $content ) ) {
				if ( is_array( $content ) ) {
					foreach ( $content as $i => $maybe_subarray ) {
						if ( is_array( $maybe_subarray ) ) {
							$content[ $i ] = $this->get_repeaters_data( $maybe_subarray, $context );
						}
					}
				}

				$result .= $this->format(
					$context['result']['clear'][ $row['tag'] ],
					$content
				);
			} elseif ( ! empty( $content ) ) {
				$result .= ( is_array( $content ) ? implode( '', $content ) : $content );
			} elseif ( ! empty( $row['tag'] ) ) {
				$result .= $context['result']['clear'][ $row['tag'] ];
			}
		}

		return $result;
	}
}
