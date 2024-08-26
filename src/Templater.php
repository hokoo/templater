<?php
/**
 * iTRON Templater
 */
namespace iTRON\Templater;

class Templater{

	private static string $_regex = '/\[\[(?P<tag>.+)\]\](?P<content>.+)\[\[\/(?P=tag)\]\]/mUs';
	private static string $_preselected_regex = '/\[\[(?P<values>.+)\/\]\](?P<index>(?-U)\d+)/mUs';
	private string $regex;
	private string $preselected_regex;
	private string $preselected_separator = '|';

	function __construct(){
		$this->set_regex( self::$_regex );
		$this->set_preselected_regex( self::$_preselected_regex );
	}

	public function set_preselected_regex( string $_preselected_regex ) {
		$this->preselected_regex = $_preselected_regex;
	}

	public function set_regex( $regex ): Templater {
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
	 * @param string $subject       template
	 * @param array $args           multidimensional indexed array of data for automatic replacement,
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
	 * @param boolean $invert       return processing only for the repeater specified in the first element of $args,
	 *                              ignoring all other repeaters and substitution arguments.
	 *
	 * @return string
	 */
	public function render( $subject, $args = [], $invert = false ){
		if ( empty( $args ) ) return $subject;

		$result = $this->get_repeaters( $subject );
		if ( empty( $result ) )
			return $invert ? $subject : $this->format( $subject, $args );

		$pre_result = str_replace( $result['data'][0], '', $subject );

		$replace = [];
		foreach( $args as $data ) :

			$replace []= $this->get_repeaters_data( $data, $result );

			if ( $invert ) break;
		endforeach;

		return $invert ? $replace[0] : $this->format( $pre_result, $replace );
	}

	private function format( $data, $args ): string {
		return $this->format_preselected_values( vsprintf( $data, $args ) );
	}

	private function format_preselected_values( string $subject ): string {
		$m = [];
		$result = $subject;
		
		preg_match_all( $this->preselected_regex, $subject, $m );

		if ( empty( $m[0] ) ) {
			return $result;
		}
		
		foreach ( $m[0] as $i => $found ) {
			$values = explode( $this->preselected_separator, $m['values'][$i] );
			$calculated = $values[ (int) $m['index'][$i] ] ?? $values[0];

			// Replace the preselected value with the calculated one. Replace the first occurrence only.
			$result = preg_replace( '/' . preg_quote( $found, '/' ) . '/', $calculated, $result, 1 );
		}

		return $result;
	}

	private static function format_esc( $format ){
		return str_replace( '%', '%%', $format );
	}

	/**
	 * Recursive function for finding nested repeaters.
	 * tag	    - names of found repeaters
	 * content	- list of repeater content, including nested ones
	 * clear	- list of repeater content without nested ones
	 */
	private function get_repeaters( $subject ){
		$m = [];
		preg_match_all( $this->regex, $subject, $m );
		if ( empty( $m[0] ) ) return false;

		$result = [
			'tag'		=> $m['tag'],
			'content'	=> $m['content'],
			'clear'		=> [],
		];
		foreach( $m['content'] as $found ):
			$inner = $this->get_repeaters( $found );
			if ( is_array( $inner ) ) :
				$result['tag'] = array_merge( $result['tag'], $inner['result']['tag'] );
				$result['content'] = array_merge( $result['content'], $inner['result']['content'] );
				$result['clear'] = array_merge( $result['clear'], [ str_replace( $inner['data'][0], '', $found ) ], $inner['result']['clear'] );
			else :
				$result['clear'] []= $found;
			endif;
		endforeach;

		return [ 'result' => $result, 'data' => $m ];
	}

	private function get_repeaters_data( $data, $context ){
		if ( is_array( $data ) ) :
			$substr = '';

			foreach( $data as $row ) :
				$content = $row['content'] ?? @$row['data'];
				if ( isset( $row['tag'] ) && ! empty( $content ) ) :
					if ( is_array( $content ) )
						foreach( $content as $i => $maybe_subarray ):
							if ( is_array( $maybe_subarray ) )
								$content[ $i ] = $this->get_repeaters_data( $maybe_subarray, $context );
						endforeach;
					$substr .= ( false !== $key = array_search ( $row['tag'], $context['result']['tag'] ) ) ?
						$this->format( $context['result']['clear'][ $key ], $content ) :
						( is_array( $content ) ? implode( '', $content ) : $content );
				elseif ( ! empty( $content ) ) :
					$substr .= ( is_array( $content ) ? implode( '', $content ) : $content );
				elseif ( ! empty( $row['tag'] ) ) :
					$substr .= ( false !== $key = array_search ( $row['tag'], $context['result']['tag'] ) ) ?
						$context['result']['clear'][ $key ] : '';
				endif;
			endforeach;
			$out = $substr;
		else :
			$out = $data;
		endif;

		return $out;
	}
}
