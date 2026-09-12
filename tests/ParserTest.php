<?php

use iTRON\Anatomy\Container;
use iTRON\Anatomy\Exception\TemplateSyntaxException;
use iTRON\Anatomy\Templater;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase {
	public function testBlockNamesSupportLettersDigitsHyphensAndUnderscores(): void {
		$result = ( new Templater() )->renderBlock(
			'[[#card-item_2]]<article>{{title}}</article>[[/card-item_2]]',
			'card-item_2',
			[ 'title' => 'Valid name' ]
		);

		$this->assertSame( '<article>Valid name</article>', $result );
	}

	public function testDistinctSiblingBlockDefinitionsRenderInTheRequestedOrder(): void {
		$template = '{{content}}'
			. '[[#card]]<article>{{title}}</article>[[/card]]'
			. '[[#badge]]<strong>{{label}}</strong>[[/badge]]';

		$content = ( new Container() )
			->addBlock( 'badge', [ 'label' => 'New' ] )
			->addBlock( 'card', [ 'title' => 'First' ] )
			->addBlock( 'card', [ 'title' => 'Second' ] );

		$result = ( new Templater() )->render( $template, [ 'content' => $content ] );

		$this->assertSame(
			'<strong>New</strong><article>First</article><article>Second</article>',
			$result
		);
	}

	public function testDistinctNestedBlockDefinitionsRenderAsNestedContainers(): void {
		$template = '{{content}}'
			. '[[#section]]<section><h2>{{title}}</h2>{{items}}'
			. '[[#item]]<p>{{text}}</p>[[/item]]'
			. '</section>[[/section]]';

		$items = ( new Container() )
			->addBlock( 'item', [ 'text' => 'One' ] )
			->addBlock( 'item', [ 'text' => 'Two' ] );
		$content = ( new Container() )->addBlock(
			'section',
			[ 'title' => 'Title', 'items' => $items ]
		);

		$result = ( new Templater() )->render( $template, [ 'content' => $content ] );

		$this->assertSame(
			'<section><h2>Title</h2><p>One</p><p>Two</p></section>',
			$result
		);
	}

	public function testOneBlockDefinitionCanBeReusedRecursivelyByNestedContainers(): void {
		$template = '<ul>{{tree}}</ul>'
			. '[[#node]]<li>{{label}}{{children}}</li>[[/node]]';

		$thirdLevel = ( new Container() )->addBlock(
			'node',
			[ 'label' => 'C', 'children' => '' ]
		);
		$secondLevel = ( new Container() )->addBlock(
			'node',
			[ 'label' => 'B', 'children' => $thirdLevel ]
		);
		$tree = ( new Container() )->addBlock(
			'node',
			[ 'label' => 'A', 'children' => $secondLevel ]
		);

		$result = ( new Templater() )->render( $template, [ 'tree' => $tree ] );

		$this->assertSame( '<ul><li>A<li>B<li>C</li></li></li></ul>', $result );
	}

	public function testUnclosedBlockDefinitionThrowsASyntaxException(): void {
		$this->expectException( TemplateSyntaxException::class );

		( new Templater() )->render(
			'Before [[#card]]<article>content</article>',
			[ 'unused' => true ]
		);
	}

	public function testClosingMarkerWithoutAnOpeningMarkerThrowsASyntaxException(): void {
		$this->expectException( TemplateSyntaxException::class );

		( new Templater() )->render(
			'Before [[/card]] after',
			[ 'unused' => true ]
		);
	}

	public function testMismatchedClosingMarkerThrowsASyntaxException(): void {
		$this->expectException( TemplateSyntaxException::class );

		( new Templater() )->render(
			'[[#card]]content[[/other]]',
			[ 'unused' => true ]
		);
	}

	public function testImproperlyNestedClosingMarkersThrowASyntaxException(): void {
		$this->expectException( TemplateSyntaxException::class );

		( new Templater() )->render(
			'[[#outer]][[#inner]]content[[/outer]][[/inner]]',
			[ 'unused' => true ]
		);
	}

	public function testDuplicateBlockDefinitionsThrowASyntaxException(): void {
		$this->expectException( TemplateSyntaxException::class );

		( new Templater() )->render(
			'[[#card]]one[[/card]][[#card]]two[[/card]]',
			[ 'unused' => true ]
		);
	}

	public function testNestedDuplicateBlockDefinitionsThrowASyntaxException(): void {
		$this->expectException( TemplateSyntaxException::class );

		( new Templater() )->render(
			'[[#node]]outer [[#node]]inner[[/node]][[/node]]',
			[ 'unused' => true ]
		);
	}

	public function testEmptyBlockNameMarkersThrowASyntaxException(): void {
		$this->expectException( TemplateSyntaxException::class );

		( new Templater() )->render(
			'Before [[#]]content[[/]] after',
			[ 'unused' => true ]
		);
	}

	/**
	 * @dataProvider invalidBlockMarkerProvider
	 */
	public function testInvalidOrUnterminatedBlockMarkersThrowASyntaxException( string $template ): void {
		$this->expectException( TemplateSyntaxException::class );

		( new Templater() )->render( $template, [ 'unused' => true ] );
	}

	public static function invalidBlockMarkerProvider(): array {
		return [
			'space in name'       => [ '[[#bad name]]content[[/bad name]]' ],
			'dot in name'         => [ '[[#bad.name]]content[[/bad.name]]' ],
			'Unicode in name'     => [ '[[#блок]]content[[/блок]]' ],
			'line feed in name'   => [ "[[#bad\nname]]content[[/bad\nname]]" ],
			'carriage return'     => [ "[[#bad\rname]]content[[/bad\rname]]" ],
			'closing bracket'     => [ '[[#bad]name]]content[[/bad]name]]' ],
			'unterminated marker' => [ 'Before [[#card' ],
		];
	}

	/**
	 * @dataProvider invalidMarkerSequenceProvider
	 */
	public function testInvalidMarkerSequencesAfterOtherTokensThrowASyntaxException( string $template ): void {
		$this->expectException( TemplateSyntaxException::class );

		( new Templater() )->render( $template, [ 'unused' => true ] );
	}

	public static function invalidMarkerSequenceProvider(): array {
		return [
			'invalid close name after open' => [ '[[#card]]content[[/bad name]]' ],
			'invalid open after valid block' => [ '[[#card]]ok[[/card]][[#bad name]]' ],
			'unterminated after valid block' => [ '[[#card]]ok[[/card]][[/other' ],
			'unexpected close after valid block' => [ '[[#card]]ok[[/card]][[/other]]' ],
			'duplicate after another definition' => [
				'[[#card]]one[[/card]][[#other]]two[[/other]][[#card]]three[[/card]]',
			],
			'nested inner block left unclosed' => [ '[[#outer]][[#inner]]content' ],
			'outer block left unclosed after inner closes' => [
				'[[#outer]][[#inner]]content[[/inner]]',
			],
			'mismatch after a nested definition' => [
				'[[#outer]][[#inner]]content[[/inner]][[/other]]',
			],
		];
	}
}
