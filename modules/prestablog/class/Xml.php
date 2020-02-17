<?php
/**
 * 2008 - 2019 (c) Prestablog
 *
 * MODULE PrestaBlog
 *
 * @author    Prestablog
 * @copyright Copyright (c) permanent, Prestablog
 * @license   Commercial
 * @version    4.2.2
 */

class Xml
{
    public static function build($input, $options = array())
    {
        if (!is_array($options)) {
            $options = array('return' => (string)$options);
        }

        $defaults = array(
            'return' => 'simplexml',
            'loadEntities' => false,
        );
        $options = array_merge($defaults, $options);

        if (is_array($input) || is_object($input)) {
            return self::fromArray((array)$input, $options);
        } elseif (strpos($input, '<') !== false) {
            return self::loadXmlR($input, $options);
        } elseif (file_exists($input)) {
            return self::loadXmlR(Tools::file_get_contents($input), $options);
        }
    }

    protected static function loadXmlR($input, $options)
    {
        $has_disable = function_exists('libxml_disable_entity_loader');
        $internal_errors = libxml_use_internal_errors(true);
        if ($has_disable && !$options['loadEntities']) {
            libxml_disable_entity_loader(true);
        } try {
            if ($options['return'] === 'simplexml' || $options['return'] === 'simplexmlelement') {
                $xml = new SimpleXMLElement($input, LIBXML_NOCDATA);
            } else {
                $xml = new DOMDocument();
                $xml->loadXML($input);
            }
        } catch (Exception $e) {
            $xml = null;
        }

        if ($has_disable && !$options['loadEntities']) {
            libxml_disable_entity_loader(false);
        }

        libxml_use_internal_errors($internal_errors);
        if ($xml === null) {
            throw new Exception('Xml cannot be read.');
        }

        return $xml;
    }

    public static function fromArray($input, $options = array())
    {
        if (!is_array($input) || count($input) !== 1) {
            throw new Exception('Invalid input.');
        }

        $key = key($input);
        if (is_int($key)) {
            throw new Exception('The key of input must be alphanumeric');
        }

        if (!is_array($options)) {
            $options = array('format' => (string)$options);
        }

        $defaults = array(
            'format' => 'tags',
            'version' => '1.0',
            //'encoding' => Configure::read('App.encoding'),
            'return' => 'simplexml'
        );
        $options = array_merge($defaults, $options);

        $dom = new DOMDocument($options['version'], $options['encoding']);
        self::fromArrayR($dom, $dom, $input, $options['format']);

        $options['return'] = Tools::strtolower($options['return']);
        if ($options['return'] === 'simplexml' || $options['return'] === 'simplexmlelement') {
            return new SimpleXMLElement($dom->saveXML());
        }

        return $dom;
    }

    protected static function fromArrayR($dom, $node, &$data, $format)
    {
        if (empty($data) || !is_array($data)) {
            return;
        }

        foreach ($data as $key => $value) {
            if (is_string($key)) {
                if (!is_array($value)) {
                    if (is_bool($value)) {
                        $value = (int)$value;
                    } elseif ($value === null) {
                        $value = '';
                    }

                    $is_namespace = strpos($key, 'xmlns:');
                    if ($is_namespace !== false) {
                        $aa = 'http';
                        $bb = 'www.w3.org/2000/xmlns/';
                        $node->setAttributeNS($aa.'://'.$bb, $key, $value);
                        continue;
                    }
                    if ($key[0] !== '@' && $format === 'tags') {
                        $child = null;
                        if (!is_numeric($value)) {
                            // Escape special characters
                            // http://www.w3.org/TR/REC-xml/#syntax
                            // https://bugs.php.net/bug.php?id=36795
                            $child = $dom->createElement($key, '');
                            $child->appendChild(new DOMText($value));
                        } else {
                            $child = $dom->createElement($key, $value);
                        }

                        $node->appendChild($child);
                    } else {
                        if ($key[0] === '@') {
                            $key = Tools::substr($key, 1);
                        }

                        $attribute = $dom->createAttribute($key);
                        $attribute->appendChild($dom->createTextNode($value));
                        $node->appendChild($attribute);
                    }
                } else {
                    if ($key[0] === '@') {
                        throw new Exception('Invalid array');
                    }

                    if (is_numeric(implode('', array_keys($value)))) {
                        foreach ($value as $item) {
                            $item_data = compact('dom', 'node', 'key', 'format');
                            $item_data['value'] = $item;
                            self::createChildR($item_data);
                        }
                    } else {
                        self::createChildR(compact('dom', 'node', 'key', 'value', 'format'));
                    }
                }
            } else {
                throw new Exception('Invalid array');
            }
        }
    }

    protected static function createChildR($data)
    {
        $value = null;
        $dom = null;
        $key = null;
        $format = null;
        $node = null;

        extract($data);

        $child_ns = $child_value = null;
        if (is_array($value)) {
            if (isset($value['@'])) {
                $child_value = (string)$value['@'];
                unset($value['@']);
            }
            if (isset($value['xmlns:'])) {
                $child_ns = $value['xmlns:'];
                unset($value['xmlns:']);
            }
        } elseif (!empty($value) || $value === 0) {
            $child_value = (string)$value;
        }

        $child = $dom->createElement($key);
        if (!is_null($child_value)) {
            $child->appendChild($dom->createTextNode($child_value));
        }

        if ($child_ns) {
            $child->setAttribute('xmlns', $child_ns);
        }

        self::fromArrayR($dom, $child, $value, $format);
        $node->appendChild($child);
    }

    public static function toArray($obj)
    {
        if ($obj instanceof DOMNode) {
            $obj = simplexml_import_dom($obj);
        }

        if (!($obj instanceof SimpleXMLElement)) {
            throw new Exception('The input is not instance of SimpleXMLElement, DOMDocument or DOMNode.');
        }

        $result = array();
        $namespaces = array_merge(array('' => ''), $obj->getNamespaces(true));
        self::toArrayR($obj, $result, '', array_keys($namespaces));
        return $result;
    }

    protected static function toArrayR($xml, &$parent_data, $ns, $namespaces)
    {
        $data = array();

        foreach ($namespaces as $namespace) {
            foreach ($xml->attributes($namespace, true) as $key => $value) {
                if (!empty($namespace)) {
                    $key = $namespace.':'.$key;
                }

                $data['@'.$key] = (string)$value;
            }

            foreach ($xml->children($namespace, true) as $child) {
                self::toArrayR($child, $data, $namespace, $namespaces);
            }
        }

        $as_string = trim((string)$xml);
        if (empty($data)) {
            $data = $as_string;
        } elseif (Tools::strlen($as_string) > 0) {
            $data['@'] = $as_string;
        }

        if (!empty($ns)) {
            $ns .= ':';
        }

        $name = $ns.$xml->getName();
        if (isset($parent_data[$name])) {
            if (!is_array($parent_data[$name]) || !isset($parent_data[$name][0])) {
                $parent_data[$name] = array($parent_data[$name]);
            }

            $parent_data[$name][] = $data;
        } else {
            $parent_data[$name] = $data;
        }
    }
}
