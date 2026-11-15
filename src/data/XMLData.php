<?php

namespace OpenPaymentSolutions\TranzWarePaymentGateway\Data;

use SimpleXMLElement;

class XMLData
{
    /**
     * Retain XML fields outside the known paths, including attributes and repeated elements.
     *
     * @param SimpleXMLElement $xml
     * @param array $known
     *
     * @return array
     */
    public static function unparsed(SimpleXMLElement $xml, array $known): array
    {
        $data = [];
        foreach ($xml->attributes() as $name => $value) {
            $data['@attributes'][$name] = (string)$value;
        }
        foreach ($xml->getNamespaces(false) as $prefix => $namespace) {
            if ($prefix === '') {
                continue;
            }
            foreach ($xml->attributes($namespace) as $name => $value) {
                $data['@attributes'][$prefix . ':' . $name] = (string)$value;
            }
        }
        $groups = [];
        foreach ($xml->xpath('./*') as $child) {
            preg_match('~^<([^\s/>]+)~', $child->asXML(), $name);
            $name = $name[1];
            $namespace = $child->getNamespaces(false)[''] ?? null;
            if (!str_contains($name, ':') && $namespace !== null) {
                $name = '{' . $namespace . '}' . $name;
            }
            if (array_key_exists($name, $known)) {
                $value = self::unparsed($child, is_array($known[$name]) ? $known[$name] : []);
                if ($value === []) {
                    continue;
                }
            } else {
                $value = self::value($child);
            }
            $groups[$name][] = $value;
        }
        foreach ($groups as $name => $values) {
            $data[$name] = count($values) === 1 ? $values[0] : $values;
        }
        return $data;
    }

    /**
     * Preserve an unknown XML value without losing nested fields or text.
     *
     * @param SimpleXMLElement $xml
     *
     * @return string|array
     */
    private static function value(SimpleXMLElement $xml): string|array
    {
        $data = self::unparsed($xml, []);
        if ($data === []) {
            return (string)$xml;
        }
        $text = (string)$xml;
        if (trim($text) !== '') {
            $data['#text'] = $text;
        }
        return $data;
    }
}
