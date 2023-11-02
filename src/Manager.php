<?php

	declare(strict_types=1);

	namespace Inteve\MessageQueue;

	use Nette\Utils\Random;


	class Manager
	{
		const FAIL_LIMIT = 10;
		const FROM_ALL_QUEUES = 0;
		const FROM_LISTED_ONLY = 1;

		/** @var IAdapter */
		private $adapter;


		public function __construct(IAdapter $adapter)
		{
			$this->adapter = $adapter;
		}


		/**
		 * @param  string $queue
		 * @param  array<mixed> $data
		 * @param  int $priority
		 * @return Message
		 */
		public function create($queue, array $data, \DateTimeImmutable $date = NULL, $priority = 0)
		{
			$currentDate = $this->adapter->createDateTime();
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
		 * @param  callable $importer
		 * @return void
		 */
		public function import(callable $importer)
		{
			$this->adapter->transactional(function () use ($importer) {
				$importer();
			});
		}


		/**
		 * @param  string $queue
		 * @param  callable $handler
		 * @return void
		 */
		public function fetch($queue, callable $handler)
		{
			$message = $this->adapter->fetchFromQueue(
				$queue,
				$this->adapter->createDateTime(),
				self::FAIL_LIMIT
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
					$this->adapter->createDateTime(),
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
				$this->adapter->transactional(function () use ($message, $handler) {
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
				});

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
			$message->markAsDone($this->adapter->createDateTime());
			$this->adapter->delete($message);
		}


		/**
		 * @param  int $minutes
		 * @return void
		 */
		private function markAsDeferred(Message $message, $minutes)
		{
			$message->markAsDeferred($this->adapter->createDateTime(), $minutes);
			$this->adapter->update($message);
		}


		/**
		 * @return void
		 */
		private function markAsFailed(Message $message)
		{
			$message->markAsFailed($this->adapter->createDateTime());
			$this->adapter->update($message);
		}
	}
