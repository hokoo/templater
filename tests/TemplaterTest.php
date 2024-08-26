<?php

use iTRON\Templater\Templater;
use PHPUnit\Framework\TestCase;

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
	<div class="tag1">CONTENT1</div><div class="tag1">CONTENT1.2</div><div class="tag1">CONTENT1.3</div>
	<div class="tag2">CONTENT2</div>
</div>
EXPECTED;

		$this->assertEquals( $expected, $result );
	}
}
