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

		$this->bindDataMap( $data, $context, $seen );

		return $this;
	}

	/**
	 * @param \SplObjectStorage<Container, null> $seen
	 */
	private function bindContext( mixed $value, Core $context, \SplObjectStorage $seen ): void {
		if ( $value instanceof Container ) {
			$this->bindContainer( $value, $context, $seen );
			return;
		}

		if ( is_array( $value ) ) {
			$this->bindFlatArray( $value, $context, $seen );
		}
	}

	/**
	 * @param \SplObjectStorage<Container, null> $seen
	 */
	private function bindContainer( Container $container, Core $context, \SplObjectStorage $seen ): void {
		if ( $seen->contains( $container ) ) {
			return;
		}

		$seen->attach( $container );
		$container->setContext( $context );

		foreach ( $container as $item ) {
			$this->bindContainerItem( $item, $context, $seen );
		}
	}

	/**
	 * @param \SplObjectStorage<Container, null> $seen
	 */
	private function bindContainerItem( mixed $item, Core $context, \SplObjectStorage $seen ): void {
		$item = $this->validateContainerItem( $item );
		$data = $item[ Core::DATA_SCHEMA_KEY ];

		if (
			isset( $item[ Core::BLOCK_NAME_SCHEMA_KEY ] )
			&& '' !== $item[ Core::BLOCK_NAME_SCHEMA_KEY ]
			&& is_array( $data )
		) {
			$this->bindDataMap( $data, $context, $seen );
			return;
		}

		$this->bindContext( $data, $context, $seen );
	}

	/**
	 * @return array{block?: mixed, data: mixed}
	 */
	private function validateContainerItem( mixed $item ): array {
		if ( ! is_array( $item ) || ! array_key_exists( Core::DATA_SCHEMA_KEY, $item ) ) {
			throw new Exception\InvalidTemplateDataException(
				'A container item must use the Anatomy element schema.'
			);
		}

		return $item;
	}

	/**
	 * @param array<array-key, mixed>              $data
	 * @param \SplObjectStorage<Container, null> $seen
	 */
	private function bindDataMap( array $data, Core $context, \SplObjectStorage $seen ): void {
		foreach ( $data as $value ) {
			$this->bindContext( $value, $context, $seen );
		}
	}

	/**
	 * @param array<array-key, mixed>              $values
	 * @param \SplObjectStorage<Container, null> $seen
	 */
	private function bindFlatArray( array $values, Core $context, \SplObjectStorage $seen ): void {
		foreach ( $values as $value ) {
			if ( is_array( $value ) ) {
				continue;
			}

			$this->bindContext( $value, $context, $seen );
		}
	}
}
