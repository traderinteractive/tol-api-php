<?php

namespace DominionEnterprises\Api;
use DominionEnterprises\Util;

/**
 * Class for iterating index responses. Collections are readonly
 */
final class Collection implements \Iterator, \Countable
{
    /**
     * API Client
     *
     * @var Client
     */
    private $_client;

    /**
     * limit to give to API
     *
     * @var int
     */
    private $_limit;

    /**
     * offset to give to API
     *
     * @var int
     */
    private $_offset;

    /**
     * resource name for collection
     *
     * @var string
     */
    private $_resource;

    /**
     * array of filters to pass to API
     *
     * @var array
     */
    private $_filters;

    /**
     * Total number of elements in the collection
     *
     * @var int
     */
    private $_total;

    /**
     * pointer in the paginated results
     *
     * @var int
     */
    private $_position;

    /**
     * A paginated set of elements from the API
     *
     * @var array
     */
    private $_result;

    /**
     * Create a new collection
     *
     * @param ClientInterface $client client connection to the API
     * @param string $resource name of API resource to request
     * @param array $filters key value pair array of search filters
     */
    public function __construct(ClientInterface $client, $resource, array $filters = [])
    {
        Util::throwIfNotType(['string' => [$resource]], true);

        $this->_client = $client;
        $this->_resource = $resource;
        $this->_filters = $filters;
        $this->rewind();
    }

    /**
     * @see Countable::count()
     *
     * @return int
     */
    public function count()
    {
        if ($this->_position === -1) {
            $this->next();
        }

        return $this->_total;
    }

    /**
     * @see Iterator::rewind()
     *
     * @return void
     */
    public function rewind()
    {
        $this->_result = null;
        $this->_offset = 0;
        $this->_total = 0;
        $this->_limit = 0;
        $this->_position = -1;
    }

    /**
     * @see Iterator::key()
     *
     * @return int
     */
    public function key()
    {
        if ($this->_position === -1) {
            $this->next();
        }

        Util::ensure(false, empty($this->_result), '\OutOfBoundsException', ['Collection contains no elements']);

        return $this->_offset + $this->_position;
    }

    /**
     * @see Iterator::valid()
     *
     * @return bool
     */
    public function valid()
    {
        if ($this->_position === -1) {
            $this->next();
        }

        return $this->_offset + $this->_position < $this->_total;
    }

    /**
     * @see Iterator::next()
     *
     * @return void
     */
    public function next()
    {
        ++$this->_position;

        if ($this->_position < $this->_limit) {
            return;
        }

        $this->_offset += $this->_limit;
        $this->_filters['offset'] = $this->_offset;
        $indexResponse = $this->_client->index($this->_resource, $this->_filters);

        $httpCode = $indexResponse->getHttpCode();
        Util::ensure(200, $httpCode, "Did not receive 200 from API. Instead received {$httpCode}");

        $response = $indexResponse->getResponse();
        $this->_limit = $response['pagination']['limit'];
        $this->_total = $response['pagination']['total'];
        $this->_result = $response['result'];
        $this->_position = 0;
    }

    /**
     * @see Iterator::current()
     *
     * @return array
     */
    public function current()
    {
        if ($this->_position === -1) {
            $this->next();
        }

        Util::ensure(
            true,
            array_key_exists($this->_position, $this->_result),
            '\OutOfBoundsException',
            ['Collection contains no element at current position']
        );

        return $this->_result[$this->_position];
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
