# Anatomy: a templater for PHP

[![Latest Stable Version](https://poser.pugx.org/hokoo/templater/v)](//packagist.org/packages/hokoo/templater) 
[![PHPUnit Tests](https://github.com/hokoo/templater/actions/workflows/phpunit.yml/badge.svg)](https://github.com/hokoo/templater/actions/workflows/phpunit.yml)

HTML Templater for PHP.

<img src="assets/anatomy-logo.png" alt="Anatomy logo" width="300" style="float: right; margin: 0 0 20px 30px; max-width: 40%"/>

Lightweight, simple but powerful templater for rendering HTML templates in PHP.

I believe that you'd like to 
- keep your HTML clean and simple and 
- bring logic to PHP (PHP itself is a templater, isn't it?), not to HTML.

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Getting Started](#getting-started)
  - [Tags](#tags)
  - [Predefined tags](#predefined-tags)
  - [Containers](#containers)
  - [Blocks](#blocks)
  - [Nested blocks](#nested-blocks)
  - [Detached blocks](#detached-blocks)


## Requirements
PHP 8.0 and later.

## Installation

```bash
composer require hokoo/templater
```


## Getting Started
Here are some examples of how to use the templater.

### Tags

```html
<div class="article">
    <h2>
        <!-- This is a tag with the name "title". -->
        {{title}}
    </h2>
    
    <div class="content">
        <!-- This is a tag with the name "content". -->
        {{content}}
    </div>

</div>
```

Tag - a point in the template where you can insert a value. Tag should be considered as a placeholder for a value. 

    In the Anatomy's paradigm, the 'tag' is only entity that can be replaced with a value.

The tag is a string that starts with `{{` and ends with `}}`.

So, in the example above, the `{{title}}` and `{{content}}` are tags.
Let's render the template with the values.

```php
use iTRON\Anatomy\Templater;
$templater = new Templater();

return $templater->render( $html, [
    'title'   => 'The Best Title',
    'content' => 'Anything you want',
] );
```

The result will be:
```html
<div class="article">
    <h2>
        <!-- This is a tag with the name "title". -->
        The Best Title
    </h2>
    
    <div class="content">
        <!-- This is a tag with the name "content". -->
        Anything you want
    </div>

</div>
```

### Predefined tags

Predefined tags are tags that can render only values predefined by the template.


Predefined tag's modifier can only accept an integer value as index of one of the predefined values (starting from 0). Any invalid modifier value (non-integer or integer that points beyond of the array) will be considered as 0.

```html
<div class="{{#class=[first|second|third]}}"></div>
```

The default values' delimiter is `|`. You can change it by setting the `delimiter` property of the tag.

```html
<div class="{{#class=[first!!second!!third] delimiter=[!!]}}"></div>
```
Let's render the template with the values.

```php
$result = $templater->render( $html, [
    'class' => '1',
] );
```

The result will be:
```html
<div class="second"></div>
```

### Containers

Consider the example for tags again, but now we will use containers as values for the tags.
```html
<div class="article">
    <h2>{{title}}</h2>
    
    <div class="content">
        {{content}}
    </div>

</div>
```

Render the template with the containers.

```php
$title = new \iTRON\Anatomy\Container();
$container->addText( 'The Best Title' );

return $templater->render( $html, [
    'title'   => $title,
    'content' => 'Anything you want',
] );
```

The result will be the same.

But the more interesting part is that you can use the containers to build the content by means blocks.

### Blocks

Blocks are a way to have a component-like structure in the template.
Unlike tags, blocks themselves are not a placeholder for a value, but they just describe a pattern of the content. You can consider blocks as a template inside a template. This is why it does not matter where you put the block in the template.

```html
<div class="feed">
    <h1>{{title}}</h1>

    {{content}}
    
    <!-- This is a block with the name "article". -->
    [[#article]]
    <div class="article">
        <h2>{{title}}</h2>
    
        <div class="content">
            {{content}}
        </div>
    </div>
    [[/article]]
</div>
```

```php
$content = new \iTRON\Anatomy\Container();
$content->addBlock( 'article', [
    'title'   => 'The Best Title',
    'content' => 'Anything you want',
] );
$content->addBlock( 'article', [
    'title'   => 'There is no title',
    'content' => 'There is no content.',
] );

return $templater->render( $html, [
    'title'   => "Feed's Title",
    'content' => $content
] );
```

The result would be:
```html
<div class="feed">
    <h1>Feed's Title</h1>
    
    <!-- This is a container with the name "article". -->
    <div class="article">
        <h2>The Best Title</h2>
    
        <div class="content">
            Anything you want
        </div>
    </div>
    
    <div class="article">
        <h2>There is no title</h2>
    
        <div class="content">
            There is no content.
        </div>
    </div>
  
</div>
```

Add a plain text after blocks.
    
```php
$container = new \iTRON\Anatomy\Container();
$container->addBlock( 'article', [
    'title'   => 'The Best Title',
    'content' => 'Anything you want',
] );
$container->addBlock( 'article', [
    'title'   => 'There is no title',
    'content' => 'There is no content.',
] );

// Add a plain text after the blocks.
$container->addText( 'This is a plain text.' );

return $templater->render( $html, [
    'title'   => "Feed's Title",
    'content' => $container
] );
```

The result would be:
```html
<div class="feed">
    <h1>Feed's Title</h1>
  
    <div class="article">
        <h2>The Best Title</h2>
    
        <div class="content">
            Anything you want
        </div>
    </div>
    
    <div class="article">
        <h2>There is no title</h2>
    
        <div class="content">
            There is no content.
        </div>
    </div>
  
    This is a plain text.
  
</div>
```

### Nested blocks

You can even put blocks inside another blocks.

```html
<div class="items">

    {{items}}
    
    [[#item]]
    <div class="item">
        <div class="title">{{title}}</div>
        
        <div class="content">
            {{buttons}}
            
            [[#button]]
            <button>{{text}}</button>
            [[/button]]
        </div>
    </div>
    [[/item]]

</div>
```

```php
$buttons = new \iTRON\Anatomy\Container();
$button->addBlock( 'button', [
    'text' => 'Tap me',
] );
$button->addBlock( 'button', [
    'text' => 'Do not tap me',
] );
$button->addBlock( 'button', [
    'text' => 'I am not a button',
] );

$container = new \iTRON\Anatomy\Container();
$container->addBlock( 'item', [
    'title'   => 'The Best Title',
    'buttons' => $buttons,
] );
```

The result would be:
```html
<div class="items">

    <div class="item">
        <div class="title">The Best Title</div>
        
        <div class="content">
            <button>Tap me</button>
            
            <button>Do not tap me</button>
          
            <button>I am not a button</button>
        </div>

    </div>
    
</div>
```

As you can see, containers allow to have nested blocks with no restrictions on the blocks' depth.

And again, it does not matter what order you describe blocks. The next template is identical to the previous one functionally.

```html
<!-- General part -->
<div class="items">
    {{items}}
</div>
<!-- /General part -->


<!-- Item's block -->
[[#item]]
<div class="item">
	<div class="title">{{title}}</div>

	<div class="content">
        {{buttons}}
	</div>
</div>
[[/item]]
<!-- /Item's block -->

<!-- Button's block -->
[[#button]]
<button>{{text}}</button>
[[/button]]
<!-- /Button's block -->
```

### Detached blocks

Since we consider blocks as an almost independent template, you can use blocks out of context of the main template.

Suppose, you need to render a "button" block in a different place. Great, there's no need to duplicate the block's code. Just render the block separately.

```php
echo $templater->renderBlock(
    $html, 
    'button', 
    [ 'text' => 'Tap me' ] 
);
```
