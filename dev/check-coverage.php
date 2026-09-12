#!/usr/bin/env php
<?php

declare(strict_types=1);

const MINIMUM_LINE_COVERAGE = 95.0;
const MINIMUM_PATH_COVERAGE = 75.0;

function reportError(string $message): void {
	fwrite(STDERR, $message . "\n");

	if (getenv('GITHUB_ACTIONS') !== 'true') {
		return;
	}

	$annotation = str_replace(
		['%', "\r", "\n"],
		['%25', '%0D', '%0A'],
		$message
	);
	fwrite(STDOUT, "::error title=Coverage gate::{$annotation}\n");
}

$projectRoot = dirname(__DIR__);
$phpunit = $projectRoot . '/vendor/phpunit/phpunit/phpunit';

if (!is_file($phpunit)) {
	reportError('PHPUnit is not installed. Run composer install first.');
	exit(2);
}

$report = tempnam(sys_get_temp_dir(), 'templater-coverage-');
if ($report === false) {
	reportError('Could not create a temporary coverage report.');
	exit(2);
}

$command = sprintf(
	'%s %s --configuration %s --path-coverage --coverage-text=%s',
	escapeshellarg(PHP_BINARY),
	escapeshellarg($phpunit),
	escapeshellarg($projectRoot . '/phpunit.xml.dist'),
	escapeshellarg($report)
);

passthru($command, $testExitCode);

if ($testExitCode !== 0) {
	@unlink($report);
	exit($testExitCode);
}

$coverage = file_get_contents($report);
@unlink($report);

if ($coverage === false) {
	reportError('Could not read the PHPUnit coverage report.');
	exit(2);
}

echo $coverage;

if (
	preg_match('/^\s*Lines:\s+([0-9]+(?:\.[0-9]+)?)%/m', $coverage, $lineMatch) !== 1
	|| preg_match('/^\s*Paths:\s+([0-9]+(?:\.[0-9]+)?)%/m', $coverage, $pathMatch) !== 1
) {
	reportError('Could not find line and path coverage totals in the PHPUnit report.');
	exit(2);
}

$lineCoverage = (float) $lineMatch[1];
$pathCoverage = (float) $pathMatch[1];
$failures = [];

if ($lineCoverage < MINIMUM_LINE_COVERAGE) {
	$failures[] = sprintf(
		'Line coverage %.2f%% is below the required %.2f%%.',
		$lineCoverage,
		MINIMUM_LINE_COVERAGE
	);
}

if ($pathCoverage < MINIMUM_PATH_COVERAGE) {
	$failures[] = sprintf(
		'Path coverage %.2f%% is below the required %.2f%%.',
		$pathCoverage,
		MINIMUM_PATH_COVERAGE
	);
}

if ($failures !== []) {
	$classMatches = [];
	preg_match_all(
		'/^iTRON\\\\Anatomy\\\\[^\r\n]+\R\s+Methods:.*$/m',
		$coverage,
		$classMatches
	);
	$details = $classMatches[0] === []
		? ''
		: "\n\nPer-class coverage:\n" . implode("\n", $classMatches[0]);
	reportError(implode("\n", $failures) . $details);
	exit(1);
}

printf(
	"Coverage thresholds passed: lines %.2f%%, paths %.2f%%.\n",
	$lineCoverage,
	$pathCoverage
);
