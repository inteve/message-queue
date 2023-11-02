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

		/** @var int|NULL */
		private $deferInterval;


		/**
		 * @return bool
		 */
		public function isUndefined()
		{
			return $this->status === NULL;
		}


		/**
		 * @return void
		 */
		public function done()
		{
			$this->setStatus(self::STATUS_DONE);
		}


		/**
		 * @return bool
		 */
		public function isDone()
		{
			return $this->status === self::STATUS_DONE;
		}


		/**
		 * @return void
		 */
		public function fail()
		{
			$this->setStatus(self::STATUS_FAILED);
		}


		/**
		 * @return bool
		 */
		public function isFailed()
		{
			return $this->status === self::STATUS_FAILED;
		}


		/**
		 * @param  int $minutes
		 * @return void
		 */
		public function defer($minutes)
		{
			$this->setStatus(self::STATUS_DEFERRED);
			$this->deferInterval = $minutes;
		}


		/**
		 * @return bool
		 */
		public function isDeferred()
		{
			return $this->status === self::STATUS_DEFERRED;
		}


		/**
		 * @return int|NULL
		 */
		public function getDeferInterval()
		{
			if (!$this->isDeferred()) {
				throw new InvalidCallExpception('Status must be deferred.');
			}

			return $this->deferInterval;
		}


		/**
		 * @param  int $status
		 * @return void
		 */
		private function setStatus($status)
		{
			if ($this->status !== NULL) {
				throw new InvalidCallExpception('Status is already setted.');
			}

			$this->status = $status;
		}
	}
