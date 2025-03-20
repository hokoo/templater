<?php

namespace iTRON\Anatomy;

class Templater {

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

	public function renderBlock(
		string $template,
		string $blockName,
		array $data
	): string {
		$engine = new Core(
			$template,
			$data
		);

		$this->defineContext( $data, $engine );

		return $engine->render( $blockName );
	}

	protected function defineContext( array $data, Core $context ): static {
		foreach ( $data as $key => $value ) {
			if ( $value instanceof Container ) {
				$value->setContext( $context );
				$inner = (array) $value;

				$set = array_column( $inner, Core::DATA_SCHEMA_KEY );

				// Filter out empty or non-array values
				$set = array_filter( $set, 'is_array' );

				// We can afford to lose keys here as they are not used,
				// only values are important in order to set contexts.
				$set = array_map( fn( $item ) => array_values( $item ), $set );
				$this->defineContext( array_merge( ...$set ), $context );
			}
		}

		return $this;
	}
}
