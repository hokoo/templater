<?php

use iTRON\Anatomy\Templater;
use PHPUnit\Framework\TestCase;

class EscapingTest extends TestCase {
	public function testRegularTagKeepsTrustedHtmlRaw(): void {
		$result = ( new Templater() )->render(
			'<main>{{content}}</main>',
			[ 'content' => '<strong title="trusted">Raw & ready</strong>' ]
		);

		$this->assertSame(
			'<main><strong title="trusted">Raw & ready</strong></main>',
			$result
		);
	}

	public function testEscapedTagEncodesHtmlSpecialCharactersAsUtf8(): void {
		$result = ( new Templater() )->render(
			'{{content|e}}',
			[ 'content' => '<div title="Tom & Jerry">François\'s</div>' ]
		);

		$this->assertSame(
			'&lt;div title=&quot;Tom &amp; Jerry&quot;&gt;François&#039;s&lt;/div&gt;',
			$result
		);
	}

	public function testEscapedTagDoesNotDoubleEncodeExistingEntities(): void {
		$result = ( new Templater() )->render(
			'{{content|e}}',
			[ 'content' => 'Fish &amp; Chips &copy; 2026' ]
		);

		$this->assertSame( 'Fish &amp; Chips &copy; 2026', $result );
	}

	public function testEscapedTagSubstitutesInvalidUtf8Sequences(): void {
		$result = ( new Templater() )->render(
			'{{content|e}}',
			[ 'content' => "invalid \xC3" ]
		);

		$this->assertSame( "invalid \u{FFFD}", $result );
	}

	public function testRawAndEscapedOccurrencesOfTheSameValueAreIndependent(): void {
		$result = ( new Templater() )->render(
			'{{content}}|{{content|e}}|{{content}}',
			[ 'content' => '<b title="quoted">&</b>' ]
		);

		$this->assertSame(
			'<b title="quoted">&</b>|&lt;b title=&quot;quoted&quot;&gt;&amp;&lt;/b&gt;|<b title="quoted">&</b>',
			$result
		);
	}

	public function testMissingEscapedTagRendersAsAnEmptyString(): void {
		$result = ( new Templater() )->render(
			'before {{missing|e}} after',
			[ 'unrelated' => true ]
		);

		$this->assertSame( 'before  after', $result );
	}

	public function testEmptyDataLeavesEscapedTagUntouched(): void {
		$template = '<p>{{content|e}}</p>';

		$this->assertSame( $template, ( new Templater() )->render( $template, [] ) );
	}

	public function testRawDataContainingRegularAndPredefinedSyntaxIsNotParsedAgain(): void {
		$value = '{{name}} {{#choice=[safe|changed]}}';

		$result = ( new Templater() )->render(
			'<p>{{content}}</p>',
			[ 'content' => $value, 'name' => 'changed', 'choice' => 1 ]
		);

		$this->assertSame( '<p>' . $value . '</p>', $result );
	}

	public function testRawDataContainingBlockSyntaxIsNotParsedAgain(): void {
		$value = '[[#injected]]<script>{{payload}}</script>[[/injected]]';

		$result = ( new Templater() )->render(
			'<p>{{content}}</p>',
			[ 'content' => $value, 'payload' => 'changed' ]
		);

		$this->assertSame( '<p>' . $value . '</p>', $result );
	}

	public function testEscapedDataContainingTemplateSyntaxIsNotParsedAgain(): void {
		$value = '{{name}} [[#injected]]<b>value</b>[[/injected]]';

		$result = ( new Templater() )->render(
			'<p>{{content|e}}</p>',
			[ 'content' => $value, 'name' => 'changed' ]
		);

		$this->assertSame(
			'<p>{{name}} [[#injected]]&lt;b&gt;value&lt;/b&gt;[[/injected]]</p>',
			$result
		);
	}
}
