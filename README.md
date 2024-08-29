# iTRON Templater
[![PHPUnit Tests](https://github.com/hokoo/templater/actions/workflows/phpunit.yml/badge.svg)](https://github.com/hokoo/templater/actions/workflows/phpunit.yml)

HTML Templater for PHP.

Lightweight, simple but powerful templater for rendering HTML templates in PHP.

I believe that you'd like to 
- keep your HTML clean and simple and 
- bring logic to PHP (PHP itself is a templater, isn't it?). 

## Table of Contents

* [Installation](#installation)
* [Requirements](#requirements)
* [Getting Started](#getting-started)
* [Simple using](#simple-using)
* [Repeaters](#repeaters)
  * [Nested repeater's tags](#nested-repeaters-tags)
* [Predefined values](#predefined-values)

## Installation

```bash
composer require hokoo/templater
```

## Requirements
PHP 7.4 and later.


## Getting Started
Here are some examples of how to use the templater.

## Simple using

```php
use iTRON\Templater\Templater;
$t = new Templater();

/**
 * HTML Template
 * @var string 
 */
$template;
```

```php
$html = <<<TEMPLATE
<div class="popup %s" data-popup="%s">
    
    <div class="title">
        %s
    </div>
    
    <div class="content">
        %s
    </div>

</div>
TEMPLATE;
```

```php
return $t->render( $template, [
    'class-foo', 
    'bar',
    'The Best Title',
    'Anything you want'
] );
```

The result will be:
```html
<div class="popup class-foo" data-popup="bar">
    
    <div class="title">
        The Best Title
    </div>
    
    <div class="content">
        Anything you want
    </div>
    
</div>
```
You can also use the numeric modifiers:

```php
$html = <<<TEMPLATE
<div class="popup %1$s" data-popup="%2$s">
    
    <div class="title">
        %3$s
    </div>
    
    <div class="content">
        %4$s
    </div>

</div>
TEMPLATE;
```

## Repeaters

```php
$html = <<<TEMPLATE
<div class="popup %s" data-popup="%s">

    %s 
    [[repeater_tag_name]]
        <div class="title">
            %s
        </div>
    
        <div class="content">
            %s
        </div>
    [[/repeater_tag_name]]

</div>
TEMPLATE;
```

```php
return $t->render( $template, [
    'class-foo',
    'bar',
    [
        [ 'tag' => 'repeater_tag_name', 'data' => [ 'The Best Title', 'Anything you want.' ] ],
        [ 'tag' => 'repeater_tag_name', 'data' => [ 'There is no title', 'There is no content.' ] ],
    ]
] );
```

The result would be:
```html
<div class="popup class-foo" data-popup="bar">

    <div class="title">
        The Best Title
    </div>

    <div class="content">
        Anything you want.
    </div>

    <div class="title">
        There is no title
    </div>

    <div class="content">
        There is no content.
    </div>
```

### Nested repeater's tags

You can even put repeater's tags inside another repeater's tags.

```php
$html = <<<TEMPLATE
<div class="popup %1$s" data-popup="%2$s">

    %3$s [[repeater_tag_name]]
    <div class="item">
        <div class="title">%1$s</div>
        
        <div class="content">
            %2$s
            
            [[button]]
            <button id="%s">
                %s
            </button>
            [[/button]]
        </div>

    </div>
    [[/repeater_tag_name]]

</div>
TEMPLATE;
```

```php
$repeated_forms_a = [ 
    [ 'tag' => 'button', 'data' => [ 'button-1', 'Tap me' ] ], 
    [ 'tag' => 'button', 'data' => [ 'button-2', 'Do not tap me' ] ], 
];
$repeated_forms_b = [ 
    [ 'tag' => 'button', 'data' => [ 'button-3', 'I ain\'t a button' ] ], 
    [ 'tag' => 'button', 'data' => [ 'button-4', 'Ok' ] ], 
];

return $t->render( $template, [
    'class-foo',
    'bar',
    [
        [ 'tag' => 'repeater_tag_name', 'data' => [ 'The Best Title',  $repeated_forms_a ] ],
        [ 'tag' => 'repeater_tag_name', 'data' => [ 'There is no title', $repeated_forms_b ] ],
    ]
] );
```

The result would be:
```html
<div class="popup class-foo" data-popup="bar">

    <div class="item">
        <div class="title">The Best Title</div>
        
        <div class="content">
            <button id="button-1">
                Tap me
            </button>
            
            <button id="button-2">
                Do not tap me
            </button>
        </div>

    </div>
    
    <div class="item">
        <div class="title">There is no title</div>
        
        <div class="content">
            <button id="button-3">
                I ain't a button
            </button>
            
            <button id="button-4">
                Ok
            </button>
        </div>
    </div>
    
</div>
```

In fact, it does not matter what order you describe the tags. The next template is identical for the previous one functionally.

```php
$html = <<<TEMPLATE
<div class="popup %1$s" data-popup="%2$s">
    %3$s
</div>

[[repeater_tag_name]]
<div class="item">
	<div class="title">%1$s</div>

	<div class="content">
		%2$s
    </div>

</div>
[[/repeater_tag_name]]

[[button]]
<button id="%s">
	%s
</button>
[[/button]]
TEMPLATE;
```

## Predefined values

Predefined modifier can only accept an integer value as index (starting from 0) of one of the predefined values. Any invalid value will be considered as 0. 

```php
$tpl = <<<TEMPLATE
<div class="[[classname1|classname2|classname3/]]%d"></div>
TEMPLATE;

$result = $templater->render( $tpl, [
    1,
] );
```

The result will be:
```html
<div class="classname2"></div>
```
