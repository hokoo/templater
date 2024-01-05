# iTRON Templater
HTML Templater for PHP.

Stop using the Twig. It's too painful and meaningless. We do not need to reinvent the wheel and get a new dialect above PHP. We need to make it better.

```php
use iTRON\Templater\Templater;
$t = new Templater();

/**
 * HTML Template
 * @var string 
 */
$template;
```
## Simple using

```html
<div class="popup %1$s" data-popup="%2$s">
    <div class="popup__container">
        <div class="grid">
            <div class="col col_7 col_sm-6">
                <div class="popup__header">
                    <div class="popup__title">%3$s</div>
                </div>
            </div>
            <div class="col col_12 col_sm-6">
                <div class="popup__form">
                    %4$s
                </div>
            </div>
        </div>
    </div>
</div>
```

```php

return $t->render( $template, [
    'foo', 
    'bar',
    'Where is the title?',
    'anything'
] );
```

The result will be:
```html
<div class="popup foo" data-popup="bar">
    <div class="popup__container">
        <div class="grid">
            <div class="col col_7 col_sm-6">
                <div class="popup__header">
                    <div class="popup__title">Where is the title?</div>
                </div>
            </div>
            <div class="col col_12 col_sm-6">
                <div class="popup__form">
                    anything
                </div>
            </div>
        </div>
    </div>
</div>
```

## Advanced using

Repeater's tags

```html
<div class="popup %1$s" data-popup="%2$s">
    <div class="popup__container">
        %3$s [[repeater_tag_name]]
        <div class="grid">
            <div class="col col_7 col_sm-6">
                <div class="popup__header">
                    <div class="popup__title">%1$s</div>
                </div>
            </div>
            <div class="col col_12 col_sm-6">
                <div class="popup__form">
                    %2$s
                </div>
            </div>
        </div>
        [[/repeater_tag_name]]
    </div>
</div>
```

```php
return $t->render( $template, [
    'foo',
    'bar',
    [
        [ 'tag' => 'repeater_tag_name', 'data' => [ 'Is this a title?', 'anything' ] ],
        [ 'tag' => 'repeater_tag_name', 'data' => [ 'There is no title', 'anythingelse' ] ],
    ]
] );
```

The result will be:
```html
<div class="popup foo" data-popup="bar">
    <div class="popup__container">
        <div class="grid">
            <div class="col col_7 col_sm-6">
                <div class="popup__header">
                    <div class="popup__title">Is this a title?</div>
                </div>
            </div>
            <div class="col col_12 col_sm-6">
                <div class="popup__form">
                    anything
                </div>
            </div>
        </div>
        <div class="grid">
            <div class="col col_7 col_sm-6">
                <div class="popup__header">
                    <div class="popup__title">There is no title</div>
                </div>
            </div>
            <div class="col col_12 col_sm-6">
                <div class="popup__form">
                    anythingelse
                </div>
            </div>
        </div>
    </div>
</div>
```

### Nested repeater's tags

```html
<div class="popup %1$s" data-popup="%2$s">
    <div class="popup__container">
        %3$s [[repeater_tag_name]]
        <div class="grid">
            <div class="col col_7 col_sm-6">
                <div class="popup__header">
                    <div class="popup__title">%1$s</div>
                </div>
            </div>
            <div class="col col_12 col_sm-6">
                <div class="popup__form">
                    %2$s [[form]]
                        <form id="%s">
                            %s
                        </form>
                    [[/form]]
                </div>
            </div>
        </div>
        [[/repeater_tag_name]]
    </div>
</div>
```

```php
$repeated_forms_a = [ 
    [ 'tag' => 'form', 'data' => [ 'form_1', $someform1 ] ], 
    [ 'tag' => 'form', 'data' => [ 'form_2', $someform2 ] ], 
];
$repeated_forms_b = [ 
    [ 'tag' => 'form', 'data' => [ 'form_3', $someform3 ] ], 
    [ 'tag' => 'form', 'data' => [ 'form_4', $someform4 ] ], 
];

return $t->render( $template, [
    'foo',
    'bar',
    [
        [ 'tag' => 'repeater_tag_name', 'data' => [ 'Is this a title?',  $repeated_forms_a ] ],
        [ 'tag' => 'repeater_tag_name', 'data' => [ 'There is no title', $repeated_forms_b ] ],
    ]
] );
```

In fact, it does not matter what order you describe the tags. The next template is identical for the previous one functionally.
```html
<div class="popup %1$s" data-popup="%2$s">
    <div class="popup__container">
        %3$s
    </div>
</div>

[[repeater_tag_name]]
    <div class="grid">
        <div class="col col_7 col_sm-6">
            <div class="popup__header">
                <div class="popup__title">%1$s</div>
            </div>
        </div>
        <div class="col col_12 col_sm-6">
            <div class="popup__form">
                %2$s
            </div>
        </div>
    </div>
[[/repeater_tag_name]]

[[form]]
    <form id="%s">
        %s
    </form>
[[/form]]
```
