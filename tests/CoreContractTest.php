<?php

use iTRON\Anatomy\Container;
use iTRON\Anatomy\Core;
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

	public function testRegularTagRendersAnEmptyArrayAsAnEmptyString(): void {
		$result = ( new Templater() )->render( '{{value}}', [ 'value' => [] ] );

		$this->assertSame( '', $result );
	}

	public function testRegularTagRendersASingleItemFlatArray(): void {
		$result = ( new Templater() )->render( '{{value}}', [ 'value' => [ 'only' ] ] );

		$this->assertSame( 'only', $result );
	}

	public function testRegularTagRendersAnEmptyContainerAsAnEmptyString(): void {
		$result = ( new Templater() )->render( '{{value}}', [ 'value' => new Container() ] );

		$this->assertSame( '', $result );
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

	public function testRegularTagRejectsANestedArrayAsTheFirstItem(): void {
		$this->expectException( InvalidTemplateDataException::class );

		( new Templater() )->render( '{{value}}', [ 'value' => [ [ 'nested' ], 'after' ] ] );
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

	/**
	 * @dataProvider invalidCustomDelimiterIndexProvider
	 */
	public function testCustomDelimiterFallsBackToTheFirstValue( mixed $modifier ): void {
		$result = ( new Templater() )->render(
			'{{#variant=[first!!second!!third] delimiter=[!!]}}',
			[ 'variant' => $modifier ]
		);

		$this->assertSame( 'first', $result );
	}

	public static function invalidCustomDelimiterIndexProvider(): array {
		return [
			'non-integer' => [ '1' ],
			'negative integer' => [ -1 ],
			'out-of-range integer' => [ 3 ],
		];
	}

	public function testEmptyCustomDelimiterUsesTheDefaultDelimiter(): void {
		$result = ( new Templater() )->render(
			'{{#variant=[first|second] delimiter=[]}}',
			[ 'variant' => 1 ]
		);

		$this->assertSame( 'second', $result );
	}

	public function testPredefinedTagAndDelimiterCanBeNamedZero(): void {
		$result = ( new Templater() )->render(
			'{{#0=[first0second] delimiter=[0]}}',
			[ 0 => 1 ]
		);

		$this->assertSame( 'second', $result );
	}

	public function testPredefinedTagFallsBackForNestedArrayModifier(): void {
		$result = ( new Templater() )->render(
			'{{#variant=[first|second]}}',
			[ 'variant' => [ [ 1 ] ] ]
		);

		$this->assertSame( 'first', $result );
	}

	public function testRegularEscapedAndPredefinedTagsCanBeInterleaved(): void {
		$result = ( new Templater() )->render(
			'{{raw}}|{{escaped|e}}|{{#variant=[first|second]}}|{{raw}}',
			[ 'raw' => '<b>raw</b>', 'escaped' => '<i>safe</i>', 'variant' => 1 ]
		);

		$this->assertSame(
			'<b>raw</b>|&lt;i&gt;safe&lt;/i&gt;|second|<b>raw</b>',
			$result
		);
	}

	public function testPrepareTemplateCanInitializeAnUnparsedCore(): void {
		$core = new Core( 'Before [[#card]]content[[/card]] after', [ 'unused' => true ] );

		$this->assertSame( $core, $core->prepareTemplate() );
		$this->assertSame( 'content', $core->render( 'card' ) );
	}

	public function testGetRenderedIsEmptyBeforeTheFirstRender(): void {
		$this->assertSame( '', ( new Core( 'template' ) )->getRendered() );
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

	public function testHardcodedBlockNamedZeroCanBeRenderedWithEmptyData(): void {
		$result = ( new Templater() )->renderBlock(
			'[[#0]]Zero block[[/0]]',
			'0'
		);

		$this->assertSame( 'Zero block', $result );
	}

	public function testStandaloneContainerCannotBeConvertedToAString(): void {
		$this->expectException( LogicException::class );

		(string) ( new Container() )->addText( 'orphaned' );
	}
}
