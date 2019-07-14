<?php

namespace Omniphx\Forrest\Repositories;

use Omniphx\Forrest\Interfaces\RepositoryInterface;

class InstanceURLRepository implements RepositoryInterface
{
    protected $tokenRepo;
    protected $settings;

    public function __construct(RepositoryInterface $tokenRepo, $settings)
    {
        $this->tokenRepo = $tokenRepo;
        $this->settings = $settings;
    }

    /**
     * Store the instance URL.
     *
     * @parameter $instanceURL   Override the instance URL returned from authentication
     */
    public function put($instanceURL)
    {
        $token = $this->tokenRepo->get();
        $token['instance_url'] = $instanceURL;
        $this->tokenRepo->put($token);
    }

    /**
     * Is there a Token Repo?
     *
     * @return bool
     */
    public function has()
    {
        return $this->tokenRepo->has();
    }

    /**
     * Get Instance URL.
     *
     * @return string
     */
    public function get()
    {
        if (isset($this->settings['instanceURL']) && !empty($this->settings['instanceURL'])) {
            return $this->settings['instanceURL'];
        } else {
            return $this->tokenRepo->get()['instance_url'];
        }
    }
}
