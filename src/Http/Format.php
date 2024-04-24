<?php

namespace Moudarir\CodeigniterApi\Http;

use CI_Controller;
use Moudarir\CodeigniterApi\Exceptions\FormatException;
use Moudarir\CodeigniterApi\Helpers\CommonHelper;
use function get_instance;
use function singular;

class Format
{

    private CI_Controller $ci;

    /**
     * @var mixed
     */
    private $data = [];

    /**
     * DO NOT CALL THIS DIRECTLY, USE factory()
     *
     * @throws FormatException
     */
    public function __construct($data = null, ?string $fromType = null)
    {
        $this->ci =& get_instance();

        if ($fromType !== null) {
            $method = CommonHelper::stringToCamelcase('from_' . $fromType);
            if (\method_exists($this, $method)) {
                $data = \call_user_func([$this, $method], $data);
            } else {
                $message = \sprintf($this->ci->lang->line('rest_response_format_not_supported'), \strtoupper($fromType));
                throw new FormatException($message);
            }
        }

        $this->setData($data);
    }

    /**
     * Create an instance of the format class
     * e.g: echo $this->format->factory(['foo' => 'bar'])->to_csv();
     *
     * @throws FormatException
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
     * @param mixed|null $data
     */
    public function toArray($data = null): array
    {
        if ($data === null && \func_num_args() === 0) {
            $data = $this->getData();
        }

        if (!\is_array($data)) {
            $data = (array)$data;
        }

        $array = [];
        foreach ($data as $key => $value) {
            if (\is_object($value) || \is_array($value)) {
                $array[$key] = $this->toArray($value);
            } else {
                $array[$key] = $value;
            }
        }

        return $array;
    }

    /**
     * @param mixed|null $data
     */
    public function toXml($data = null, ?\SimpleXMLElement $structure = null, string $baseNode = 'xml'): ?string
    {
        if ($data === null && \func_num_args() === 0) {
            $data = $this->getData();
        }

        if ($structure === null) {
            $structure = \simplexml_load_string("<?xml version='1.0' encoding='utf-8'?><$baseNode />");
        }

        if (!\is_array($data) && !\is_object($data)) {
            $data = (array)$data;
        }

        foreach ($data as $key => $value) {
            //change false/true to 0/1
            if (\is_bool($value)) {
                $value = (int)$value;
            }

            // no numeric keys in our xml please!
            if (\is_numeric($key)) {
                $this->ci->load->helper('inflector');
                $key = (singular($baseNode) !== $baseNode) ? singular($baseNode) : 'item';
            }

            // replace anything not alphanumeric
            $key = \preg_replace('/[^a-z_\-0-9]/i', '', $key);

            if ($key === '_attributes' && (\is_array($value) || \is_object($value))) {
                $attributes = $value;
                if (\is_object($attributes)) {
                    $attributes = \get_object_vars($attributes);
                }

                foreach ($attributes as $attribute_name => $attribute_value) {
                    $structure->addAttribute($attribute_name, $attribute_value);
                }
            } elseif (\is_array($value) || \is_object($value)) {
                $this->toXml($value, $structure->addChild($key), $key);
            } else {
                if (\is_string($value)) {
                    $value = \htmlspecialchars(\html_entity_decode($value, ENT_QUOTES, 'UTF-8'), ENT_QUOTES);
                }

                $structure->addChild($key, $value);
            }
        }

        $output = $structure->asXML();
        return $output !== false ? $output : null;
    }

    /**
     * @param mixed|null $data
     */
    public function toJson($data = null): string
    {
        if ($data === null && \func_num_args() === 0) {
            $data = $this->getData();
        }

        // Get the callback parameter (if set)
        $callback = $this->ci->input->get('callback');

        if (empty($callback) === true) {
            return \json_encode($data, JSON_UNESCAPED_UNICODE);
        } elseif (\preg_match('/^[a-z_\$][a-z0-9\$_]*(\.[a-z_\$][a-z0-9\$_]*)*$/i', $callback)) {
            return $callback . '(' . \json_encode($data, JSON_UNESCAPED_UNICODE) . ');';
        }

        // An invalid jsonp callback function provided.
        // Though I don't believe this should be hardcoded here
        $data['warning'] = \sprintf($this->ci->lang->line('rest_invalid_jsonp_callback'), $callback);

        return \json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    protected function fromXml(?string $data = null): array
    {
        return $data ? (array)\simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA) : [];
    }

    protected function fromJson(string $data)
    {
        return \json_decode(\trim($data), true);
    }

    /**
     * @param mixed $data
     */
    private function setData($data)
    {
        $this->data = $data;
    }
}
