<?php

	declare(strict_types=1);

	namespace Inteve\MessageQueue;


	interface Adapter
	{
		function create(Message $message): void;


		function update(Message $message): void;


		function delete(Message $message): void;


		/**
		 * @param  positive-int $failLimit
		 * @param  string[]|NULL $queues
		 */
		function fetchNext(
			\DateTimeImmutable $currentDate,
			int $failLimit,
			?array $queues = NULL
		): ?Message;


		function logException(\Throwable $e): void;
	}
