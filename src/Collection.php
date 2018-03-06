<?php

namespace TraderInteractive\Api;

use TraderInteractive\Util;

/**
 * Class for iterating index responses. Collections are readonly
 */
final class Collection implements \Iterator, \Countable
{
    /**
     * API Client
     *
     * @var ClientInterface
     */
    private $client;

    /**
     * limit to give to API
     *
     * @var int
     */
    private $limit;

    /**
     * offset to give to API
     *
     * @var int
     */
    private $offset;

    /**
     * resource name for collection
     *
     * @var string
     */
    private $resource;

    /**
     * array of filters to pass to API
     *
     * @var array
     */
    private $filters;

    /**
     * Total number of elements in the collection
     *
     * @var int
     */
    private $total;

    /**
     * pointer in the paginated results
     *
     * @var int
     */
    private $position;

    /**
     * A paginated set of elements from the API
     *
     * @var array|null
     */
    private $result;

    /**
     * Create a new collection
     *
     * @param Client $client client connection to the API
     * @param string $resource name of API resource to request
     * @param array $filters key value pair array of search filters
     */
    public function __construct(ClientInterface $client, $resource, array $filters = [])
    {
        Util::throwIfNotType(['string' => [$resource]], true);

        $this->client = $client;
        $this->resource = $resource;
        $this->filters = $filters;
        $this->rewind();
    }

    /**
     * @see Countable::count()
     *
     * @return int
     */
    public function count()
    {
        if ($this->position === -1) {
            $this->next();
        }

        return $this->total;
    }

    /**
     * @see Iterator::rewind()
     *
     * @return void
     */
    public function rewind()
    {
        $this->result = null;
        $this->offset = 0;
        $this->total = 0;
        $this->limit = 0;
        $this->position = -1;
    }

    /**
     * @see Iterator::key()
     *
     * @return int
     */
    public function key()
    {
        if ($this->position === -1) {
            $this->next();
        }

        Util::ensure(false, empty($this->result), '\OutOfBoundsException', ['Collection contains no elements']);

        return $this->offset + $this->position;
    }

    /**
     * @see Iterator::valid()
     *
     * @return bool
     */
    public function valid()
    {
        if ($this->position === -1) {
            $this->next();
        }

        return $this->offset + $this->position < $this->total;
    }

    /**
     * @see Iterator::next()
     *
     * @return void
     */
    public function next()
    {
        ++$this->position;

        if ($this->position < $this->limit) {
            return;
        }

        $this->offset += $this->limit;
        $this->filters['offset'] = $this->offset;
        $indexResponse = $this->client->index($this->resource, $this->filters);

        $httpCode = $indexResponse->getHttpCode();
        Util::ensure(200, $httpCode, "Did not receive 200 from API. Instead received {$httpCode}");

        $response = $indexResponse->getResponse();
        $this->limit = $response['pagination']['limit'];
        $this->total = $response['pagination']['total'];
        $this->result = $response['result'];
        $this->position = 0;
    }

    /**
     * @see Iterator::current()
     *
     * @return array
     */
    public function current()
    {
        if ($this->position === -1) {
            $this->next();
        }

        Util::ensure(
            true,
            array_key_exists($this->position, $this->result),
            '\OutOfBoundsException',
            ['Collection contains no element at current position']
        );

        return $this->result[$this->position];
    }

    /**
     * Returns the values from a single field this collection, identified by the given $key.
     *
     * @param string $key The name of the field for which the values will be returned.
     *
     * @return iterable
     */
    public function column($key)
    {
        foreach ($this as $item) {
            yield Util\Arrays::get($item, $key);
        }
    }

    /**
     * Return an iterable generator containing only the fields specified in the $keys array.
     *
     * @param array $keys The list of field names to be returned.
     *
     * @return \Generator
     */
    public function select(array $keys)
    {
        foreach ($this as $item) {
            $result = array_fill_keys($keys, null);
            Util\Arrays::copyIfKeysExist($item, $result, $keys);
            yield  $result;
        }
    }
}
