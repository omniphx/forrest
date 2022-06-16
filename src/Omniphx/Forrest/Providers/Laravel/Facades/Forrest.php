<?php

namespace Omniphx\Forrest\Providers\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Illuminate\Http\RedirectResponse|void authenticate()
 * @method static void refresh()
 * @method static \Psr\Http\Message\ResponseInterface|void revoke()
 * @method static void setCredentials(array $credentials)
 * @method static string|array versions(array $options = [])
 * @method static string|array resources(array $options = [])
 * @method static string|array identity(array $options = [])
 * @method static string|array limits(array $options = [])
 * @method static string|array describe(string $object_name = null, array $options = [])
 * @method static string getBaseUrl()
 * @method static string getInstanceURL()
 * @method static string|array get(string $path, array $requestBody = [], array $options = [])
 * @method static string|array head(string $path, array $requestBody = [], array $options = [])
 * @method static string|array delete(string $path, array $requestBody = [], array $options = [])
 * @method static string|array query(string $path, array $requestBody = [], array $options = [])
 * @method static string|array next(string $nextUrl, array $options = [])
 * @method static string|array queryExplain(string $query, array $options = [])
 * @method static string|array queryAll(string $query, array $options = [])
 * @method static string|array search(string $query, array $options = [])
 * @method static string|array scopeOrder(array $options = [])
 * @method static string|array searchLayouts(array $objectList, $options = [])
 * @method static string|array suggestedArticles(string $query, $options = [])
 * @method static string|array suggestedQueries(string $query, $options = [])
 * @method static string|array post(string $path, array $requestBody = [], array $options = [])
 * @method static string|array patch(string $path, array $requestBody = [], array $options = [])
 * @method static string|array put(string $path, array $requestBody = [], array $options = [])
 * @method static string|array custom(string $customURI, array $options = [])
 * @method static string|array request(string $url, array $options = [])
 * @method static string|array chatter(string $resource, array $options = [])
 * @method static string|array tabs(string $resource, array $options = [])
 * @method static string|array appMenu(string $resource, array $options = [])
 * @method static string|array quickActions(string $resource, array $options = [])
 * @method static string|array commerce(string $resource, array $options = [])
 * @method static string|array wave(string $resource, array $options = [])
 * @method static string|array exchange-connect(string $resource, array $options = [])
 * @method static string|array analytics(string $resource, array $options = [])
 * @method static string|array composite(string $resource, array $options = [])
 * @method static string|array theme(string $resource, array $options = [])
 * @method static string|array nouns(string $resource, array $options = [])
 * @method static string|array recent(string $resource, array $options = [])
 * @method static string|array licensing(string $resource, array $options = [])
 * @method static string|array async-queries(string $resource, array $options = [])
 * @method static string|array emailConnect(string $resource, array $options = [])
 * @method static string|array compactLayouts(string $resource, array $options = [])
 * @method static string|array flexiPage(string $resource, array $options = [])
 * @method static string|array knowledgeManagement(string $resource, array $options = [])
 * @method static string|array sobjects(string $resource, array $options = [])
 * @method static string|array actions(string $resource, array $options = [])
 * @method static string|array support(string $resource, array $options = [])
 * @method static \GuzzleHttp\ClientInterface getClient()
 * @method static string getInstanceURL()
 * @method static string getBaseUrl()
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
