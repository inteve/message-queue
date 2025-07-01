# Inteve\MessageQueue

[![Build Status](https://github.com/inteve/message-queue/workflows/Build/badge.svg)](https://github.com/inteve/message-queue/actions)
[![Downloads this Month](https://img.shields.io/packagist/dm/inteve/message-queue.svg)](https://packagist.org/packages/inteve/message-queue)
[![Latest Stable Version](https://poser.pugx.org/inteve/message-queue/v/stable)](https://github.com/inteve/message-queue/releases)
[![License](https://img.shields.io/badge/license-New%20BSD-blue.svg)](https://github.com/inteve/message-queue/blob/master/license.md)

Simple message queue

<a href="https://www.janpecha.cz/donate/"><img src="https://buymecoffee.intm.org/img/donate-banner.v1.svg" alt="Donate" height="100"></a>


## Installation

[Download a latest package](https://github.com/inteve/message-queue/releases) or use [Composer](http://getcomposer.org/):

```
composer require inteve/message-queue
```

Inteve\MessageQueue requires PHP 8.0 or later.


## Usage

``` php
$exceptionHandler = function (\Throwable $e) {
	\Tracy\Debugger::log($e, \Tracy\Debugger::EXCEPTION);
};
$dateTimeFactory = new MyDateTimeFactory; // implementation of Phig\DateTimeFactory
$adapter = new MemoryAdapter($exceptionHandler);
$manager = new Manager($adapter, $dateTimeFactory);
```

### Insert message

``` php
$manager->create(
	queue: 'name-of-queue',
	data: [
		'field' => 'value',
		'field2' => 'value2',
	]
);
```


### Consume message

Process message from given queue:

``` php
$manager->fetch(
	queue: 'name-of-queue',
	handler: function (array $data) {
		$data['field'];
		$data['field2'];
	}
);
```

Process messages from ALL queues (logs missing handlers via `$exceptionHandler`):

``` php
$message = $manager->multiFetch(
	handlers: [
		'name-of-queue' => function (array $data) {
			$data['field'];
			$data['field2'];
		}
		'name-of-queue-B' => function (array $data) {
			$data['field'];
			$data['field2'];
		}
	],
	limit: 10 // number of messages processed in multiFetch() call
);
```

Process messages from SPECIFIC queues:

``` php
$message = $manager->multiFetch(
	handlers: [
		'name-of-queue' => function (array $data) {
			$data['field'];
			$data['field2'];
		}
		'name-of-queue-B' => function (array $data) {
			$data['field'];
			$data['field2'];
		}
	],
	limit: 10, // number of messages processed in multiFetch() call
	fetchFrom: $mananger::FROM_LISTED_ONLY
);
```


------------------------------

License: [New BSD License](license.md)
<br>Author: Jan Pecha, https://www.janpecha.cz/
