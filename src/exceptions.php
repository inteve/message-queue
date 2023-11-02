<?php

	declare(strict_types=1);

	namespace Inteve\MessageQueue;


	class Exception extends \Exception
	{
	}


	class InvalidArgumentExpception extends Exception
	{
	}


	class InvalidCallExpception extends Exception
	{
	}


	class MissingHandlerException extends Exception
	{
	}
