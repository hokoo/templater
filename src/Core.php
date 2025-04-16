<?php

namespace iTRON\Anatomy;

final class Core {
	const BLOCK_NAME_SCHEMA_KEY = 'block';
	const DATA_SCHEMA_KEY = 'data';

	private string $repeater_regex =
		'/\[\[#(?P<' . self::BLOCK_NAME_SCHEMA_KEY . '>.+)\]\](?P<' . self::DATA_SCHEMA_KEY . '>.+)\[\[\/(?P=' . self::BLOCK_NAME_SCHEMA_KEY . ')\]\]/mUs';

	private string $tag_regex = '/\{\{(?P<tag>[a-zA-Z\d_\-]*)\}\}/mUs';
	private string $predefined_regex = '/\{\{#(?P<tag>[a-zA-Z\d_\-]+)=\[(?P<values>.+)\]\s?(delimiter=\[(?P<delimiter>.+)\])*\}\}/mUs';
	private string $predefined_delimiter = '|';

	private array $extracted = [];
	private string $rendered = '';
	private string $preparedTemplate = '';

	public function __construct(
		public string $template,
		public array $data = []
	) {
	}

	public function render( string $singleBlock = '' ): string {
		if ( empty( $this->data ) && empty( $singleBlock) ) {
			return $this->template;
		}

		// Extract blocks from the template.
		$this->extractBlocks();

		// Cut repeater tags off from the template.
		$this->prepareTemplate();

		// Render global context.
		$this->rendered = $this->renderBlock( $singleBlock, $this->data );

		return $this->getRendered();
	}

	/**
	 * Build an array of repeaters found in given template
	 * and put it in $this->extracted.
	 *
	 * @see \iTRON\Templater\Templater::processBlocks()
	 *
	 * @return void
	 */
	public function extractBlocks(): void {
		$this->extracted = $this->processBlocks( $this->template );
	}

	private function processBlocks( $template ): bool|array {
		$m = [];
		preg_match_all( $this->repeater_regex, $template, $m );

		if ( empty( $m[0] ) ) {
			return [];
		}

		$result = [
			self::BLOCK_NAME_SCHEMA_KEY => $m[ self::BLOCK_NAME_SCHEMA_KEY ],
			self::DATA_SCHEMA_KEY       => $m[ self::DATA_SCHEMA_KEY ],
			'raw'                       => [],
		];

		foreach ( $m[ self::DATA_SCHEMA_KEY ] as $index => $found ):
			$inner = $this->processBlocks( $found );
			if ( is_array( $inner ) && ! empty( $inner ) ) :
				$result[ self::BLOCK_NAME_SCHEMA_KEY ] = array_merge(
					$result[ self::BLOCK_NAME_SCHEMA_KEY ],
					$inner['result'][ self::BLOCK_NAME_SCHEMA_KEY ]
				);

				$result[ self::DATA_SCHEMA_KEY ] = array_merge(
					$result[ self::DATA_SCHEMA_KEY ],
					$inner['result'][ self::DATA_SCHEMA_KEY ]
				);

				$result['raw'] = array_merge(
					$result['raw'],
					[ $m[ self::BLOCK_NAME_SCHEMA_KEY ][$index] => str_replace( $inner['rx_output'][0], '', $found ) ],
					$inner['result']['raw'],
				);
			else :
				$result['raw'][ $m[ self::BLOCK_NAME_SCHEMA_KEY ][$index] ] = $found;
			endif;
		endforeach;

		return [ 'result' => $result, 'rx_output' => $m ];
	}

	public function renderContainer( Container $container ): string {
		$result = '';
		foreach ( $container as $item ) {
			$block = $item[ self::BLOCK_NAME_SCHEMA_KEY ] ?? '';
			$data = $item[ self::DATA_SCHEMA_KEY ];

			if ( ! empty( $block ) ) {
				$result .= $this->renderBlock( $block, $data );
				continue;
			}

			$result .= (string) $data;
		}

		return $result;
	}

	private function renderBlock( string $blockName, array $data ): string {
		foreach ( $data as $key => $value ) {
			$data[ $key ] = (string) $value;
		}

		return $this->processTags( $blockName, $data );
	}

	/**
	 * Clears given template and prepare for filling.
	 *
	 * @return Core
	 */
	public function prepareTemplate(): Core {
		$this->preparedTemplate = str_replace(
			$this->extracted['rx_output'][0] ?? [],
			'',
			$this->template );

		return $this;
	}

	private function processTags( string $blockName, array $data ): string {
		$template = $blockName ? $this->extracted['result']['raw'][ $blockName ] : $this->preparedTemplate;

		$template = $this->processRegularTags( $template, $data );
		return $this->processPredefinedTags( $template, $data );
	}

	/**
	 * Performs replacing of regular tags.
	 *
	 * @param $template
	 * @param $data
	 *
	 * @return string
	 */
	private function processRegularTags( $template, $data ): string {
		return (string) preg_replace_callback(
			$this->tag_regex,
			function ( $matches ) use ( $data ) {
				$tag = $matches['tag'];

				if ( ! isset( $data[ $tag ] ) ) {
					return '';
				}

				// If value is an array, convert it to string
				if ( is_array( $data[ $tag ] ) ) {
					return implode(
						'',
						array_map(
							fn( $item ) => is_string( $item ) ? $item : '',
							(array) $data[ $tag ]
						)
					);
				}

				return (string) $data[ $tag ];
			},
			$template
		);
	}

	/**
	 * Performs replacing of named tags.
	 *
	 * @param string $template
	 * @param array $data
	 *
	 * @return string
	 */
	private function processPredefinedTags( string $template, array $data ): string {
		$m = [];
		preg_match_all( $this->predefined_regex, $template, $m );

		if ( empty( $m['tag'] ) ) {
			return $template;
		}

		foreach ( $m['tag'] as $index => $tag ) {
			$delimiter = $m['delimiter'][ $index ] ?: $this->predefined_delimiter;
			$values = explode( $delimiter, $m['values'][ $index ] );
			$value = $values[ (int) $data[ $tag ] ] ?? $values[0];
			$template = str_replace( $m[0][ $index ], $value, $template );
		}

		return $template;
	}

	public function getRendered(): string {
		return $this->rendered;
	}
}
