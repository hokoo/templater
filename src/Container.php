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
	private bool $exporting = false;

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

		if ( '' !== $blockName ) {
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
		if ( $this->exporting ) {
			throw new InvalidTemplateDataException( 'Cyclic container reference detected.' );
		}

		$this->exporting = true;

		try {
			return $this->exportElements();
		} finally {
			$this->exporting = false;
		}
	}

	private function exportElements(): array {
		$result = parent::getArrayCopy();

		foreach ( $result as $key => $value ) {
			$result[ $key ] = $this->exportElement( $value );
		}

		return $result;
	}

	/**
	 * @return array{block?: mixed, data: mixed}
	 */
	private function exportElement( mixed $element ): array {
		if ( ! is_array( $element ) || ! array_key_exists( Core::DATA_SCHEMA_KEY, $element ) ) {
			throw new InvalidTemplateDataException( 'A container item must use the Anatomy element schema.' );
		}

		$element[ Core::DATA_SCHEMA_KEY ] = $this->exportValue( $element[ Core::DATA_SCHEMA_KEY ] );

		return $element;
	}

	private function exportValue( mixed $value ): mixed {
		if ( $value instanceof self ) {
			return $value->getArrayCopy();
		}

		return is_array( $value ) ? $this->exportData( $value ) : $value;
	}

	/**
	 * @param array<array-key, mixed> $data
	 *
	 * @return array<array-key, mixed>
	 */
	private function exportData( array $data ): array {
		foreach ( $data as $key => $value ) {
			if ( $value instanceof self ) {
				$data[ $key ] = $value->getArrayCopy();
			}
		}

		return $data;
	}
}
