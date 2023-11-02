<?php

	declare(strict_types=1);

	namespace Inteve\MessageQueue;


	class Result
	{
		const STATUS_DONE = 1;
		const STATUS_FAILED = 2;
		const STATUS_DEFERRED = 3;

		/** @var int|NULL */
		private $status;

		/** @var positive-int|NULL */
		private $deferInterval;


		public function isUndefined(): bool
		{
			return $this->status === NULL;
		}


		public function done(): void
		{
			$this->setStatus(self::STATUS_DONE);
		}


		public function isDone(): bool
		{
			return $this->status === self::STATUS_DONE;
		}


		public function fail(): void
		{
			$this->setStatus(self::STATUS_FAILED);
		}


		public function isFailed(): bool
		{
			return $this->status === self::STATUS_FAILED;
		}


		/**
		 * @param  positive-int $minutes
		 */
		public function defer(int $minutes): void
		{
			$this->setStatus(self::STATUS_DEFERRED);
			$this->deferInterval = $minutes;
		}


		public function isDeferred(): bool
		{
			return $this->status === self::STATUS_DEFERRED;
		}


		/**
		 * @return positive-int
		 */
		public function getDeferInterval(): int
		{
			if (!$this->isDeferred()) {
				throw new InvalidCallExpception('Status must be deferred.');
			}

			if ($this->deferInterval === NULL) {
				throw new InvalidCallExpception('Missing deferInterval.');
			}

			return $this->deferInterval;
		}


		/**
		 * @param  int $status
		 */
		private function setStatus(int $status): void
		{
			if ($this->status !== NULL) {
				throw new InvalidCallExpception('Status is already setted.');
			}

			$this->status = $status;
		}
	}
