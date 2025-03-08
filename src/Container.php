<?php

namespace iTRON\Anatomy;

use ArrayObject;

class Container extends ArrayObject {
	protected Core $context;

	public function addText( string $text ): static {
		$this->append( $this->getElementSchema( data: $text ) );

		return $this;
	}

	public function addBlock( string $name, array $data ): static {
		$this->append( $this->getElementSchema( $name, $data ) );

		return $this;
	}

	protected function getElementSchema( string $blockName = '', $data = [] ): array {
		$schema = [];

		if ( ! empty( $blockName ) ) {
			$schema[ Core::BLOCK_NAME_SCHEMA_KEY ] = $blockName;
		}

		$schema[ Core::DATA_SCHEMA_KEY ] = $data;

		return $schema;
	}

	public function getContext(): Core {
		return $this->context;
	}

	public function setContext( Core $context ): static {
		$this->context = $context;

		return $this;
	}

	public function __toString(): string {
		if ( empty( $this->getContext() ) ) {
			return '';
		}

		return $this->context->renderContainer( $this );
	}

	/**
	 * Convert the object to its array representation recursively.
	 */
	public function getArrayCopy(): array {
		$result = parent::getArrayCopy();

		foreach ( $result as $key => $value ) {
			if ( is_array( $value[ Core::DATA_SCHEMA_KEY ] ) )
			foreach ( $value[ Core::DATA_SCHEMA_KEY ] as $k => $v ) {
				if ( $v instanceof static ) {
					$result[ $key ][ Core::DATA_SCHEMA_KEY ][ $k ] = $v->getArrayCopy();
				}
			}
		}

		return $result;
	}
}
