# iTron Templater
An another templater for php.

## A Simple use

```
<div class="popup $1$s" data-popup="$2$s">
    <div class="popup__container">
        <div class="grid">
            <div class="col col_7 col_sm-6">
                <div class="popup__header">
                    <div class="popup__title">$3$s</div>
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

```
use iTRON;
return Templater->render( $template, [
    'foo', 
    'bar',
    'Where is the title?',
    $anything
] );
```

## An Advanced use

Repeater's tags

```
<div class="popup $1$s" data-popup="$2$s">
    <div class="popup__container">
        %3$s [[repeater_tag_name]]
        <div class="grid">
            <div class="col col_7 col_sm-6">
                <div class="popup__header">
                    <div class="popup__title">$1$s</div>
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

```
use iTRON;
return Templater->render( $template, [
    'foo',
    'bar',
    [
        [ 'tag' => 'repeater_tag_name', 'data' => [ 'Is this a title?', $anything ] ],
        [ 'tag' => 'repeater_tag_name', 'data' => [ 'There is no title', $anythingelse ] ],
    ]
] );
```

### Nested repeater's tags

```
<div class="popup $1$s" data-popup="$2$s">
    <div class="popup__container">
        %3$s [[repeater_tag_name]]
        <div class="grid">
            <div class="col col_7 col_sm-6">
                <div class="popup__header">
                    <div class="popup__title">$1$s</div>
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

```
use iTRON;

$anything = [ 
    [ 'tag' => 'form', 'data' => [ 'form_1', $someform1 ] ], 
    [ 'tag' => 'form', 'data' => [ 'form_2', $someform2 ] ], 
];
$anythingelse = [ 
    [ 'tag' => 'form', 'data' => [ 'form_3', $someform3 ] ], 
    [ 'tag' => 'form', 'data' => [ 'form_4', $someform4 ] ], 
];
return Templater->render( $template, [
    'foo',
    'bar',
    [
        [ 'tag' => 'repeater_tag_name', 'data' => [ 'Is this a title?', $anything ] ],
        [ 'tag' => 'repeater_tag_name', 'data' => [ 'There is no title', $anythingelse ] ],
    ]
] );
```

In fact, does not matter what order you describe the tags. The next template is identical for previous.
```
<div class="popup $1$s" data-popup="$2$s">
    <div class="popup__container">
        %3$s
    </div>
</div>

[[repeater_tag_name]]
    <div class="grid">
        <div class="col col_7 col_sm-6">
            <div class="popup__header">
                <div class="popup__title">$1$s</div>
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
