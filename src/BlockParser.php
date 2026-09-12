<?php

namespace iTRON\Anatomy;

use iTRON\Anatomy\Exception\TemplateSyntaxException;

/**
 * Parses block definitions into the root template and reusable block bodies.
 *
 * @internal
 */
final class BlockParser {
    private const TOKEN_PREFIX_REGEX = '/\[\[(#|\/)/';
    private const BLOCK_NAME_REGEX = '/^[a-zA-Z\d_\-]+$/D';

    /**
     * @return array{template: string, blocks: array<string, string>}
     */
    public function parse( string $template ): array {
        $tokens = $this->tokenize( $template );

        /** @var list<array{name: string|null, content: string, offset: int}> $frames */
        $frames = [
            [
                'name'    => null,
                'content' => '',
                'offset'  => 0,
            ],
        ];
        /** @var array<string, string> $blocks */
        $blocks = [];
        /** @var array<string, true> $defined */
        $defined = [];
        $cursor = 0;

        foreach ( $tokens as $tokenData ) {
            $token = $tokenData['token'];
            $offset = $tokenData['offset'];
            $type = $tokenData['type'];
            $name = $tokenData['name'];
            $frameIndex = count( $frames ) - 1;

            if ( 1 !== preg_match( self::BLOCK_NAME_REGEX, $name ) ) {
                throw new TemplateSyntaxException(
                    sprintf( 'Invalid block name "%s" at offset %d.', $name, $offset )
                );
            }

            $frames[ $frameIndex ]['content'] .= substr( $template, $cursor, $offset - $cursor );
            $cursor = $offset + strlen( $token );

            if ( '#' === $type ) {
                if ( isset( $defined[ $name ] ) ) {
                    throw new TemplateSyntaxException(
                        sprintf( 'Block "%s" is defined more than once at offset %d.', $name, $offset )
                    );
                }

                $defined[ $name ] = true;
                $frames[] = [
                    'name'    => $name,
                    'content' => '',
                    'offset'  => $offset,
                ];
                continue;
            }

            if ( 1 === count( $frames ) ) {
                throw new TemplateSyntaxException(
                    sprintf( 'Unexpected closing marker for block "%s" at offset %d.', $name, $offset )
                );
            }

            $openFrame = $frames[ count( $frames ) - 1 ];
            if ( $openFrame['name'] !== $name ) {
                throw new TemplateSyntaxException(
                    sprintf(
                        'Closing marker for block "%s" at offset %d does not match open block "%s" at offset %d.',
                        $name,
                        $offset,
                        $openFrame['name'],
                        $openFrame['offset']
                    )
                );
            }

            array_pop( $frames );
            $blocks[ $name ] = $openFrame['content'];
        }

        $frameIndex = count( $frames ) - 1;
        $frames[ $frameIndex ]['content'] .= substr( $template, $cursor );

        if ( 1 !== count( $frames ) ) {
            $openFrame = $frames[ count( $frames ) - 1 ];
            throw new TemplateSyntaxException(
                sprintf( 'Block "%s" opened at offset %d is not closed.', $openFrame['name'], $openFrame['offset'] )
            );
        }

        return [
            'template' => $frames[0]['content'],
            'blocks'   => $blocks,
        ];
    }

    /**
     * Recognize every block-marker prefix before validating its complete name.
     *
     * @return list<array{token: string, type: string, name: string, offset: int}>
     */
    private function tokenize( string $template ): array {
        $tokens = [];
        $scanOffset = 0;
        $templateLength = strlen( $template );

        while ( $scanOffset < $templateLength ) {
            $match = [];
            $matched = preg_match(
                self::TOKEN_PREFIX_REGEX,
                $template,
                $match,
                PREG_OFFSET_CAPTURE,
                $scanOffset
            );

            if ( 1 !== $matched ) {
                break;
            }

            $offset = $match[0][1];
            $endOffset = strpos( $template, ']]', $offset + 3 );
            if ( false === $endOffset ) {
                throw new TemplateSyntaxException(
                    sprintf( 'Block marker opened at offset %d is not terminated.', $offset )
                );
            }

            $tokenLength = $endOffset + 2 - $offset;
            $tokens[] = [
                'token'  => substr( $template, $offset, $tokenLength ),
                'type'   => $match[1][0],
                'name'   => substr( $template, $offset + 3, $endOffset - $offset - 3 ),
                'offset' => $offset,
            ];
            $scanOffset = $endOffset + 2;
        }

        return $tokens;
    }
}
