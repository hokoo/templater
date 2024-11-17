<?php

namespace iTRON\Anatomy;

class Container {
	protected array $data = [];

	public function __construct() {}

	public function addText( string $text ): static {
		$this->data[] = $this->getElementSchema( data: $text );

		return $this;
	}

	public function addBlock( string $name, array $data ): static {
		array_walk(
			$data,
			function ( &$value ) {
				$value = is_a( $value, static::class ) ? $value->toArray() : $value;
			}
		);

		$this->data[] = $this->getElementSchema( $name, $data );

		return $this;
	}

	protected function getElementSchema( string $blockName = '', $data = [] ): array {
		$schema = [];

		if ( ! empty( $blockName ) ) {
			$schema[ Core::BLOCK_NAME_SCHEMA_KEY ] = $blockName;
		}

		if ( ! empty( $data ) ) {
			$schema[ Core::DATA_SCHEMA_KEY ] = $data;
		}

		return $schema;
	}

	public function toArray(): array {
		return $this->data;
	}
}
