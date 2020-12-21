<?php

namespace RamdanRiawan;

class XMLConvertor
{
    private $xmlData;

    public function __construct($xmlData)
    {
        $this->xmlData = $xmlData;
    }

    private function domNodesToArray(array $tags, \DOMXPath $xpath)
    {
        $tagNameToArr = [];
        foreach ($tags as $tag) {
            $tagData = [];
            $attrs   = $tag->attributes ? iterator_to_array($tag->attributes) : [];
            $subTags = $tag->childNodes ? iterator_to_array($tag->childNodes) : [];
            foreach ($xpath->query('namespace::*', $tag) as $nsNode) {
                // the only way to get xmlns:*, see https://stackoverflow.com/a/2470433/2750743
                if ($tag->hasAttribute($nsNode->nodeName)) {
                    $attrs[] = $nsNode;
                }
            }

            foreach ($attrs as $attr) {
                $tagData[$attr->nodeName] = $attr->nodeValue;
            }
            if (count($subTags) === 1 && $subTags[0] instanceof \DOMText) {
                $text = $subTags[0]->nodeValue;
            } elseif (count($subTags) === 0) {
                $text = '';
            } else {
                // ignore whitespace (and any other text if any) between nodes
                $isNotDomText = function ($node) {return !($node instanceof \DOMText);};
                $realNodes       = array_filter($subTags, $isNotDomText);
                $subTagNameToArr = $this->domNodesToArray($realNodes, $xpath);
                $tagData         = array_merge($tagData, $subTagNameToArr);
                $text            = null;
            }
            if (!is_null($text)) {
                if ($attrs) {
                    if ($text) {
                        $tagData['_'] = $text;
                    }
                } else {
                    $tagData = $text;
                }
            }
            $keyName                  = $tag->nodeName;
            $tagNameToArr[$keyName][] = $tagData;
        }
        return $tagNameToArr;
    }

    public function toArray()
    {
        $doc = new \DOMDocument();
        $doc->loadXML($this->xmlData);
        $xpath = new \DOMXPath($doc);
        $tags  = $doc->childNodes ? iterator_to_array($doc->childNodes) : [];

        return $this->domNodesToArray($tags, $xpath);
    }

    public function toJson()
    {

        return json_encode($this->toArray());
    }
}

//9999999 is the length which fread stops to read.
$xmldata =
    '<body>
    <div>
        <span status="old">Trooper</span>
        <span status="old">Ultrablock</span>
        <span status="new">Bike</span>
    </div>
</body>
';

$xmlConvertor = new XMLConvertor($xmldata);

print_r($xmlConvertor->toJson());