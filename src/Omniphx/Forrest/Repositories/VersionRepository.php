<?php

namespace Omniphx\Forrest\Repositories;

use Omniphx\Forrest\Interfaces\RepositoryInterface;
use Omniphx\Forrest\Interfaces\StorageInterface;
use Omniphx\Forrest\Exceptions\MissingVersionException;

class VersionRepository implements RepositoryInterface {

    protected $storage;

    public function __construct(StorageInterface $storage) {
        $this->storage  = $storage;
    }

    public function put($versions)
    {
        $this->storage->put('version', $versions);
    }

    /**
     * Get version
     *
     * @return mixed
     */
    public function get()
    {
        $this->verify();

        return $this->storage->get('version');
    }

    public function has()
    {
        return $this->storage->has('version');
    }

    private function verify() {
        if ($this->storage->has('version')) return;

        throw new MissingVersionException('No version available');
    }
}