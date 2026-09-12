<?php

namespace iTRON\Anatomy;

use ArrayObject;
use iTRON\Anatomy\Exception\InvalidTemplateDataException;
use LogicException;

/**
 * Container elements are validated at render/export time because ArrayObject's
 * public mutation API can insert arbitrary values.
 *
 * @extends ArrayObject<array-key, mixed>
 */
class Container extends ArrayObject {
	protected ?Core $context = null;

	public function addText( string $text ): static {
		$this->append( $this->getElementSchema( data: $text ) );

		return $this;
	}

	/**
	 * @param array<string, mixed> $data
	 */
	public function addBlock( string $name, array $data ): static {
		$this->append( $this->getElementSchema( $name, $data ) );

		return $this;
	}

	/**
	 * @return array{block?: string, data: mixed}
	 */
	protected function getElementSchema( string $blockName = '', mixed $data = [] ): array {
		$schema = [];

		if ( ! empty( $blockName ) ) {
			$schema[ Core::BLOCK_NAME_SCHEMA_KEY ] = $blockName;
		}

		$schema[ Core::DATA_SCHEMA_KEY ] = $data;

		return $schema;
	}

	public function getContext(): ?Core {
		return $this->context;
	}

	public function setContext( Core $context ): static {
		$this->context = $context;

		return $this;
	}

	public function __toString(): string {
		$context = $this->getContext();
		if ( null === $context ) {
			throw new LogicException( 'A container cannot be rendered before it is attached to a template context.' );
		}

		return $context->renderContainer( $this );
	}

	/**
	 * Convert the object to its array representation recursively.
	 */
	public function getArrayCopy(): array {
		$result = parent::getArrayCopy();

		foreach ( $result as $key => $value ) {
			if ( ! is_array( $value ) || ! array_key_exists( Core::DATA_SCHEMA_KEY, $value ) ) {
				throw new InvalidTemplateDataException( 'A container item must use the Anatomy element schema.' );
			}

			if ( ! is_array( $value[ Core::DATA_SCHEMA_KEY ] ) ) {
				continue;
			}

			foreach ( $value[ Core::DATA_SCHEMA_KEY ] as $k => $v ) {
				if ( $v instanceof static ) {
					$result[ $key ][ Core::DATA_SCHEMA_KEY ][ $k ] = $v->getArrayCopy();
				}
			}
		}

		return $result;
	}
}
