<?php

namespace Omniphx\Forrest\Repositories;

use Omniphx\Forrest\Interfaces\RepositoryInterface;

class InstanceURLRepository implements RepositoryInterface {

    protected $tokenRepo;
    protected $settings;

    public function __construct(RepositoryInterface $tokenRepo, $settings) {
        $this->tokenRepo = $tokenRepo;
        $this->settings  = $settings;
    }

    public function put($instanceURL) {
        return;
    }

    /**
     * Get version
     *
     * @return mixed
     */
    public function get()
    {
        if (isset($this->settings['instanceURL'])) {
            return $this->settings['instanceURL'];
        } else {
            return $this->tokenRepo->get()['instance_url'];
        }
    } 
}