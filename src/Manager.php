<?php

	declare(strict_types=1);

	namespace Inteve\MessageQueue;

	use Nette\Utils\Random;
	use Phig\DateTimeFactory;


	class Manager
	{
		const FAIL_LIMIT = 10;
		const FROM_ALL_QUEUES = 0;
		const FROM_LISTED_ONLY = 1;

		/** @var Adapter */
		private $adapter;

		/** @var DateTimeFactory */
		private $dateTimeFactory;


		public function __construct(
			Adapter $adapter,
			DateTimeFactory $dateTimeFactory
		)
		{
			$this->adapter = $adapter;
			$this->dateTimeFactory = $dateTimeFactory;
		}


		/**
		 * @param  string $queue
		 * @param  array<mixed> $data
		 * @param  int $priority
		 * @return Message
		 */
		public function create($queue, array $data, \DateTimeImmutable $date = NULL, $priority = 0)
		{
			$currentDate = $this->dateTimeFactory->create();
			$message = new Message(
				Random::generate(16),
				$queue,
				$data,
				$date !== NULL ? $date : $currentDate,
				$priority,
				$currentDate,
				Message::STATUS_NEW,
				NULL,
				0
			);

			$this->adapter->create($message);
			return $message;
		}


		/**
		 * @param  string $queue
		 * @param  callable $handler
		 * @return void
		 */
		public function fetch($queue, callable $handler)
		{
			$message = $this->adapter->fetchNext(
				$this->dateTimeFactory->create(),
				self::FAIL_LIMIT,
				[$queue]
			);

			if ($message !== NULL) {
				$this->processMessage($message, $handler);
			}
		}


		/**
		 * @param  array<string, callable> $handlers
		 * @param  int $limit
		 * @param  int $fetchFrom
		 * @return void
		 */
		public function multiFetch(array $handlers, $limit, $fetchFrom = self::FROM_ALL_QUEUES)
		{
			for ($i = 0; $i < $limit; $i++) {
				$message = $this->adapter->fetchNext(
					$this->dateTimeFactory->create(),
					self::FAIL_LIMIT,
					$fetchFrom === self::FROM_LISTED_ONLY ? array_keys($handlers) : NULL
				);

				if ($message === NULL) {
					return;
				}

				$queue = $message->getQueue();

				if (isset($handlers[$queue])) {
					$this->processMessage($message, $handlers[$queue]);

				} else {
					$this->markAsFailed($message);
					$e = new MissingHandlerException("Missing handler for queue '$queue'");
					$this->adapter->logException($e);
				}
			}
		}


		/**
		 * @return void
		 */
		private function processMessage(Message $message, callable $handler)
		{
			$this->markAsProcessing($message);

			try {
				$result = new Result;
				$handler($message->getData(), $result);

				if ($result->isUndefined()) {
					$result->done();
				}

				if ($result->isDone()) {
					$this->markAsDone($message);

				} elseif ($result->isFailed()) {
					$this->markAsFailed($message);

				} elseif ($result->isDeferred()) {
					$this->markAsDeferred($message, (int) $result->getDeferInterval());
				}

			} catch (\Exception $e) {
				$this->markAsFailed($message);
				$this->adapter->logException($e);

			} catch (\Throwable $e) {
				$this->markAsFailed($message);
				$this->adapter->logException($e);
			}
		}


		/**
		 * @return void
		 */
		private function markAsProcessing(Message $message)
		{
			$message->markAsProcessing();
			$this->adapter->update($message);
		}


		/**
		 * @return void
		 */
		private function markAsDone(Message $message)
		{
			$message->markAsDone($this->dateTimeFactory->create());
			$this->adapter->delete($message);
		}


		/**
		 * @param  int $minutes
		 * @return void
		 */
		private function markAsDeferred(Message $message, $minutes)
		{
			$message->markAsDeferred($this->dateTimeFactory->create(), $minutes);
			$this->adapter->update($message);
		}


		/**
		 * @return void
		 */
		private function markAsFailed(Message $message)
		{
			$message->markAsFailed($this->dateTimeFactory->create());
			$this->adapter->update($message);
		}
	}
