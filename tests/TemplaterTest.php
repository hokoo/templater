<?php

use PHPUnit\Framework\TestCase;
use iTRON\Anatomy\Templater;
use iTRON\Anatomy\Container;

class TemplaterTest extends TestCase
{

	public function testRenderSimpleTemplate()
	{
		$templater = new Templater();
		$template = "{{greeting}}, {{name}}!";
		$data = [
			'greeting' => 'Hello',
			'name' => 'John'
		];

		$result = $templater->render($template, $data);

		$this->assertEquals("Hello, John!", $result);
	}

	public function testRenderWithMultipleBlocks()
	{
		$template = "Before {{content}}[[#person]]{{name}} [[/person]] After {{after}}[[#footer]]{{text}}[[/footer]]";

		$content = new Container();
		$content->addBlock('person', ['name' => 'Alice']);
		$content->addBlock('person', ['name' => 'Bob']);

		$after = new Container();
		$after->addBlock( 'footer',
			[
				'text' => 'Footer text',
			]
		);

		$templater = new Templater();

		$result = $templater->render(
			$template,
			[
				'content' => $content,
				'after' => $after
			]
		);

		$this->assertEquals("Before Alice Bob  After Footer text", $result);
	}

	public function testRenderWithNestedBlocks()
	{
		$template = "Before " .
		"{{content}}[[#person]]{{name}}: {{position}}[[#position]]{{title}} - {{exp}}[[/position]][[/person]] " .
		"After {{after}}[[#footer]]{{text}}[[/footer]]";

		$content = new Container();
		$content->addBlock('person', [
			'name' => 'Alice',
			'position' => ( new Container() )
				->addBlock('position', ['title' => 'Developer', 'exp' => '5 years'])
				->addBlock('position', ['title' => 'Designer', 'exp' => '3 years'])
		]);
		$content->addBlock('person', [
			'name' => 'Harry',
			'position' => ( new Container() )
				->addBlock('position', ['title' => 'Lead', 'exp' => '15 years'])
				->addBlock('position', ['title' => 'Manager', 'exp' => '10 years'])
		]);

		$after = new Container();
		$after->addBlock( 'footer',
			[
				'text' => 'Footer text',
			]
		);

		$templater = new Templater();
		$result = $templater->render(
			$template,
			[
				'content' => $content,
				'after' => $after
			]
		);

		$expected = "Before " .
		            "Alice: Developer - 5 yearsDesigner - 3 years" .
		            "Harry: Lead - 15 yearsManager - 10 years" .
		            " After Footer text";
		$this->assertEquals($expected, $result);
	}

	public function testRenderPredefined() {
		$template = "<div class='{{#class=[zero|one|two|three]}}' id='{{#id=[*foo*bar] delimiter=[*]}}'>{{content}}</div>";

		$templater = new Templater();
		$result = $templater->render(
			$template,
			[
				'content' => 'Content',
				'class' => 2,
				'id' => 1,
			]
		);

		$this->assertEquals("<div class='two' id='foo'>Content</div>", $result);
	}

	public function testRenderWithManyTags() {
		$template = "<div class='{{#class=[zero|one|two|three]}}' id='{{#id=[*foo*bar] delimiter=[*]}}'>{{content}}</div>";
		$template .= "[[#body]]<h1>{{title}}</h1><span>{{header}}</span>[[/body]]";
		$template .= "[[#footer]]<footer>{{one}} - {{two}}</footer>[[#copy]]<copy>{{text}}</copy>[[/copy]][[/footer]]{{footer}}";

		$content = new Container();
		$content->addBlock('body', [
			'title' => 'Title 1',
			'header' => 'Header 1',
		]);
		$content->addBlock('body', [
			'title' => 'Title 2',
			'header' => 'Header 2',
		]);
		$content->addText( 'Finally. ' );

		$footer = new Container();
		$footer->addBlock('footer', [
			'one' => 'One',
			'two' => 'Two',
		]);
		$footer->addBlock('copy', [
			'text' => 'Copy text',
		]);
		$footer->addBlock('footer', [
			'one' => 'New One',
			'two' => ( new Container() )
				->addBlock('copy', ['text' => 'New Copy text'])
				->addBlock('copy', ['text' => 'New Copy text 2'])
		]);
		$footer->addText( 'Footer text' );

		$templater = new Templater();
		$result = $templater->render(
			$template,
			[
				'content' => $content,
				'class' => 2,
				'id' => 1,
				'footer' => $footer,
			]
		);

		$this->assertEquals(
			"<div class='two' id='foo'><h1>Title 1</h1><span>Header 1</span><h1>Title 2</h1><span>Header 2</span>Finally. </div>"
			. "<footer>One - Two</footer>"
			. "<copy>Copy text</copy>"
			. "<footer>New One - <copy>New Copy text</copy><copy>New Copy text 2</copy></footer>"
			. "Footer text",
			$result
		);
	}

	public function testRenderBlock() {
		$template = "<div class='{{#class=[zero|one|two|three]}}' id='{{#id=[*foo*bar] delimiter=[*]}}'>{{content}}</div>";
		$template .= "[[#body]]<h1>{{title}}</h1><span>{{header}}</span>[[/body]]";
		$template .= "[[#smth]]<h1>Hardcoded title</h1><span>Hi there!</span>[[/smth]]";
		$template .= "[[#footer]]<footer>{{one}} - {{two}}</footer>[[#copy]]<copy>{{text}}</copy>[[/copy]][[/footer]]{{footer}}";

		$templater = new Templater();
		$result = $templater->renderBlock(
			$template,
			'copy',
			[
				'text' => 'Copy text',
			]
		);

		$this->assertEquals("<copy>Copy text</copy>", $result);

		// Single block rendering using another block as an inner content.
		$templater = new Templater();
		$result = $templater->renderBlock(
			$template,
			'body',
			[
				'title' => 'Title 1',
				'header' => ( new Container() )
					->addBlock('copy', ['text' => 'Copy text'])
					->addBlock('copy', ['text' => 'Copy text 2'])
			]
		);

		$this->assertEquals("<h1>Title 1</h1><span><copy>Copy text</copy><copy>Copy text 2</copy></span>", $result);

		$templater = new Templater();
		$result = $templater->renderBlock(
			$template,
			'smth',
			[]
		);

		$this->assertEquals("<h1>Hardcoded title</h1><span>Hi there!</span>", $result);
	}
}
