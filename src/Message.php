<?php

	declare(strict_types=1);

	namespace Inteve\MessageQueue;


	class Message
	{
		const STATUS_NEW = 0;
		const STATUS_DONE = 1;
		const STATUS_FAILED = 2;
		const STATUS_PROCESSING = 3;

		/** @var string */
		private $id;

		/** @var string */
		private $queue;

		/** @var array<mixed> */
		private $data;

		/** @var \DateTimeImmutable */
		private $date;

		/** @var int */
		private $priority;

		/** @var \DateTimeImmutable */
		private $created;

		/** @var int */
		private $status;

		/** @var \DateTimeImmutable|NULL */
		private $processed;

		/** @var int */
		private $fails;


		/**
		 * @param string $id
		 * @param string $queue
		 * @param array<mixed> $data
		 * @param int $priority
		 * @param int $status
		 * @param int $fails
		 */
		public function __construct(
			$id,
			$queue,
			array $data,
			\DateTimeImmutable $date,
			$priority,
			\DateTimeImmutable $created,
			$status,
			\DateTimeImmutable $processed = NULL,
			$fails
		)
		{
			if ($priority < 0) {
				throw new InvalidArgumentExpception('Priority must be 0 or higher.');
			}

			$this->id = $id;
			$this->queue = $queue;
			$this->data = $data;
			$this->date = $date;
			$this->priority = $priority;
			$this->created = $created;
			$this->status = $status;
			$this->processed = $processed;
			$this->fails = $fails;
		}


		/**
		 * @return string
		 */
		public function getId()
		{
			return $this->id;
		}


		/**
		 * @return string
		 */
		public function getQueue()
		{
			return $this->queue;
		}


		/**
		 * @return array<mixed>
		 */
		public function getData()
		{
			return $this->data;
		}


		/**
		 * @return \DateTimeImmutable
		 */
		public function getDate()
		{
			return $this->date;
		}


		/**
		 * @return int
		 */
		public function getPriority()
		{
			return $this->priority;
		}


		/**
		 * @return \DateTimeImmutable
		 */
		public function getCreated()
		{
			return $this->created;
		}


		/**
		 * @return int
		 */
		public function getStatus()
		{
			return $this->status;
		}


		/**
		 * @return \DateTimeImmutable|NULL
		 */
		public function getProcessed()
		{
			return $this->processed;
		}


		/**
		 * @return int
		 */
		public function getFails()
		{
			return $this->fails;
		}


		/**
		 * @return void
		 */
		public function markAsProcessing()
		{
			$this->status = self::STATUS_PROCESSING;
		}


		/**
		 * @return void
		 */
		public function markAsDone(\DateTimeImmutable $processed)
		{
			$this->status = self::STATUS_DONE;
			$this->processed = $processed;
		}


		/**
		 * @return void
		 */
		public function markAsFailed(\DateTimeImmutable $processed)
		{
			$this->status = self::STATUS_FAILED;
			$this->fails += 1;

			$date = clone $this->date;
			$minutesToMove = min(60, $this->fails * 5);
			$this->date = $this->calculateNewDate($date, $processed, $minutesToMove, 5);
			$this->processed = $processed;
		}


		/**
		 * @param  int $minutes
		 * @return void
		 */
		public function markAsDeferred(\DateTimeImmutable $processed, $minutes)
		{
			$this->status = self::STATUS_NEW;

			$date = clone $processed;
			$this->date = $this->calculateNewDate($date, $processed, $minutes, 5);
			$this->processed = $processed;
		}


		/**
		 * @param  int $minutesToMove
		 * @param  int $stepInMinutes
		 * @return \DateTimeImmutable
		 */
		private function calculateNewDate(\DateTimeImmutable $date, \DateTimeImmutable $processed, $minutesToMove, $stepInMinutes)
		{
			// musime docilit toho, aby novy cas byl za $processed
			$date = $date->add(new \DateInterval('PT' . $minutesToMove . 'M'));

			while ($date <= $processed) {
				$date = $date->add(new \DateInterval('PT' . $stepInMinutes . 'M'));
			}

			return $date;
		}
	}
