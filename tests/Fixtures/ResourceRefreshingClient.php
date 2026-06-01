<?php

namespace Tests\Fixtures;

class ResourceRefreshingClient extends InspectableClient
{
    /**
     * Mirrors the side effect of an OAuth refresh: re-fetching the resource
     * listing issues a nested request that overwrites $this->options,
     * which Client::request() must restore before retrying.
     */
    public function refresh()
    {
        parent::refresh();

        $this->resources(['format' => 'json']);
    }
}
