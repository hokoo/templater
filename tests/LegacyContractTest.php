<?php

use iTRON\Templater\Templater;
use PHPUnit\Framework\TestCase;

class LegacyContractTest extends TestCase {

	/**
	 * @dataProvider zeroContentProvider
	 */
	public function testZeroIsRenderedAsRepeaterContent( mixed $content ): void {
		$templater = new Templater();

		$result = $templater->render(
			'%s[[item]]<span>%s</span>[[/item]]',
			[
				[
					[ 'tag' => 'item', 'content' => $content ],
				],
			]
		);

		$this->assertSame( '<span>0</span>', $result );
	}

	public static function zeroContentProvider(): array {
		return [
			'string zero'  => [ '0' ],
			'integer zero' => [ 0 ],
		];
	}

	public function testLegacyClassIsMarkedAsDeprecated(): void {
		$docComment = ( new ReflectionClass( Templater::class ) )->getDocComment();

		$this->assertSame( true, is_string( $docComment ) );
		$this->assertSame( true, str_contains( (string) $docComment, '@deprecated since 4.2.0' ) );
	}

	public function testPreselectedReplacementPreservesBackreferencesAndBackslashes(): void {
		$templater = new Templater();

		$result = $templater->render( '[[free|price-$1\\path/]]%d', [ 1 ] );

		$this->assertSame( 'price-$1\\path', $result );
	}

	public function testUnknownRepeaterThrowsInvalidArgumentException(): void {
		$templater = new Templater();

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Unknown legacy repeater "missing".' );

		$templater->render(
			'%s[[known]]<span>%s</span>[[/known]]',
			[
				[
					[ 'tag' => 'missing', 'content' => 'value' ],
				],
			]
		);
	}

	public function testSetPreselectedSeparatorIsFluentAndChangesSeparator(): void {
		$templater = new Templater();

		$this->assertSame( $templater, $templater->set_preselected_separator( ';' ) );
		$this->assertSame( 'two', $templater->render( '[[one;two/]]%d', [ 1 ] ) );
	}

	public function testSetPreselectedRegexIsFluentAndChangesSyntax(): void {
		$templater = new Templater();
		$regex     = '/\(\((?P<values>.+)\)\)(?P<index>(?-U)\d+)/mUs';

		$this->assertSame( $templater, $templater->set_preselected_regex( $regex ) );
		$this->assertSame( 'two', $templater->render( '((one|two))%d', [ 1 ] ) );
	}

	public function testSetRepeaterRegexIsFluentAndChangesSyntax(): void {
		$templater = new Templater();
		$regex     = '/\{\{(?P<tag>.+)\}\}(?P<content>.+)\{\{\/(?P=tag)\}\}/mUs';

		$this->assertSame( $templater, $templater->set_regex( $regex ) );

		$result = $templater->render(
			'%s{{item}}<span>%s</span>{{/item}}',
			[
				[
					[ 'tag' => 'item', 'content' => 'value' ],
				],
			]
		);

		$this->assertSame( '<span>value</span>', $result );
	}

	public function testInvertRendersOnlyTheFirstRequestedRepeaterGroup(): void {
		$templater = new Templater();

		$result = $templater->render(
			'outside%s[[item]]<span>%s</span>[[/item]]',
			[
				[
					[ 'tag' => 'item', 'content' => 'first' ],
				],
				[
					[ 'tag' => 'item', 'content' => 'second' ],
				],
			],
			true
		);

		$this->assertSame( '<span>first</span>', $result );
	}
}
