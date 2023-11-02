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
		 * @param  non-empty-string $queue
		 * @param  array<mixed> $data
		 * @param  positive-int $order
		 */
		public function create(
			string $queue,
			array $data,
			?\DateTimeImmutable $date = NULL,
			$order = 1
		): Message
		{
			$currentDate = $this->dateTimeFactory->create();
			$id = Random::generate(16);
			assert($id !== '');
			$message = new Message(
				$id,
				$queue,
				$data,
				$date !== NULL ? $date : $currentDate,
				$order,
				$currentDate,
				Message::STATUS_NEW,
				NULL,
				0
			);

			$this->adapter->create($message);
			return $message;
		}


		/**
		 * @param  non-empty-string $queue
		 */
		public function fetch(string $queue, callable $handler): void
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
		 * @param  array<non-empty-string, callable> $handlers
		 * @param  positive-int $limit
		 * @param  self::FROM_* $fetchFrom
		 */
		public function multiFetch(
			array $handlers,
			int $limit,
			int $fetchFrom = self::FROM_ALL_QUEUES
		): void
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


		private function processMessage(Message $message, callable $handler): void
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


		private function markAsProcessing(Message $message): void
		{
			$message->markAsProcessing();
			$this->adapter->update($message);
		}


		private function markAsDone(Message $message): void
		{
			$message->markAsDone($this->dateTimeFactory->create());
			$this->adapter->delete($message);
		}


		/**
		 * @param  positive-int $minutes
		 */
		private function markAsDeferred(Message $message, int $minutes): void
		{
			$message->markAsDeferred($this->dateTimeFactory->create(), $minutes);
			$this->adapter->update($message);
		}


		private function markAsFailed(Message $message): void
		{
			$message->markAsFailed($this->dateTimeFactory->create());
			$this->adapter->update($message);
		}
	}
