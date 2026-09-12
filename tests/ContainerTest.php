<?php


use iTRON\Anatomy\Container;
use iTRON\Anatomy\Core;
use iTRON\Anatomy\Exception\InvalidTemplateDataException;
use iTRON\Anatomy\Templater;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase {
	public function testAddText() {
		$array = new Container();

		$array->addText( 'Lorem Ipsum Dolor sit Amet' );

		$this->assertEquals(
			[
				[
					'data' => 'Lorem Ipsum Dolor sit Amet',
				],
			],
			$array->getArrayCopy()
		);
	}

	public function testAddBlock() {
		$array = new Container();

		$array->addBlock( 'repeater_0', [ 'key' => 'data' ] );

		$this->assertEquals(
			[
				[
					'block' => 'repeater_0',
					'data'     => [ 'key' => 'data' ],
				],
			],
			$array->getArrayCopy()
		);

		$container = new Container();
		$container->addBlock( 'repeater_1', [
			'key' => 'data',
			'key2' => $array
		] );

		$container->addText( 'Lorem Ipsum Dolor sit Amet' );

		$this->assertEquals(
			[
				[
					'block' => 'repeater_1',
					'data'     => [
						'key' => 'data',
						'key2' => [
							[
								'block' => 'repeater_0',
								'data'     => [ 'key' => 'data' ],
							],
						],
					],
				],
				[
					'data' => 'Lorem Ipsum Dolor sit Amet',
				],
			],
			$container->getArrayCopy()
		);

	}

	public function testEmptyContainerHasAnEmptyArrayRepresentation(): void {
		$this->assertSame( [], ( new Container() )->getArrayCopy() );
	}

	public function testArrayRepresentationRejectsANonArrayElement(): void {
		$container = new Container();
		$container->append( 'invalid' );

		$this->expectException( InvalidTemplateDataException::class );

		$container->getArrayCopy();
	}

	public function testArrayRepresentationRejectsAnElementWithoutData(): void {
		$container = new Container();
		$container->append( [ 'block' => 'card' ] );

		$this->expectException( InvalidTemplateDataException::class );

		$container->getArrayCopy();
	}

	public function testArrayRepresentationPreservesEmptyBlockData(): void {
		$container = ( new Container() )->addBlock( 'empty', [] );

		$this->assertSame(
			[ [ 'block' => 'empty', 'data' => [] ] ],
			$container->getArrayCopy()
		);
	}

	public function testArrayRepresentationExportsADirectNestedContainer(): void {
		$nested = ( new Container() )->addText( 'nested' );
		$container = new Container();
		$container->append( [ 'data' => $nested ] );

		$this->assertSame(
			[ [ 'data' => [ [ 'data' => 'nested' ] ] ] ],
			$container->getArrayCopy()
		);
	}

	public function testContainerSubclassExportsANestedBaseContainer(): void {
		$nested = ( new Container() )->addText( 'base' );
		$container = new class extends Container {};
		$container->append( [ 'data' => $nested ] );

		$this->assertSame(
			[ [ 'data' => [ [ 'data' => 'base' ] ] ] ],
			$container->getArrayCopy()
		);
	}

	public function testTemplaterRejectsAMalformedContainerBeforeRendering(): void {
		$container = new Container();
		$container->append( 'invalid' );

		$this->expectException( InvalidTemplateDataException::class );

		( new Templater() )->render( '{{content}}', [ 'content' => $container ] );
	}

	public function testCoreRejectsAMalformedContainerElement(): void {
		$container = new Container();
		$container->append( 'invalid' );

		$this->expectException( InvalidTemplateDataException::class );

		( new Core( '' ) )->renderContainer( $container );
	}

	public function testCoreRejectsAContainerElementWithoutData(): void {
		$container = new Container();
		$container->append( [ 'block' => 'card' ] );

		$this->expectException( InvalidTemplateDataException::class );

		( new Core( '' ) )->renderContainer( $container );
	}

	public function testCoreRejectsANonStringBlockName(): void {
		$container = new Container();
		$container->append( [ 'block' => 42, 'data' => [] ] );

		$this->expectException( InvalidTemplateDataException::class );

		( new Core( '' ) )->renderContainer( $container );
	}

	public function testCoreRejectsNonArrayDataForANamedBlock(): void {
		$container = new Container();
		$container->append( [ 'block' => 'card', 'data' => 'invalid' ] );

		$this->expectException( InvalidTemplateDataException::class );

		( new Core( '' ) )->renderContainer( $container );
	}

	public function testCoreRendersAnEmptyContainer(): void {
		$this->assertSame( '', ( new Core( '' ) )->renderContainer( new Container() ) );
	}

	public function testCoreRendersAnEmptyArrayTextElement(): void {
		$container = new Container();
		$container->append( [ 'data' => [] ] );

		$this->assertSame( '', ( new Core( '' ) )->renderContainer( $container ) );
	}

	public function testCoreRendersMixedTextAndBlockElements(): void {
		$core = new Core( '[[#card]]<b>{{label}}</b>[[/card]]' );
		$core->extractBlocks();
		$container = ( new Container() )
			->addText( 'before' )
			->addBlock( 'card', [ 'label' => 'inside' ] )
			->addText( 'after' );

		$this->assertSame( 'before<b>inside</b>after', $core->renderContainer( $container ) );
	}

	public function testContainerRendersABlockNamedZero(): void {
		$container = ( new Container() )->addBlock( '0', [ 'value' => 'zero' ] );

		$result = ( new Templater() )->render(
			'{{content}}[[#0]]<b>{{value}}</b>[[/0]]',
			[ 'content' => $container ]
		);

		$this->assertSame( '<b>zero</b>', $result );
	}

	public function testTemplaterTreatsAnExplicitEmptyBlockNameAsText(): void {
		$container = new Container();
		$container->append( [ 'block' => '', 'data' => 'text' ] );

		$result = ( new Templater() )->render( '{{content}}', [ 'content' => $container ] );

		$this->assertSame( 'text', $result );
	}

	public function testTemplaterRejectsNonArrayDataForANamedBlock(): void {
		$container = new Container();
		$container->append( [ 'block' => 'card', 'data' => 'invalid' ] );

		$this->expectException( InvalidTemplateDataException::class );
		$this->expectExceptionMessage( 'Data for block "card" must be an array.' );

		( new Templater() )->render(
			'{{content}}[[#card]]{{value}}[[/card]]',
			[ 'content' => $container ]
		);
	}

	public function testOneContainerCanBeBoundFromMultipleDataPaths(): void {
		$container = ( new Container() )->addText( 'shared' );

		$result = ( new Templater() )->render(
			'{{first}}/{{second}}',
			[ 'first' => $container, 'second' => [ $container ] ]
		);

		$this->assertSame( 'shared/shared', $result );
	}

	public function testTemplaterRejectsAContainerElementWithoutData(): void {
		$container = new Container();
		$container->append( [ 'block' => 'card' ] );

		$this->expectException( InvalidTemplateDataException::class );

		( new Templater() )->render( '{{content}}', [ 'content' => $container ] );
	}

	public function testTemplaterRejectsAMalformedElementAfterAValidElement(): void {
		$container = ( new Container() )->addText( 'valid prefix' );
		$container->append( [ 'block' => 'card' ] );

		$this->expectException( InvalidTemplateDataException::class );

		( new Templater() )->render( '{{content}}', [ 'content' => $container ] );
	}

	public function testRenderingRejectsACyclicContainerReference(): void {
		$container = new Container();
		$container->append( [ 'data' => $container ] );

		$this->expectException( InvalidTemplateDataException::class );
		$this->expectExceptionMessage( 'Cyclic container reference detected.' );

		( new Templater() )->render( '{{content}}', [ 'content' => $container ] );
	}

	public function testRenderingRejectsMutuallyCyclicContainers(): void {
		$first = new Container();
		$second = new Container();
		$first->append( [ 'data' => $second ] );
		$second->append( [ 'data' => $first ] );

		$this->expectException( InvalidTemplateDataException::class );
		$this->expectExceptionMessage( 'Cyclic container reference detected.' );

		( new Templater() )->render( '{{content}}', [ 'content' => $first ] );
	}

	public function testArrayExportRejectsACyclicContainerReference(): void {
		$container = new Container();
		$container->append( [ 'data' => [ 'self' => $container ] ] );

		$this->expectException( InvalidTemplateDataException::class );
		$this->expectExceptionMessage( 'Cyclic container reference detected.' );

		$container->getArrayCopy();
	}

	public function testArrayExportRejectsMutuallyCyclicContainers(): void {
		$first = new Container();
		$second = new Container();
		$first->append( [ 'data' => $second ] );
		$second->append( [ 'data' => $first ] );

		$this->expectException( InvalidTemplateDataException::class );
		$this->expectExceptionMessage( 'Cyclic container reference detected.' );

		$first->getArrayCopy();
	}

	public function testArrayExportRejectsMixedSubclassCycles(): void {
		$first = new class extends Container {};
		$second = new Container();
		$first->append( [ 'data' => $second ] );
		$second->append( [ 'data' => $first ] );

		$this->expectException( InvalidTemplateDataException::class );

		$first->getArrayCopy();
	}

	public function testRenderCycleGuardIsReleasedAfterAnException(): void {
		$core = new Core( '' );
		$container = new Container();
		$container->append( [ 'data' => $container ] );
		$container->setContext( $core );
		$exceptionThrown = false;

		try {
			$core->renderContainer( $container );
		} catch ( InvalidTemplateDataException ) {
			$exceptionThrown = true;
		}

		$container->exchangeArray( [] );
		$container->addText( 'recovered' );

		$this->assertSame( true, $exceptionThrown );
		$this->assertSame( 'recovered', $core->renderContainer( $container ) );
	}

	public function testExportCycleGuardIsReleasedAfterAnException(): void {
		$container = new Container();
		$container->append( [ 'data' => $container ] );
		$exceptionThrown = false;

		try {
			$container->getArrayCopy();
		} catch ( InvalidTemplateDataException ) {
			$exceptionThrown = true;
		}

		$container->exchangeArray( [] );
		$container->addText( 'recovered' );

		$this->assertSame( true, $exceptionThrown );
		$this->assertSame( [ [ 'data' => 'recovered' ] ], $container->getArrayCopy() );
	}

	public function testRenderingRejectsASelfReferentialArray(): void {
		$value = [];
		$value['self'] =& $value;

		$this->expectException( InvalidTemplateDataException::class );
		$this->expectExceptionMessage( 'Nested arrays are not supported for tag "value".' );

		( new Templater() )->render( '{{value}}', [ 'value' => $value ] );
	}
}
