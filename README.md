# Anatomy: a templater for PHP

[![Latest Stable Version](https://poser.pugx.org/hokoo/templater/v)](//packagist.org/packages/hokoo/templater) 
[![PHPUnit Tests](https://github.com/hokoo/templater/actions/workflows/phpunit.yml/badge.svg)](https://github.com/hokoo/templater/actions/workflows/phpunit.yml)

HTML Templater for PHP.

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
  - [Repeaters](#repeaters)
  - [Nested repeaters](#nested-repeaters)


## Requirements
PHP 7.4 and later.

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
TEMPLATE;
```


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

You can use predefined tags for your tags. Put the predefined values in the tag separated by a pipe `|`.


Predefined tag's modifier can only accept an integer value as index of one of the predefined values (starting from 0). Any invalid modifier value (non-integer or integer that points beyond of the array) will be considered as 0.

```html
$html = <<<TEMPLATE
<div class="{{class[first|second|third]}}"></div>
```

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

```php
$title = new \iTRON\Anatomy\Container();
$container->addText( 'The Best Title' );

return $templater->render( $html, [
    'title'   => $title,
    'content' => 'Anything you want',
] );
```

The result will be the same.

But the more interesting part is that you can the containers to repeat the content.

### Repeaters

```html
<div class="feed">
    <h1>{{title}}</h1>

    {{content}}
    
    <!-- This is a repeater with the name "article". -->
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
$content->addRepeater( 'article', [
    'title'   => 'The Best Title',
    'content' => 'Anything you want',
] );
$content->addRepeater( 'article', [
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

Add a plain text after the repeaters.
    
```php
$container = new \iTRON\Anatomy\Container();
$container->addRepeater( 'article', [
    'title'   => 'The Best Title',
    'content' => 'Anything you want',
] );
$container->addRepeater( 'article', [
    'title'   => 'There is no title',
    'content' => 'There is no content.',
] );

// Add a plain text after the repeaters.
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

### Nested repeaters

You can even put repeater's tags inside another repeater's tags.

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
$button->addRepeater( 'button', [
    'text' => 'Tap me',
] );
$button->addRepeater( 'button', [
    'text' => 'Do not tap me',
] );
$button->addRepeater( 'button', [
    'text' => 'I am not a button',
] );

$container = new \iTRON\Anatomy\Container();
$container->addRepeater( 'item', [
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

As you can see, containers allow to have nested repeaters without restrictions on the repeater's depth.

And speaking about the template, it does not matter what order you describe the repeaters. The next template is identical to the previous one functionally.

```html
<!-- General part -->
<div class="items">
    {{items}}
</div>
<!-- /General part -->


<!-- Item's repeater -->
[[#item]]
<div class="item">
	<div class="title">{{title}}</div>

	<div class="content">
        {{buttons}}
	</div>
</div>
[[/item]]
<!-- /Item's repeater -->

<!-- Button's repeater -->
[[#button]]
<button>{{text}}</button>
[[/button]]
<!-- /Button's repeater -->
```
