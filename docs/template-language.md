# Anatomy template language

This document defines the public rendering contract for Anatomy 4.2.

## Rendering modes

`Templater::render()` deliberately treats an empty data array as a request to
leave the source untouched:

```php
$templater->render( 'Hello {{name}}', [] ); // Hello {{name}}
```

This bypass includes tag replacement, block extraction, and syntax validation.
It preserves the behavior of previous releases. In contrast,
`Templater::renderBlock()` parses and renders the requested block when its data
argument is omitted, which makes hard-coded detached blocks useful.

## Regular tags and values

`{{name}}` inserts a raw value. A missing tag or `null` renders as an empty
string. Scalars and `Stringable` objects are converted to strings. A flat array
of those values is concatenated in iteration order. Nested arrays, resources,
and objects that are not stringable raise `InvalidTemplateDataException`.

Inserted values are opaque: template-like text inside data is not parsed a
second time.

## HTML escaping

`{{name|e}}` applies HTML escaping with `ENT_QUOTES | ENT_SUBSTITUTE`, UTF-8,
and double encoding disabled:

```html
<p>{{user_input|e}}</p>
```

The existing `{{name}}` syntax remains raw for 4.x compatibility and must only
receive trusted or already-sanitized HTML. HTML escaping is not sufficient for
JavaScript, CSS, or URL contexts; values for those contexts require a suitable
context-specific encoder. An escaped placeholder used in an HTML attribute must
be quoted, for example `data-label="{{label|e}}"`; unquoted attribute values are
not supported as a safe output context.

## Predefined tags

A predefined tag selects a template-owned value:

```html
{{#size=[small|medium|large]}}
{{#size=[small!!medium!!large] delimiter=[!!]}}
```

Only a non-negative integer is a valid index. Missing keys, non-integer values,
negative integers, and out-of-range indexes select the first value.

## Blocks

A block definition uses matching markers:

```html
[[#card]]<article>{{title}}</article>[[/card]]
```

Block names contain ASCII letters, digits, underscores, and hyphens. Every
definition name must be unique within a template. A definition may be rendered
any number of times, including recursively through nested `Container` values.
Distinct definitions may be nested; nested definitions are removed from their
parent body and remain reusable by name.

Invalid names, unterminated markers, unclosed definitions, unexpectedly closed,
mismatched, improperly nested, and duplicate block definitions raise
`TemplateSyntaxException`. Requesting an undefined block through
`renderBlock()` raises `UnknownBlockException`.

## Containers

Containers are bound to the rendering context by `Templater`. Converting a
standalone container to a string raises `LogicException`; pass it as template
data instead. Container block items must contain an array of block data.

## Legacy lifecycle

The `iTRON\Templater\Templater` API is deprecated as of 4.2. It receives
compatibility fixes during the 4.x lifecycle but no new features or parser
rewrite. It is planned for removal in 5.0. New code should use
`iTRON\Anatomy\Templater`.
