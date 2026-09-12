# Changelog

## 4.2.0 - 2026-09-12

### Added

- Deterministic exceptions for invalid template data, malformed block syntax,
  and unknown detached blocks.
- Opt-in HTML escaping with `{{tag|e}}`.
- Contract documentation and regression coverage for edge cases.
- PHP 8.0–8.5 CI test matrix, static analysis, dependency audit, and coverage
  gates.

### Changed

- Replaced block extraction by regular expression with a stack-based parser.
- Defined value normalization for scalars, `Stringable` values, and flat arrays.
- Made inserted data opaque to later template parsing stages.
- Declared PHP `^8.0` in Composer metadata.
- Deprecated the version 3 compatibility API ahead of its planned removal in
  version 5.

### Fixed

- Array-to-string warnings in regular tags.
- Uninitialized context errors from standalone containers now produce a clear
  `LogicException`.
- Cyclic container and array data now fail explicitly instead of exhausting
  process memory.
- Missing or invalid predefined indexes now consistently select the first value.
- Legacy rendering of `"0"`, replacement strings containing `$1` or backslashes,
  and unknown repeater diagnostics.

### Compatibility

- `render( $template, [] )` intentionally continues to return the original
  template without parsing it.
- Existing `{{tag}}` values remain unescaped in 4.x.
- Malformed templates and duplicate block definitions now fail explicitly
  instead of producing partial output.
