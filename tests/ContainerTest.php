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

	public function testCoreRendersMixedTextAndBlockElements(): void {
		$core = new Core( '[[#card]]<b>{{label}}</b>[[/card]]' );
		$core->extractBlocks();
		$container = ( new Container() )
			->addText( 'before' )
			->addBlock( 'card', [ 'label' => 'inside' ] )
			->addText( 'after' );

		$this->assertSame( 'before<b>inside</b>after', $core->renderContainer( $container ) );
	}

	public function testOneContainerCanBeBoundFromMultipleDataPaths(): void {
		$container = ( new Container() )->addText( 'shared' );

		$result = ( new Templater() )->render(
			'{{first}}/{{second}}',
			[ 'first' => $container, 'second' => [ $container ] ]
		);

		$this->assertSame( 'shared/shared', $result );
	}
}
