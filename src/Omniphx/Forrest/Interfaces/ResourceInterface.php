<?php namespace Omniphx\Forrest\Interfaces;

interface ResourceInterface {

    /**
     * Method returns the response for the requested resource
     * @param  string $pURI 
     * @param  array  $pOptions
     * @return mixed
     */
	public function request($pURI, array $pOptions);

    /**
     * Set the headers for the request
     * @param array $options
     * @return array $headers
     */
	public function setHeaders(array $options);

    /**
     * Set the body for the request
     * @param array $options
     * @return array $body
     */
	public function setBody(array $options);

}