<?php


use iTRON\Anatomy\Container;
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
			$array->toArray()
		);
	}

	public function testAddRepeater() {
		$array = new Container();

		$array->addBlock( 'repeater_0', [ 'key' => 'data' ] );

		$this->assertEquals(
			[
				[
					'block' => 'repeater_0',
					'data'     => [ 'key' => 'data' ],
				],
			],
			$array->toArray()
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
			$container->toArray()
		);

	}
}
