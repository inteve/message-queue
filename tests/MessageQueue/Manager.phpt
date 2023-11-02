<?php

declare(strict_types=1);

use Inteve\MessageQueue\Manager;
use Inteve\MessageQueue\MemoryAdapter;
use Inteve\MessageQueue\Message;
use Inteve\MessageQueue\Result;
use Tester\Assert;

require __DIR__ . '/../bootstrap.php';


test('Basic', function () {
	$logs = [];
	$logger = function ($data) use (&$logs) {
		$logs[] = $data;
	};

	$adapter = new MemoryAdapter(function () {});
	$manager = new Manager(
		$adapter,
		\Inteve\MessageQueue\Tests\StaticDateTimeFactory::fromString('now')
	);
	Assert::true(count($logs) === 0);

	$manager->fetch('testQueue', $logger);
	Assert::true(count($logs) === 0);

	$manager->create('testQueue', [
		'value' => 5,
	], NULL, 10);

	$manager->create('testQueue', [
		'value' => 10,
	]);

	$manager->create('testQueue2', [
		'value' => 20,
	]);

	$manager->fetch('testQueue', $logger);
	Assert::true(count($logs) === 1);
	Assert::same([
		['value' => 10],
	], $logs);
});


test('Multifetch - from ALL', function () {
	$adapter = new MemoryAdapter(function () {});
	$manager = new Manager(
		$adapter,
		\Inteve\MessageQueue\Tests\StaticDateTimeFactory::fromString('now')
	);

	$messageA = $manager->create('a', [
		'value' => 'a',
	]);

	$messageB = $manager->create('b', [
		'value' => 'b',
	]);

	$messageC = $manager->create('c', [
		'value' => 'c',
	]);

	Assert::same(Message::STATUS_NEW, $messageA->getStatus());
	Assert::same(Message::STATUS_NEW, $messageB->getStatus());
	Assert::same(Message::STATUS_NEW, $messageC->getStatus());

	$manager->multiFetch([
		'b' => function () {},
		'c' => function () {},
	], 2);

	Assert::same(Message::STATUS_FAILED, $messageA->getStatus()); // missing handler
	Assert::same(Message::STATUS_DONE, $messageB->getStatus());
	Assert::same(Message::STATUS_NEW, $messageC->getStatus());
});


test('Multifetch - from LISTED ONLY', function () {
	$adapter = new MemoryAdapter(function () {});
	$manager = new Manager(
		$adapter,
		\Inteve\MessageQueue\Tests\StaticDateTimeFactory::fromString('now')
	);

	$messageA = $manager->create('a', [
		'value' => 'a',
	]);

	$messageB = $manager->create('b', [
		'value' => 'b',
	]);

	$messageC = $manager->create('c', [
		'value' => 'c',
	]);

	Assert::same(Message::STATUS_NEW, $messageA->getStatus());
	Assert::same(Message::STATUS_NEW, $messageB->getStatus());
	Assert::same(Message::STATUS_NEW, $messageC->getStatus());

	$manager->multiFetch([
		'b' => function () {},
		'c' => function () {},
	], 2, Manager::FROM_LISTED_ONLY);

	Assert::same(Message::STATUS_NEW, $messageA->getStatus()); // missing handler
	Assert::same(Message::STATUS_DONE, $messageB->getStatus());
	Assert::same(Message::STATUS_DONE, $messageC->getStatus());
});


test('Failing', function () {
	$failingHandler = function ($data, Result $result) {
		$result->fail();
	};

	$adapter = new MemoryAdapter(function () {});
	$manager = new Manager(
		$adapter,
		\Inteve\MessageQueue\Tests\StaticDateTimeFactory::fromString('now')
	);

	$message = $manager->create('testQueue', [
		'value' => 5,
	]);

	Assert::same(0, $message->getFails());
	Assert::same(Message::STATUS_NEW, $message->getStatus());

	for ($i = 0; $i <= Manager::FAIL_LIMIT; $i++) {
		$manager->fetch('testQueue', $failingHandler);
	}

	Assert::same(10, $message->getFails());
	Assert::same(Message::STATUS_FAILED, $message->getStatus());

	$manager->fetch('testQueue', $failingHandler);
	Assert::same(10, $message->getFails());
});


test('Failing by Exception', function () {
	$failingHandler = function ($data) {
		throw new \RuntimeException("Exception");
	};

	$adapter = new MemoryAdapter(function () {});
	$manager = new Manager(
		$adapter,
		\Inteve\MessageQueue\Tests\StaticDateTimeFactory::fromString('now')
	);

	$message = $manager->create('testQueue', [
		'value' => 5,
	]);

	Assert::same(0, $message->getFails());
	Assert::same(Message::STATUS_NEW, $message->getStatus());

	for ($i = 0; $i <= Manager::FAIL_LIMIT; $i++) {
		$manager->fetch('testQueue', $failingHandler);
	}

	Assert::same(10, $message->getFails());
	Assert::same(Message::STATUS_FAILED, $message->getStatus());

	$manager->fetch('testQueue', $failingHandler);
	Assert::same(10, $message->getFails());
});


test('Deferring', function () {
	$deferringHandler = function ($data, Result $result) {
		$result->defer(5);
	};

	$adapter = new MemoryAdapter(function () {});
	$manager = new Manager(
		$adapter,
		\Inteve\MessageQueue\Tests\StaticDateTimeFactory::fromString('now')
	);

	$message = $manager->create('testQueue', [
		'value' => 5,
	]);

	Assert::same(0, $message->getFails());
	Assert::same(Message::STATUS_NEW, $message->getStatus());

	for ($i = 0; $i <= Manager::FAIL_LIMIT; $i++) {
		$manager->fetch('testQueue', $deferringHandler);
	}

	Assert::same(0, $message->getFails());
	Assert::same(Message::STATUS_NEW, $message->getStatus());
});
