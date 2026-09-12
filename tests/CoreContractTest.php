<?php

use iTRON\Anatomy\Container;
use iTRON\Anatomy\Exception\InvalidTemplateDataException;
use iTRON\Anatomy\Exception\UnknownBlockException;
use iTRON\Anatomy\Templater;
use PHPUnit\Framework\TestCase;

class CoreContractTest extends TestCase {
	public function testEmptyDataReturnsTheOriginalTemplateWithoutProcessing(): void {
		$template = 'Before {{missing}} [[#unclosed]] After';

		$result = ( new Templater() )->render( $template, [] );

		$this->assertSame( $template, $result );
	}

	public function testMissingRegularTagRendersAsAnEmptyStringWhenDataIsNotEmpty(): void {
		$result = ( new Templater() )->render(
			'Before {{missing}} after {{present}}',
			[ 'present' => 'value' ]
		);

		$this->assertSame( 'Before  after value', $result );
	}

	/**
	 * @dataProvider supportedSingleValueProvider
	 */
	public function testRegularTagAcceptsSupportedSingleValues( mixed $value, string $expected ): void {
		$result = ( new Templater() )->render( '{{value}}', [ 'value' => $value ] );

		$this->assertSame( $expected, $result );
	}

	public static function supportedSingleValueProvider(): array {
		return [
			'string'          => [ 'text', 'text' ],
			'integer'         => [ 42, '42' ],
			'float'           => [ 1.5, '1.5' ],
			'boolean true'    => [ true, '1' ],
			'boolean false'   => [ false, '' ],
			'null'            => [ null, '' ],
			'stringable value' => [
				new class implements Stringable {
					public function __toString(): string {
						return 'stringable';
					}
				},
				'stringable',
			],
		];
	}

	public function testRegularTagConcatenatesAFlatArrayOfSupportedValues(): void {
		$stringable = new class implements Stringable {
			public function __toString(): string {
				return 'three';
			}
		};

		$result = ( new Templater() )->render(
			'{{value}}',
			[ 'value' => [ 'one', 2, null, $stringable, false, 4.5 ] ]
		);

		$this->assertSame( 'one2three4.5', $result );
	}

	public function testContainerInsideAFlatArrayReceivesTheRenderingContext(): void {
		$container = ( new Container() )->addText( 'nested container' );

		$result = ( new Templater() )->render(
			'{{value}}',
			[ 'value' => [ 'before ', $container, ' after' ] ]
		);

		$this->assertSame( 'before nested container after', $result );
	}

	public function testRegularTagRejectsNestedArrays(): void {
		$this->expectException( InvalidTemplateDataException::class );

		( new Templater() )->render( '{{value}}', [ 'value' => [ 'one', [ 'two' ] ] ] );
	}

	public function testRegularTagRejectsNonStringableObjects(): void {
		$this->expectException( InvalidTemplateDataException::class );

		( new Templater() )->render( '{{value}}', [ 'value' => new stdClass() ] );
	}

	public function testRegularTagRejectsResources(): void {
		$resource = fopen( 'php://memory', 'r' );

		try {
			$this->expectException( InvalidTemplateDataException::class );

			( new Templater() )->render( '{{value}}', [ 'value' => $resource ] );
		} finally {
			fclose( $resource );
		}
	}

	/**
	 * @dataProvider invalidPredefinedIndexProvider
	 */
	public function testInvalidPredefinedIndexFallsBackToTheFirstValue( array $data ): void {
		$result = ( new Templater() )->render(
			'{{#variant=[first|second|third]}}',
			$data
		);

		$this->assertSame( 'first', $result );
	}

	public static function invalidPredefinedIndexProvider(): array {
		return [
			'missing key'       => [ [ 'unrelated' => true ] ],
			'float'             => [ [ 'variant' => 1.0 ] ],
			'numeric string'    => [ [ 'variant' => '1' ] ],
			'non-numeric string' => [ [ 'variant' => 'invalid' ] ],
			'boolean'           => [ [ 'variant' => true ] ],
			'null'              => [ [ 'variant' => null ] ],
			'negative integer'  => [ [ 'variant' => -1 ] ],
			'out-of-range integer' => [ [ 'variant' => 3 ] ],
		];
	}

	/**
	 * @dataProvider validPredefinedIndexProvider
	 */
	public function testIntegerPredefinedIndexSelectsTheMatchingValue( int $index, string $expected ): void {
		$result = ( new Templater() )->render(
			'{{#variant=[first|second|third]}}',
			[ 'variant' => $index ]
		);

		$this->assertSame( $expected, $result );
	}

	public static function validPredefinedIndexProvider(): array {
		return [
			'first value'  => [ 0, 'first' ],
			'second value' => [ 1, 'second' ],
			'third value'  => [ 2, 'third' ],
		];
	}

	public function testPredefinedTagSupportsACustomDelimiter(): void {
		$result = ( new Templater() )->render(
			'{{#variant=[first!!second!!third] delimiter=[!!]}}',
			[ 'variant' => 2 ]
		);

		$this->assertSame( 'third', $result );
	}

	public function testRenderingAnUnknownBlockThrowsAnExplicitException(): void {
		$this->expectException( UnknownBlockException::class );

		( new Templater() )->renderBlock(
			'[[#known]]Known[[/known]]',
			'unknown'
		);
	}

	public function testHardcodedBlockCanBeRenderedWithEmptyData(): void {
		$result = ( new Templater() )->renderBlock(
			'[[#message]]Hardcoded message[[/message]]',
			'message'
		);

		$this->assertSame( 'Hardcoded message', $result );
	}

	public function testStandaloneContainerCannotBeConvertedToAString(): void {
		$this->expectException( LogicException::class );

		(string) ( new Container() )->addText( 'orphaned' );
	}
}
