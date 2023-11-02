<?php

	declare(strict_types=1);

	namespace Inteve\MessageQueue;


	class Message
	{
		const STATUS_NEW = 0;
		const STATUS_DONE = 1;
		const STATUS_FAILED = 2;
		const STATUS_PROCESSING = 3;
		const STATUS_DEFERRED = 4;

		/** @var non-empty-string */
		private $id;

		/** @var non-empty-string */
		private $queue;

		/** @var array<mixed> */
		private $data;

		/** @var \DateTimeImmutable */
		private $date;

		/** @var positive-int */
		private $order;

		/** @var \DateTimeImmutable */
		private $created;

		/** @var self::STATUS_* */
		private $status;

		/** @var \DateTimeImmutable|NULL */
		private $processed;

		/** @var non-negative-int */
		private $fails;


		/**
		 * @param non-empty-string $id
		 * @param non-empty-string $queue
		 * @param array<mixed> $data
		 * @param positive-int $order
		 * @param self::STATUS_* $status
		 * @param non-negative-int $fails
		 */
		public function __construct(
			string $id,
			string $queue,
			array $data,
			\DateTimeImmutable $date,
			int $order,
			\DateTimeImmutable $created,
			int $status,
			\DateTimeImmutable $processed = NULL,
			int $fails
		)
		{
			$this->id = $id;
			$this->queue = $queue;
			$this->data = $data;
			$this->date = $date;
			$this->order = $order;
			$this->created = $created;
			$this->status = $status;
			$this->processed = $processed;
			$this->fails = $fails;
		}


		/**
		 * @return non-empty-string
		 */
		public function getId(): string
		{
			return $this->id;
		}


		/**
		 * @return non-empty-string
		 */
		public function getQueue(): string
		{
			return $this->queue;
		}


		/**
		 * @return array<mixed>
		 */
		public function getData(): array
		{
			return $this->data;
		}


		public function getDate(): \DateTimeImmutable
		{
			return $this->date;
		}


		/**
		 * @return positive-int
		 */
		public function getOrder(): int
		{
			return $this->order;
		}


		public function getCreated(): \DateTimeImmutable
		{
			return $this->created;
		}


		/**
		 * @return self::STATUS_*
		 */
		public function getStatus(): int
		{
			return $this->status;
		}


		public function getProcessed(): ?\DateTimeImmutable
		{
			return $this->processed;
		}


		/**
		 * @return non-negative-int
		 */
		public function getFails(): int
		{
			return $this->fails;
		}


		public function markAsProcessing(): void
		{
			$this->status = self::STATUS_PROCESSING;
		}


		public function markAsDone(\DateTimeImmutable $processed): void
		{
			$this->status = self::STATUS_DONE;
			$this->processed = $processed;
		}


		public function markAsFailed(\DateTimeImmutable $processed): void
		{
			$this->status = self::STATUS_FAILED;
			$this->fails += 1;

			$date = clone $this->date;
			$minutesToMove = min(60, $this->fails * 5);
			$this->date = $this->calculateNewDate($date, $processed, $minutesToMove, 5);
			$this->processed = $processed;
		}


		/**
		 * @param  positive-int $minutes
		 */
		public function markAsDeferred(\DateTimeImmutable $processed, int $minutes): void
		{
			$this->status = self::STATUS_DEFERRED;

			$date = clone $processed;
			$this->date = $this->calculateNewDate($date, $processed, $minutes, 5);
			$this->processed = $processed;
		}


		/**
		 * @param  positive-int $minutesToMove
		 * @param  positive-int $stepInMinutes
		 */
		private function calculateNewDate(
			\DateTimeImmutable $date,
			\DateTimeImmutable $processed,
			int $minutesToMove,
			int $stepInMinutes
		): \DateTimeImmutable
		{
			// musime docilit toho, aby novy cas byl za $processed
			$date = $date->add(new \DateInterval('PT' . $minutesToMove . 'M'));

			while ($date <= $processed) {
				$date = $date->add(new \DateInterval('PT' . $stepInMinutes . 'M'));
			}

			return $date;
		}
	}
