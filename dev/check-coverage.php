#!/usr/bin/env php
<?php

declare(strict_types=1);

const MINIMUM_LINE_COVERAGE = 95.0;
const MINIMUM_PATH_COVERAGE = 75.0;

$projectRoot = dirname(__DIR__);
$phpunit = $projectRoot . '/vendor/phpunit/phpunit/phpunit';

if (!is_file($phpunit)) {
	fwrite(STDERR, "PHPUnit is not installed. Run composer install first.\n");
	exit(2);
}

$report = tempnam(sys_get_temp_dir(), 'templater-coverage-');
if ($report === false) {
	fwrite(STDERR, "Could not create a temporary coverage report.\n");
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
	fwrite(STDERR, "Could not read the PHPUnit coverage report.\n");
	exit(2);
}

echo $coverage;

if (
	preg_match('/^\s*Lines:\s+([0-9]+(?:\.[0-9]+)?)%/m', $coverage, $lineMatch) !== 1
	|| preg_match('/^\s*Paths:\s+([0-9]+(?:\.[0-9]+)?)%/m', $coverage, $pathMatch) !== 1
) {
	fwrite(STDERR, "Could not find line and path coverage totals in the PHPUnit report.\n");
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
	fwrite(STDERR, implode("\n", $failures) . "\n");
	exit(1);
}

printf(
	"Coverage thresholds passed: lines %.2f%%, paths %.2f%%.\n",
	$lineCoverage,
	$pathCoverage
);
