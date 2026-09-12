<?php

namespace iTRON\Anatomy;

class Templater {

	/**
	 * @param array<string, mixed> $data
	 */
	public function render(
		string $template,
		array $data
	): string {
		$engine = new Core(
			$template,
			$data
		);

		$this->defineContext( $data, $engine );

		return $engine->render();
	}

	/**
	 * @param array<string, mixed> $data
	 */
	public function renderBlock(
		string $template,
		string $blockName,
		array $data = []
	): string {
		$engine = new Core(
			$template,
			$data
		);

		$this->defineContext( $data, $engine );

		return $engine->render( $blockName );
	}

	/**
	 * @param array<string, mixed> $data
	 */
	protected function defineContext( array $data, Core $context ): static {
		/** @var \SplObjectStorage<Container, null> $seen */
		$seen = new \SplObjectStorage();

		foreach ( $data as $value ) {
			$this->bindContext( $value, $context, $seen );
		}

		return $this;
	}

	/**
	 * @param \SplObjectStorage<Container, null> $seen
	 */
	private function bindContext( mixed $value, Core $context, \SplObjectStorage $seen ): void {
		if ( $value instanceof Container ) {
			if ( $seen->contains( $value ) ) {
				return;
			}

			$seen->attach( $value );
			$value->setContext( $context );

			foreach ( $value as $item ) {
				if ( ! is_array( $item ) || ! array_key_exists( Core::DATA_SCHEMA_KEY, $item ) ) {
					throw new Exception\InvalidTemplateDataException(
						'A container item must use the Anatomy element schema.'
					);
				}

				$this->bindContext( $item[ Core::DATA_SCHEMA_KEY ], $context, $seen );
			}

			return;
		}

		if ( is_array( $value ) ) {
			foreach ( $value as $item ) {
				$this->bindContext( $item, $context, $seen );
			}
		}
	}
}
