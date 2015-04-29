<?php namespace Ihsw\Toxiproxy\Exception;

class ProxyExistsException extends \RuntimeException
{
	private $name;

	public function setName($name)
	{
		$this->name = $name;
	}
}