<?php

namespace iTRON\Anatomy;

use ArrayObject;

class Container extends ArrayObject {
	public function addText( string $text ): static {
		$this->append( $this->getElementSchema( data: $text ) );

		return $this;
	}

	public function addBlock( string $name, array $data ): static {
		array_walk(
			$data,
			function ( &$value ) {
				$value = is_a( $value, static::class ) ? $value->getArrayCopy() : $value;
			}
		);

		$this->append( $this->getElementSchema( $name, $data ) );

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
}
