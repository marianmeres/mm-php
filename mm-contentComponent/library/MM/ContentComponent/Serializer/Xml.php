<?php
/**
 * Created by PhpStorm.
 * User: mm
 * Date: 10/07/15
 * Time: 16:09
 */

namespace MM\ContentComponent\Serializer;

use MM\ContentComponent\Model;

class Xml extends AbstractSerializer
{
    /**
     * @var string
     */
    public $rootNodeName = 'component';

    /**
     * @param Model $model
     * @param array $options
     * @return mixed
     */
    public function serialize(Model $model, array $options = array())
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;
        $root = $dom->createElement($this->rootNodeName);
        $dom->appendChild($root);

        if (!empty($model->componentAttributes)) {
            foreach ((array) $model->componentAttributes as $an => $av) {
                $a = $dom->createAttribute($an);
                $a->value = "$av";
                $root->appendChild($a);
            }
        }

        $data = $model->toArray();

        foreach ($data as $key => $value) {
            $n = $dom->createElement($key);
            if ("" != $value) {
                $n->appendChild($dom->createCDATASection("\n$value\n"));
            }
            $root->appendChild($n);

            foreach ((array) $model->attr($key) as $an => $av){
                $a = $dom->createAttribute($an);
                $a->value = "$av";
                $n->appendChild($a);
            }
        }

        return $dom->saveXML();
    }

    /**
     * @param $data
     * @param array $options
     * @return array
     */
    public function unserialize($data, array $options = array())
    {
        $_data = $_attrs = [];

        $xml = simplexml_load_string($data);

        // loop top level
        /** @var \SimpleXMLElement $node */
        foreach ($xml as $node) {
            $key = $node->getName();
            $val = (string) $xml->$key;
            $_data[$key] = trim($val);
            foreach ($node->attributes() as $an => $av){
                $_attrs[$key][$an] = (string) $av;
            }
        }
        //prx($_attrs);

        return [
            '_data' => $_data,
            '_attrs' => $_attrs
        ];
    }
}