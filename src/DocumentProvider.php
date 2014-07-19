<?php namespace Pandemonium\Methuselah;

use Pandemonium\Methuselah\UrlRepositories\UrlRepositoryInterface;
use Pandemonium\Methuselah\UrlRepositories\JsonUrlRepository;
use GuzzleHttp\Client;
use GuzzleHttp\Query;

/**
 * Download documents and provide their content.
 *
 * @author Michaël Lecerf <michael@estsurinter.net>
 */
class DocumentProvider
{
    /**
     * A URL repository.
     *
     * @var \Pandemonium\Methuselah\UrlRepositories\UrlRepositoryInterface
     */
    protected $urlRepository;

    /**
     * Specify how query string values are URL encoded.
     *
     * @var string|false
     */
    protected $queryEncoding;

    /**
     * Constructor.
     *
     * @param array  $config
     * @return void
     */
    public function __construct(array $config = [])
    {
        $this->configureUrlRepository($config);

        // Allow to specify how query string values are URL encoded.
        // This is necessary for bogus sites that do not comply with RFCs.
        if (array_key_exists('query_encoding', $config)) {
            $this->queryEncoding = $config['query_encoding'];
        }
    }

    /**
     * Get a web page for a given key.
     *
     * @param  string        $key     A URL pattern identifier
     * @param  string|array  $values  Pattern values
     * @return string                 Source code of the page
     */
    public function get($key, $values = null)
    {
        $url = $this->urlRepository->find($key, $values);

        return $this->download($url);
    }

    /**
     * Get the URL repository of the document provider.
     *
     * @return \Pandemonium\Methuselah\UrlRepositories\UrlRepositoryInterface
     */
    public function getUrlRepository()
    {
        return $this->urlRepository;
    }

    /**
     * Create a new HTTP client.
     *
     * @return \GuzzleHttp\Client
     */
    public function newHttpClient()
    {
        return new Client;
    }

    /**
     * Download the content at the given URL.
     *
     * @param  string  $url
     * @return string
     */
    protected function download($url)
    {
        $client = $this->newHttpClient();

        $request = $client->createRequest('GET', $url);

        // Some sites, such as the one of the Chamber, do not respect RFC
        // 1738 nor RFC 3986. In order to work with them, query strings
        // must not be encoded. We then need to provide a way to disable
        // the automatic encoding that Guzzle provides by default.
        if (isset($this->queryEncoding)) {

            $request->getQuery()->setEncodingType($this->queryEncoding);
        }

        // Return the body of the response.
        return (string) $client->send($request)->getBody();
    }

    /**
     * Set the URL repository according to the given config.
     *
     * @param  array  $config
     * @return void
     */
    protected function configureUrlRepository($config)
    {
        $repo = array_get($config, 'url_repository');

        if ($repo instanceof UrlRepositoryInterface) {
            $this->urlRepository = $repo;
        } else {
            $this->setDefaultUrlRepository();
        }
    }

    /**
     * Set up the URL repository with the default class.
     *
     * @return void
     */
    protected function setDefaultUrlRepository()
    {
        $this->urlRepository = new JsonUrlRepository;
    }
}
