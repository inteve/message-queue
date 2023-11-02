<?php

	declare(strict_types=1);

	namespace Inteve\MessageQueue;


	class MemoryAdapter implements Adapter
	{
		/** @var array<string, Message> */
		private $messages = [];

		/** @var callable */
		private $exceptionHandler;

		/** @var \DateTimeZone */
		private $timezone;


		public function __construct(
			callable $exceptionHandler
		)
		{
			$this->exceptionHandler = $exceptionHandler;
			$this->timezone = new \DateTimeZone('UTC');
		}


		public function createDateTime()
		{
			return new \DateTimeImmutable('now', $this->timezone);
		}

		public function create(Message $message)
		{
			if (isset($this->messages[$message->getId()])) {
				throw new InvalidArgumentExpception("Message with ID '{$message->getId()}' already exists.");
			}

			$this->messages[$message->getId()] = $message;
		}

		public function update(Message $message)
		{
			if (!isset($this->messages[$message->getId()])) {
				throw new InvalidArgumentExpception("Missing message with ID '{$message->getId()}'.");
			}

			$this->messages[$message->getId()] = $message;
		}

		public function delete(Message $message)
		{
			unset($this->messages[$message->getId()]);
		}


		public function fetchFromQueue($queue, \DateTimeImmutable $currentDate, $failLimit)
		{
			return $this->fetchNext($currentDate, $failLimit, [$queue]);
		}


		public function fetchNext(\DateTimeImmutable $currentDate, $failLimit, ?array $queues = NULL)
		{
			$finalMessage = NULL;

			foreach ($this->messages as $message) {
				if ($message->getFails() >= $failLimit) {
					continue;
				}

				$status = $message->getStatus();

				if ($queues !== NULL && !in_array($message->getQueue(), $queues, TRUE)) {
					continue;
				}

				if ($status !== Message::STATUS_NEW && $status !== Message::STATUS_FAILED) {
					continue;
				}

				if ($finalMessage !== NULL) {
					$finalMessagePriority = $finalMessage->getPriority();
					$messagePriority = $message->getPriority();

					if ($messagePriority > $finalMessagePriority) {
						continue;

					} elseif (($messagePriority === $finalMessagePriority) && ($message->getDate() >= $finalMessage->getDate())) {
						continue;
					}
				}

				$finalMessage = $message;
			}

			return $finalMessage;
		}


		public function transactional(callable $handler)
		{
			$handler();
		}


		public function logException($e)
		{
			($this->exceptionHandler)($e);
		}
	}
