<?php

use iTRON\Templater\Templater;
use PHPUnit\Framework\TestCase;

require_once 'inc/functions.php';

class TemplaterTest extends TestCase {

	public function testRender() {
		/**
		 * Simple template with two placeholders.
		 */
		$tpl = <<<TEMPLATE
<div class="classname">
	<div>%s</div>
	<div>%s</div>
</div>
TEMPLATE;

		$templater = new Templater();
		$result    = $templater->render( $tpl, [ 'CONTENT1', 'CONTENT2' ] );

		$expected = <<<EXPECTED
<div class="classname">
	<div>CONTENT1</div>
	<div>CONTENT2</div>
</div>
EXPECTED;

		$this->assertEquals( $expected, $result );

		/**
		 * Template with a repeater.
		 */
		$tpl = <<<TEMPLATE
<div class="classname">
	%s[[tag1]]<div class="tag1">%s</div>[[/tag1]]
</div>
TEMPLATE;

		$templater = new Templater();
		$result    = $templater->render( $tpl, [
			[
				[ 'tag' => 'tag1', 'content' => 'CONTENT' ],
			],
		] );

		$expected = <<<EXPECTED
<div class="classname">
	<div class="tag1">CONTENT</div>
</div>
EXPECTED;

		$this->assertEquals( $expected, $result );

		/**
		 * Template with two repeaters.
		 */
		$tpl = <<<TEMPLATE
<div class="classname">
	%s[[tag1]]<div class="tag1">%s</div>[[/tag1]]
	%s[[tag2]]<div class="tag2">%s</div>[[/tag2]]
</div>
TEMPLATE;

		$result = $templater->render( $tpl, [
			[
				[ 'tag' => 'tag1', 'content' => 'CONTENT1' ],
				[ 'tag' => 'tag1', 'content' => 'CONTENT1.2' ],
				[ 'tag' => 'tag1', 'content' => 'CONTENT1.3' ],
			],
			[
				[ 'tag' => 'tag2', 'content' => 'CONTENT2' ],
			]
		] );

		$expected = <<<EXPECTED
<div class="classname">
	<div class="tag1">CONTENT1</div>
	<div class="tag1">CONTENT1.2</div>
	<div class="tag1">CONTENT1.3</div>
	<div class="tag2">CONTENT2</div>
</div>
EXPECTED;

		$this->assertEquals( remove_spaces( $expected ), remove_spaces( $result ) );

		/**
		 * Template with a preselected value.
		 */
		$tpl = <<<TEMPLATE
<div class="classname">
	%s[[tag1]]<div class="tag1">%s</div>[[/tag1]]
	%s[[tag2]]<div class="tag2">%s</div>[[/tag2]]
	[[value1|value2|value3/]]%d
</div>
TEMPLATE;

		$result = $templater->render( $tpl, [
			[
				[ 'tag' => 'tag1', 'content' => 'CONTENT1' ],
				[ 'tag' => 'tag1', 'content' => 'CONTENT1.2' ],
				[ 'tag' => 'tag1', 'content' => 'CONTENT1.3' ],
			],
			[
				[ 'tag' => 'tag2', 'content' => 'CONTENT2' ],
			],
			1,
		] );

		$expected = <<<EXPECTED
<div class="classname">
	<div class="tag1">CONTENT1</div>
	<div class="tag1">CONTENT1.2</div>
	<div class="tag1">CONTENT1.3</div>
	<div class="tag2">CONTENT2</div>
	value2
</div>
EXPECTED;

		$this->assertEquals( remove_spaces( $expected ), remove_spaces( $result ) );

		/**
		 * The same test with a wrong index.
		 */
		$result = $templater->render( $tpl, [
			[
				[ 'tag' => 'tag1', 'content' => 'CONTENT1' ],
				[ 'tag' => 'tag1', 'content' => 'CONTENT1.2' ],
				[ 'tag' => 'tag1', 'content' => 'CONTENT1.3' ],
			],
			[
				[ 'tag' => 'tag2', 'content' => 'CONTENT2' ],
			],
			10,
		] );

		$expected = <<<EXPECTED
<div class="classname">
	<div class="tag1">CONTENT1</div>
	<div class="tag1">CONTENT1.2</div>
	<div class="tag1">CONTENT1.3</div>
	<div class="tag2">CONTENT2</div>
	value1
</div>
EXPECTED;

		$this->assertEquals( remove_spaces( $expected ), remove_spaces( $result ) );
	}

	public function testNestedRepeaters() {
		$templater = new Templater();

		/**
		 * Template with several tags.
		 */
		$tpl = <<<TEMPLATE
<div class="list">
  %s[[list-item]]<div class="sub-list">%s</div>[[/list-item]]
</div>

[[logo]]<div class="logo">%s</div>[[/logo]]
[[text]]<div class="text">%s</div>[[/text]]
TEMPLATE;

		$result = $templater->render( $tpl, [
			[
				[
					'tag'     => 'list-item',
					'content' => [
						[
							[ 'tag' => 'logo', 'content' => 'LOGO1' ],
							[ 'tag' => 'text', 'content' => 'TEXT1' ],
						]
					],
				],
			],
		] );

		$expected = <<<EXPECTED
<div class="list">
  <div class="sub-list">
  	<div class="logo">LOGO1</div>
  	<div class="text">TEXT1</div>
  </div>
</div>
EXPECTED;

		$this->assertEquals( remove_spaces( $expected ), remove_spaces( $result ) );

		/**
		 * Template with nested repeaters.
		 */
		$tpl = <<<TEMPLATE
<div class="classname">
	%s
	[[tag1]]
	<div class="tag1">
		%s
		[[tag2]]<div class="tag2">%s</div>[[/tag2]]
	</div>
	[[/tag1]]
</div>
TEMPLATE;

		$result = $templater->render( $tpl, [
			[
				[
					'tag'     => 'tag1',
					'content' => [
						[
							[ 'tag' => 'tag2', 'content' => 'CONTENT2' ],
						],
					]
				],
			],
		] );

		$expected = <<<EXPECTED
<div class="classname">
	<div class="tag1">
		<div class="tag2">CONTENT2</div>
	</div>
</div>
EXPECTED;


		$this->assertEquals( remove_spaces( $expected ), remove_spaces( $result ) );

		/**
		 * Tests template with several nested tags.
		 */
		$tpl = <<<TEMPLATE
<div class="list">
  %s[[list-item]]
  <div class="sub-list">%s</div>
  
	[[logo]]<div class="logo">%s</div>[[/logo]]
	[[text]]<div class="text">%s</div>[[/text]]
  [[/list-item]]
</div>
TEMPLATE;

		$result = $templater->render( $tpl, [
			[
				[
					'tag'     => 'list-item',
					'content' => [
						[
							[ 'tag' => 'logo', 'content' => 'LOGO1' ],
							[ 'tag' => 'text', 'content' => 'TEXT1' ],
						]
					],
				],
			],
		] );

		$expected = <<<EXPECTED
<div class="list">
  <div class="sub-list">
  	<div class="logo">LOGO1</div>
  	<div class="text">TEXT1</div>
  </div>
</div>
EXPECTED;


		$this->assertEquals( remove_spaces( $expected ), remove_spaces( $result ) );

		$tpl = <<<TEMPLATE
<div class="list">
  %s[[list-item]]
		%s
  
	[[logo]]
	<div class="logo">
		%s
	</div>
	[[/logo]]
	[[text]]<div class="text">%s</div>[[/text]]
	[[img]]<img src="%s" alt=""/>[[/img]]
  [[/list-item]]
</div>
TEMPLATE;

		$data = [
			[
				[
					'tag'     => 'list-item',
					'content' => [
						[
							[
								'tag'     => 'logo',
								'content' => [
									[
										[ 'tag' => 'img', 'content' => 'localhost//image1.jpg' ],
									]
								]
							],
							[ 'tag' => 'text', 'content' => 'TEXT1' ],
						]
					],
				],
			],
		];

		$result = $templater->render( $tpl, $data );

		$expected = <<<EXPECTED
<div class="list">
  <div class="logo">
  	<img src="localhost//image1.jpg" alt=""/>
  </div>
  <div class="text">TEXT1</div>
</div>
EXPECTED;


		$this->assertEquals( remove_spaces( $expected ), remove_spaces( $result ) );

		/**
		 * The same test, but with a deeper nesting.
		 */

		$tpl = <<<TEMPLATE
<div class="list">
  %s[[list-item]]
		%s
  
	[[logo]]
	<div class="logo">
		%s
		[[img]]<img src="%s" alt=""/>[[/img]]
	</div>
	[[/logo]]
	[[text]]<div class="text">%s</div>[[/text]]
  [[/list-item]]
</div>
TEMPLATE;

		$result = $templater->render( $tpl, $data );
		// This fails.
		$this->assertEquals( remove_spaces( $expected ), remove_spaces( $result ) );
	}

	public function testGetRepeaters() {
		$templater = new Templater();
		$method    = new ReflectionMethod( Templater::class, 'get_repeaters' );
		$method->setAccessible( true );

		/**
		 * Simple template with nested repeaters.
		 */
		$tpl = <<<TEMPLATE
<div class="list">
  %s[[list-item]]
    %s[[logo]]<div class="logo">%s</div>[[/logo]][[text]]<div class="text">%s</div>[[/text]]
  [[/list-item]]
</div>
TEMPLATE;

		$result = $method->invoke( $templater, $tpl );

		$expected = [
			'result' => [
				'clear' => [
					'list-item' => '%s',
					'logo'      => '<div class="logo">%s</div>',
					'text'      => '<div class="text">%s</div>'
				],

				'content' => [
					'%s[[logo]]<div class="logo">%s</div>[[/logo]][[text]]<div class="text">%s</div>[[/text]]',
					'<div class="logo">%s</div>',
					'<div class="text">%s</div>'
				],

				'tag' => [
					'list-item',
					'logo',
					'text',
				],
			],
		];

		$this->assertEquals(
			remove_spaces_recursively( $expected['result'] ),
			remove_spaces_recursively( $result['result'] )
		);

		/**
		 * Deep nested.
		 */
		$tpl = <<<TEMPLATE
<div class="list">
  %s[[list-item]]
		%s
  
	[[logo]]
	<div class="logo">
		%s
	</div>
	[[/logo]]
	[[text]]<div class="text">%s</div>[[/text]]
	[[img]]<img src="%s" alt=""/>[[/img]]
  [[/list-item]]
</div>
TEMPLATE;

		$result   = $method->invoke( $templater, $tpl );
		$expected = [
			'result' => [
				'clear' => [
					'list-item' => '%s',
					'logo'      => '<div class="logo">%s</div>',
					'text'      => '<div class="text">%s</div>',
					'img'       => '<img src="%s" alt=""/>',
				],

				'content' => [
					'%s[[logo]]<div class="logo">%s</div>[[/logo]][[text]]<div class="text">%s</div>[[/text]][[img]]<img src="%s" alt=""/>[[/img]]',
					'<div class="logo">%s</div>',
					'<div class="text">%s</div>',
					'<img src="%s" alt=""/>',
				],

				'tag' => [
					'list-item',
					'logo',
					'text',
					'img',
				],
			],
		];

		$this->assertEquals(
			remove_spaces_recursively( $expected['result'] ),
			remove_spaces_recursively( $result['result'] )
		);

		/**
		 * The same, but with a deeper nesting.
		 */
		$tpl      = <<<TEMPLATE
<div class="list">
  %s[[list-item]]
		%s
  
	[[logo]]
	<div class="logo">
		%s
		[[img]]<img src="%s" alt=""/>[[/img]]
	</div>
	[[/logo]]
	[[text]]<div class="text">%s</div>[[/text]]
  [[/list-item]]
</div>
TEMPLATE;
		$expected = [
			'result' => [
				'clear' => [
					'list-item' => '%s',
					'logo'      => '<div class="logo">%s</div>',
					'text'      => '<div class="text">%s</div>',
					'img'       => '<img src="%s" alt=""/>',
				],

				'content' => [
					'%s[[logo]]<div class="logo">%s[[img]]<img src="%s" alt=""/>[[/img]]</div>[[/logo]][[text]]<div class="text">%s</div>[[/text]]',
					'<div class="logo">%s[[img]]<img src="%s" alt=""/>[[/img]]</div>',
					'<div class="text">%s</div>',
					'<img src="%s" alt=""/>',
				],

				'tag' => [
					'list-item',
					'logo',
					'text',
					'img',
				],
			],
		];

		$result = $method->invoke( $templater, $tpl );
		// This fails.
		$this->assertEquals(
			remove_spaces_recursively( $expected['result'] ),
			remove_spaces_recursively( $result['result'] )
		);
	}

	public function testDeepNested_repeaters_parser() {
		$templater = new Templater();
		$method    = new ReflectionMethod( Templater::class, 'get_repeaters' );
		$method->setAccessible( true );

		/**
		 * The same, but with a deeper nesting.
		 */
		$tpl      = <<<TEMPLATE
<div class="list">
  %s[[list-item]]
		%s
  
	[[logo]]
	<div class="logo">
		%s
		[[img]]<img src="%s" alt=""/>[[/img]]
		[[caption]]<span class="caption">%s</span>[[/caption]]
	</div>
	[[/logo]]
	[[text]]<div class="text">%s</div>[[/text]]
  [[/list-item]]
</div>
TEMPLATE;
		$expected = [
			'result' => [
				'clear' => [
					'list-item' => '%s',
					'logo'      => '<div class="logo">%s</div>',
					'img'       => '<img src="%s" alt=""/>',
					'caption'   => '<span class="caption">%s</span>',
					'text'      => '<div class="text">%s</div>',
				],

				'content' => [
					'%s[[logo]]<div class="logo">%s[[img]]<img src="%s" alt=""/>[[/img]][[caption]]<span class="caption">%s</span>[[/caption]]</div>[[/logo]][[text]]<div class="text">%s</div>[[/text]]',
					'<div class="logo">%s[[img]]<img src="%s" alt=""/>[[/img]][[caption]]<span class="caption">%s</span>[[/caption]]</div>',
					'<div class="text">%s</div>',
					'<img src="%s" alt=""/>',
					'<span class="caption">%s</span>',
				],

				'tag' => [
					'list-item',
					'logo',
					'text',
					'img',
					'caption',
				],
			],
		];

		$result = $method->invoke( $templater, $tpl );
		// This fails.
		$this->assertEquals(
			remove_spaces_recursively( $expected['result'] ),
			remove_spaces_recursively( $result['result'] )
		);
	}
}
