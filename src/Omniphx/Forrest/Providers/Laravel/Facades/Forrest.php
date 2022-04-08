<?php

namespace Omniphx\Forrest\Providers\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static authenticate()
 * @method static refresh()
 * @method static revoke()
 * @method static versions()
 * @method static resources()
 * @method static identity()
 * @method static getBaseUrl()
 * @method static getInstanceURL()
 * @method static get(string $resource)
 * @method static head(string $resource)
 * @method static delete(string $resource)
 * @method static query(string $query)
 * @method static post(string $resource, array $options = [])
 * @method static patch(string $resource, array $options = [])
 * @method static put(string $resource, array $options = [])
 * @method static custom(string $resource, array $options = [])
 * @method static sobjects(string $resource, array $options = [])
 * @method static chatter(string $resource, array $options = [])
 * @method static tabs(string $resource, array $options = [])
 * @method static appMenu(string $resource, array $options = [])
 * @method static quickActions(string $resource, array $options = [])
 * @method static commerce(string $resource, array $options = [])
 * @method static wave(string $resource, array $options = [])
 * @method static exchange-connect(string $resource, array $options = [])
 * @method static analytics(string $resource, array $options = [])
 * @method static composite(string $resource, array $options = [])
 * @method static theme(string $resource, array $options = [])
 * @method static nouns(string $resource, array $options = [])
 * @method static recent(string $resource, array $options = [])
 * @method static licensing(string $resource, array $options = [])
 * @method static async-queries(string $resource, array $options = [])
 * @method static emailConnect(string $resource, array $options = [])
 * @method static compactLayouts(string $resource, array $options = [])
 * @method static flexiPage(string $resource, array $options = [])
 * @method static knowledgeManagement(string $resource, array $options = [])
 * @method static actions(string $resource, array $options = [])
 * @method static support(string $resource, array $options = [])
*/
class Forrest extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'forrest';
    }
}
