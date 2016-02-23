<?php namespace Academe\SagePay\Psr7\Response;

/**
 * A collection of errors, normally validation errors.
 * Once we have  validation errors collected in here, we can sort them into
 * property names (fields), error types etc.
 */

use Exception;
use UnexpectedValueException;
use Psr\Http\Message\ResponseInterface;

use ArrayIterator;

use Academe\SagePay\Psr7\Helper;
use Academe\SagePay\Psr7\Model\Error;

class ErrorCollection extends AbstractResponse implements \IteratorAggregate
{
    /**
     * @var array
     */
    protected $items = array();

    /**
     * @param array $items Initial array of Error instances
     */
    public function __construct($data, $httpCode = null)
    {
        // If $data is a PSR-7 message, then extract what we need.
        if ($data instanceof ResponseInterface) {
            $httpCode = $data->getStatusCode();

            if ($data->hasHeader('Content-Type') && $data->getHeaderLine('Content-Type') == 'application/json') {
                $data = json_decode($data->getBody());
            } else {
                $data = [];
            }
        } else {
            $httpCode = $this->deriveHttpCode($httpCode, $data);
        }

        // A list of errors will be provided in a wrapping "errors" element.
        $errors = Helper::structureGet($data, 'errors', null);

        // If there was no "errors" wrapper, then assume what we have is a single error,
        // provided there is a "code" element at a minimum.
        if (!isset($errors) && !empty(Helper::structureGet($data, 'code', null))) {
            $this->add(Error::fromData($data, $httpCode));
        } elseif (is_array($errors)) {
            foreach($errors as $error) {
                // The $error may be an Error object or an array.
                $this->add(Error::fromData($error, $httpCode));
            }
        }

        $this->httpCode = $httpCode;
    }

    /**
     * Add a new error to the collection.
     *
     * @param Error $item An Error instance to add
     */
    public function add(Error $item)
    {
        $this->items[] = $item;
    }

    /**
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Return errors for a specific property.
     * Use null to return errors without a property reference.
     * Returns ErrorCollection
     *
     * @param null|string $property_name The property name or null to get errors without a property name
     *
     * @return static A collection of zero or more Error objects
     */
    public function byProperty($property_name = null)
    {
        $result = new static();

        foreach($this as $error) {
            if ($property_name === $error->getProperty()) {
                $result->add($error);
            }
        }

        return $result;
    }

    /**
     * @return array Array of all properties the errors in this collection report on
     */
    public function getProperties()
    {
        $result = array();

        foreach($this as $error) {
            if ( ! in_array($error->getProperty(), $result)) {
                $result[] = $error->getProperty();
            }
        }

        return $result;
    }

    /**
     * @return int Count of errors in the collection
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * @return bool True if there are any errors in the collection, otherwise False
     */
    public function hasErrors()
    {
        return $this->count() > 0;
    }

    /**
     * @return Error The first error in the collection.
     */
    public function first()
    {
        return reset($this->items);
    }

    /**
     * @return array All errors in the collection.
     */
    public function all()
    {
        return $this->items;
    }
}