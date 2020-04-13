<?php
namespace PoP\APIMirrorQuery\DataStructureFormatters;

use PoP\ComponentModel\DataStructure\XMLDataStructureFormatterTrait;

class XMLMirrorQueryDataStructureFormatter extends MirrorQueryDataStructureFormatter
{
    use XMLDataStructureFormatterTrait;

    public const NAME = 'xml';
    public static function getName()
    {
        return self::NAME;
    }
}
