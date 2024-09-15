<?php

namespace iTRON\Anatomy;

final class Core {
	const REPEATER_SCHEMA_KEY = 'repeater';
	const DATA_SCHEMA_KEY = 'data';

	private string $repeater_regex =
		'/\[\[#(?P<' . self::REPEATER_SCHEMA_KEY . '>.+)\]\](?P<' . self::DATA_SCHEMA_KEY . '>.+)\[\[\/(?P=' . self::REPEATER_SCHEMA_KEY . ')\]\]/mUs';

	private string $tag_regex = '/\{\{(?P<tag>[a-zA-Z\d_\-]*)\}\}/mUs';
	private string $predefined_regex = '/\{\{#(?P<tag>[a-zA-Z\d_\-]+)=\[(?P<values>.+)\]\s?(?P<delimiter>delimiter=\[(.+)\])*\}\}/mUs';
	private string $predefined_delimiter = '|';

	public function render( $subject, $data = [], $detached = false ) {
		// @todo
	}
}
