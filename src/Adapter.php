<?php

	declare(strict_types=1);

	namespace Inteve\MessageQueue;


	interface Adapter
	{
		/**
		 * @return \DateTimeImmutable
		 */
		function createDateTime();


		/**
		 * @return void
		 */
		function create(Message $message);


		/**
		 * @return void
		 */
		function update(Message $message);


		/**
		 * @return void
		 */
		function delete(Message $message);


		/**
		 * @param  \DateTimeImmutable $currentDate
		 * @param  int $failLimit
		 * @param  string[]|NULL $queues
		 * @return Message|NULL
		 */
		function fetchNext(\DateTimeImmutable $currentDate, $failLimit, array $queues = NULL);


		/**
		 * @param  \Exception|\Throwable $e
		 * @return void
		 */
		function logException($e);
	}
