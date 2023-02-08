<?php

namespace Moudarir\CodeigniterApi\Http;

use CI_Controller;
use Exception;
use SimpleXMLElement;

class Format
{

    /**
     * @var CI_Controller
     */
    private CI_Controller $ci;

    /**
     * Data to parse
     *
     * @var mixed
     */
    private $data = [];

    /**
     * DO NOT CALL THIS DIRECTLY, USE factory()
     *
     * @param mixed|null $data
     * @param string|null $fromType
     * @throws Exception
     */
    public function __construct($data = null, ?string $fromType = null)
    {
        // Get the CodeIgniter reference
        $this->ci = &get_instance();

        // If the provided data is already formatted we should probably convert it to an array
        if ($fromType !== null) {
            $method = Helpers::stringToCamelcase('from_' . $fromType);
            if (method_exists($this, $method)) {
                $data = call_user_func([$this, $method], $data);
            } else {
                $message = sprintf($this->ci->lang->line('rest_response_format_not_supported'), strtoupper($fromType));
                throw new Exception($message, Config::HTTP_BAD_REQUEST);
            }
        }

        // Set the member variable to the data passed
        $this->setData($data);
    }

    /**
     * Create an instance of the format class
     * e.g: echo $this->format->factory(['foo' => 'bar'])->to_csv();
     *
     * @param mixed $data           Data to convert/parse
     * @param string|null $fromType Type to convert from e.g. json, csv, html
     * @return self
     * @throws Exception
     */
    public static function factory($data, ?string $fromType = null): self
    {
        return new static($data, $fromType);
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Format data as an array
     *
     * @param mixed|null $data Optional data to pass, so as to override the data passed
     *                         to the constructor
     * @return array Data parsed as an array; otherwise, an empty array
     */
    public function toArray($data = null): array
    {
        // If no data is passed as a parameter, then use the data passed
        // via the constructor
        if ($data === null && func_num_args() === 0) {
            $data = $this->getData();
        }

        // Cast as an array if not already
        if (!is_array($data)) {
            $data = (array)$data;
        }

        $array = [];
        foreach ((array)$data as $key => $value) {
            if (is_object($value) || is_array($value)) {
                $array[$key] = $this->toArray($value);
            } else {
                $array[$key] = $value;
            }
        }

        return $array;
    }

    /**
     * Format data as XML
     *
     * @param mixed|null $data Optional data to pass, so as to override the data passed
     *                         to the constructor
     * @param SimpleXMLElement|null $structure
     * @param string $baseNode
     * @return string|null
     */
    public function toXml($data = null, ?SimpleXMLElement $structure = null, string $baseNode = 'xml'): ?string
    {
        if ($data === null && func_num_args() === 0) {
            $data = $this->getData();
        }

        if ($structure === null) {
            $structure = simplexml_load_string("<?xml version='1.0' encoding='utf-8'?><$baseNode />");
        }

        // Force it to be something useful
        if (!is_array($data) && !is_object($data)) {
            $data = (array)$data;
        }

        foreach ($data as $key => $value) {
            //change false/true to 0/1
            if (is_bool($value)) {
                $value = (int)$value;
            }

            // no numeric keys in our xml please!
            if (is_numeric($key)) {
                $this->ci->load->helper('inflector');
                // make string key...
                $key = (singular($baseNode) !== $baseNode) ? singular($baseNode) : 'item';
            }

            // replace anything not alpha numeric
            $key = preg_replace('/[^a-z_\-0-9]/i', '', $key);

            if ($key === '_attributes' && (is_array($value) || is_object($value))) {
                $attributes = $value;
                if (is_object($attributes)) {
                    $attributes = get_object_vars($attributes);
                }

                foreach ($attributes as $attribute_name => $attribute_value) {
                    $structure->addAttribute($attribute_name, $attribute_value);
                }
            // if there is another array found recursively call this function
            } elseif (is_array($value) || is_object($value)) {
                $node = $structure->addChild($key);

                // recursive call.
                $this->toXml($value, $node, $key);
            } else {
                // add single node.
                $value = htmlspecialchars(html_entity_decode($value, ENT_QUOTES, 'UTF-8'), ENT_QUOTES, 'UTF-8');

                $structure->addChild($key, $value);
            }
        }

        $output = $structure->asXML();
        return $output !== false ? $output : null;
    }

    /**
     * Encode data as json
     *
     * @param mixed|null $data Optional data to pass, so as to override the data
     *                         passed to the constructor
     * @return string Json representation of a value
     */
    public function toJson($data = null): string
    {
        // If no data is passed as a parameter, then use the data passed
        // via the constructor
        if ($data === null && func_num_args() === 0) {
            $data = $this->getData();
        }

        // Get the callback parameter (if set)
        $callback = $this->ci->input->get('callback');

        if (empty($callback) === true) {
            return json_encode($data, JSON_UNESCAPED_UNICODE);
        // We only honour a jsonp callback which are valid javascript identifiers
        } elseif (preg_match('/^[a-z_\$][a-z0-9\$_]*(\.[a-z_\$][a-z0-9\$_]*)*$/i', $callback)) {
            // Return the data as encoded json with a callback
            return $callback . '(' . json_encode($data, JSON_UNESCAPED_UNICODE) . ');';
        }

        // An invalid jsonp callback function provided.
        // Though I don't believe this should be hardcoded here
        $data['warning'] = sprintf($this->ci->lang->line('rest_invalid_jsonp_callback'), $callback);

        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param string|null $data XML string
     * @return array XML element object; otherwise, empty array
     */
    protected function fromXml(?string $data = null)
    {
        return $data ? (array)simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA) : [];
    }

    /**
     * @param string $data Encoded json string
     * @return mixed Decoded json string with leading and trailing whitespace removed
     */
    protected function fromJson($data)
    {
        return json_decode(trim($data));
    }

    /**
     * @param mixed $data
     */
    private function setData($data)
    {
        $this->data = $data;
    }
}
