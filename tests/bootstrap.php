<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/classes/StaticDateTimeFactory.php';

Tester\Environment::setup();


function test(string $description, Closure $closure): void
{
	$closure();
}
