<?php namespace Omniphx\Forrest\Interfaces;

interface ResourceInterface {

	public function request($pURI, array $pOptions);

	public function setHeaders(array $options);

	public function setBody(array $options);

}