<?php namespace Omniphx\Forrest\Interfaces;

interface RequestInterface {

	public function getRequest($url, $header=null);

	public function postRequest($url, $postfields);

}