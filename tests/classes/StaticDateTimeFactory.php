<?php

	declare(strict_types=1);

	namespace Inteve\MessageQueue\Tests;


	class StaticDateTimeFactory implements \Phig\DateTimeFactory
	{
		/** @var \DateTimeImmutable */
		private $datetime;


		public function __construct(
			\DateTimeImmutable $datetime
		)
		{
			$this->datetime = $datetime;
		}


		public function create()
		{
			return $this->datetime;
		}


		public static function fromString(string $dt): self
		{
			return new self(new \DateTimeImmutable($dt, new \DateTimeZone('UTC')));
		}
	}
