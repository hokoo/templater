# Anatomy: a templater for PHP

[![Latest Stable Version](https://poser.pugx.org/hokoo/templater/v)](//packagist.org/packages/hokoo/templater) 
[![PHPUnit Tests](https://github.com/hokoo/templater/actions/workflows/phpunit.yml/badge.svg)](https://github.com/hokoo/templater/actions/workflows/phpunit.yml)

HTML Templater for PHP.

<img src="assets/anatomy-logo.png" alt="Anatomy logo" width="300" style="float: right; margin: 0 0 20px 30px; max-width: 40%"/>

Lightweight, simple but powerful templater for rendering HTML templates in PHP.

I believe that you'd like to 
- keep your HTML clean and simple, and 
- maintain the logic in PHP, not in HTML.

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Getting Started](#getting-started)
  - [Tags](#a-tags)
  - [Predefined tags](#a-predefined-tags)
  - [Blocks & Containers](#a-blocks--containers)
  - [Detached mode](#a-detached-mode)
- [Back compatibility](#back-compatibility)


## Requirements
PHP 8.0 and later.

## Installation

```bash
composer require hokoo/templater
```


## Getting Started
Here are some examples of how to use the templater.

### :a: Tags
#### Example:
```html
<div class="article">
    <h2>
        <!-- This is a tag named "title". -->
        {{title}}
    </h2>
    
    <div class="content">
        <!-- This is a tag named "content". -->
        {{content}}
    </div>

</div>
```
#### PHP code:
```php
use iTRON\Anatomy\Templater;
$templater = new Templater();

return $templater->render( $html, [
    'title'   => 'The Best Title',
    'content' => 'Anything you want',
] );
```
#### The result will be:
```html
<div class="article">
    <h2>
        <!-- This is a tag named "title". -->
        The Best Title
    </h2>
    
    <div class="content">
        <!-- This is a tag named "content". -->
        Anything you want
    </div>

</div>
```
<details>
<summary>Read more theory about Tags</summary>

Tag - a point in the template where you can insert a value. Tag should be considered as a placeholder for a value. 

    In the Anatomy's paradigm, the 'tag' is only entity that can be replaced with a value.

The tag is a string that starts with `{{` and ends with `}}`.

So, in the example above, the `{{title}}` and `{{content}}` are tags.
Let's render the template with the values.
</details>

### :a: Predefined tags
#### Example:
```html
<div class="{{#class=[first|second|third]}}"></div>
```
#### PHP code:

```php
$templater = new \iTRON\Anatomy\Templater();

$result = $templater->render( $html, [
    'class' => 0,
] );
```
#### The result will be:
```html
<div class="first"></div>
```

<details>
<summary>Read more theory about Predefined Tags</summary>
Predefined tags are tags that can render only values predefined by the template.


Predefined tag's modifier can only accept an integer value as index of one of the predefined values (starting from 0). Any invalid modifier value (non-integer or integer that points beyond of the array) will be considered as 0.


The default values' delimiter is `|`. You can change it by setting the `delimiter` property of the tag.

```html
<div class="{{#class=[first!!second!!third] delimiter=[!!]}}"></div>
```
</details>

### :a: Blocks & Containers
#### Example:
```html
<div class="feed">
    <h1>{{title}}</h1>

    {{content}}
    
    <!-- This is a block named "article". -->
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

#### PHP code:

```php
// This is a container. 
// It can contain blocks and texts.
$content = new \iTRON\Anatomy\Container();

// Let's put block "article" into the container.
$content->addBlock( 'article', [
    'title'   => 'The Best Title',
    'content' => 'Anything you want',
] );

// Another one.
$content->addBlock( 'article', [
    'title'   => 'There is no title',
    'content' => 'There is no content.',
] );

// Or you can add a plain text.
$content->addText( 'Plain text.' );

$templater = new iTRON\Anatomy\Templater();

return $templater->render( $html, [
    'title'   => "Feed's Title",
    'content' => $content
] );
```

#### The result will be:
```html
<div class="feed">
    <h1>Feed's Title</h1>
    
    <!-- This is a block named "article". -->
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
  
    Plain text.
  
</div>
```

<details>
<summary>Read more theory about Blocks & Containers</summary>

Blocks are a way to have a component-like structure in the template. You can consider blocks as a template inside a template. 

You can define as many blocks as you want and render them in any order.

You can put one block into another block. This is how you can create a nested structure without any restrictions on the depth of nesting.
</details>

### :a: Detached mode

Since we consider blocks as an almost independent template, you can use blocks out of context of the main template.

Suppose, you need to render the "article" block from the template above in a different place. Great, there's no need to duplicate the block's code. Just render the block separately.

```php
$templater = new \iTRON\Anatomy\Templater();

echo $templater->renderBlock(
    $html, 
    'article', 
    [ 
      'title' => 'Title',
      'content' => 'Content'
    ] 
);
```
The result would be:

```html
<div class="article">
    <h2>Title</h2>
    
    <div class="content">
        Content
    </div>
</div>
```
## Back compatibility
Version 3 is still supported. Syntax of the older versions is not supported anymore.

To use v3 syntax, you have to instantiate the legacy version of the templater:

```php
$templater = new \iTRON\Templater\Templater();

// Instead of
// $templater = new \iTRON\Anatomy\Templater();
```
