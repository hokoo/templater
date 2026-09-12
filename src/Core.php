<?php

namespace iTRON\Anatomy;

use iTRON\Anatomy\Exception\InvalidTemplateDataException;
use iTRON\Anatomy\Exception\UnknownBlockException;
use Stringable;

final class Core {
    public const BLOCK_NAME_SCHEMA_KEY = 'block';
    public const DATA_SCHEMA_KEY = 'data';

    private const TAG_REGEX = <<<'REGEX'
~
\{\{
(?:
    \#(?P<predefined_tag>[a-zA-Z\d_\-]+)=\[(?P<predefined_values>.*?)\]
    (?:\s+delimiter=\[(?P<delimiter>.*?)\])?
    |
    (?P<tag>[a-zA-Z\d_\-]+)(?P<escape>\|e)?
)
\}\}
~sx
REGEX;

    private const PREDEFINED_DELIMITER = '|';

    /** @var array<string, string> */
    private array $blocks = [];
    private string $rendered = '';
    private string $preparedTemplate = '';
    private bool $parsed = false;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        public string $template,
        public array $data = []
    ) {
    }

    public function render( string $singleBlock = '' ): string {
        // An empty data set intentionally keeps the source template untouched.
        if ( empty( $this->data ) && empty( $singleBlock ) ) {
            $this->rendered = $this->template;
            return $this->rendered;
        }

        $this->extractBlocks();
        $this->prepareTemplate();

        $this->rendered = $this->renderBlock( $singleBlock, $this->data );

        return $this->getRendered();
    }

    /**
     * Extract block definitions and prepare the root template.
     */
    public function extractBlocks(): void {
        $parsed = ( new BlockParser() )->parse( $this->template );
        $this->blocks = $parsed['blocks'];
        $this->preparedTemplate = $parsed['template'];
        $this->parsed = true;
    }

    public function renderContainer( Container $container ): string {
        $result = '';

        foreach ( $container as $item ) {
            if ( ! is_array( $item ) || ! array_key_exists( self::DATA_SCHEMA_KEY, $item ) ) {
                throw new InvalidTemplateDataException( 'A container item must use the Anatomy element schema.' );
            }

            if (
                array_key_exists( self::BLOCK_NAME_SCHEMA_KEY, $item )
                && ! is_string( $item[ self::BLOCK_NAME_SCHEMA_KEY ] )
            ) {
                throw new InvalidTemplateDataException( 'A container block name must be a string.' );
            }

            $block = $item[ self::BLOCK_NAME_SCHEMA_KEY ] ?? '';
            $data = $item[ self::DATA_SCHEMA_KEY ];

            if ( '' !== $block ) {
                if ( ! is_array( $data ) ) {
                    throw new InvalidTemplateDataException(
                        sprintf( 'Data for block "%s" must be an array.', $block )
                    );
                }

                $result .= $this->renderBlock( $block, $data );
                continue;
            }

            $result .= $this->stringifyValue( $data, self::DATA_SCHEMA_KEY );
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderBlock( string $blockName, array $data ): string {
        return $this->processTags( $this->getTemplateForBlock( $blockName ), $data );
    }

    /**
     * Clears block definitions from the root template.
     */
    public function prepareTemplate(): Core {
        if ( ! $this->parsed ) {
            $this->extractBlocks();
        }

        return $this;
    }

    private function getTemplateForBlock( string $blockName ): string {
        if ( '' === $blockName ) {
            return $this->preparedTemplate;
        }

        if ( ! array_key_exists( $blockName, $this->blocks ) ) {
            throw new UnknownBlockException( sprintf( 'Block "%s" is not defined in the template.', $blockName ) );
        }

        return $this->blocks[ $blockName ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function processTags( string $template, array $data ): string {
        return (string) preg_replace_callback(
            self::TAG_REGEX,
            function ( array $matches ) use ( $data ): string {
                if ( ! empty( $matches['predefined_tag'] ) ) {
                    return $this->resolvePredefinedTag( $matches, $data );
                }

                $tag = $matches['tag'] ?? '';
                $value = array_key_exists( $tag, $data ) ? $data[ $tag ] : null;
                $rendered = $this->stringifyValue( $value, $tag );

                if ( ! empty( $matches['escape'] ) ) {
                    return htmlspecialchars(
                        $rendered,
                        ENT_QUOTES | ENT_SUBSTITUTE,
                        'UTF-8',
                        false
                    );
                }

                return $rendered;
            },
            $template
        );
    }

    /**
     * @param array<int|string, string> $matches
     * @param array<string, mixed>       $data
     */
    private function resolvePredefinedTag( array $matches, array $data ): string {
        $delimiter = ! empty( $matches['delimiter'] )
            ? $matches['delimiter']
            : self::PREDEFINED_DELIMITER;
        $values = explode( $delimiter, $matches['predefined_values'] );
        $modifier = $data[ $matches['predefined_tag'] ] ?? null;
        $index = is_int( $modifier ) && $modifier >= 0 ? $modifier : 0;

        return $values[ $index ] ?? $values[0];
    }

    private function stringifyValue( mixed $value, string $tag ): string {
        if ( null === $value ) {
            return '';
        }

        if ( is_array( $value ) ) {
            $result = '';

            foreach ( $value as $item ) {
                if ( is_array( $item ) ) {
                    throw new InvalidTemplateDataException(
                        sprintf( 'Nested arrays are not supported for tag "%s".', $tag )
                    );
                }

                $result .= $this->stringifyValue( $item, $tag );
            }

            return $result;
        }

        if ( is_scalar( $value ) || $value instanceof Stringable ) {
            return (string) $value;
        }

        throw new InvalidTemplateDataException(
            sprintf( 'Value of type "%s" is not supported for tag "%s".', get_debug_type( $value ), $tag )
        );
    }

    public function getRendered(): string {
        return $this->rendered;
    }
}
