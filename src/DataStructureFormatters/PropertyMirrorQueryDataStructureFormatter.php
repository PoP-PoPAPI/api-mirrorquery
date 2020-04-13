<?php
namespace PoP\APIMirrorQuery\DataStructureFormatters;

use PoP\ComponentModel\DataStructure\PropertyDataStructureFormatterTrait;

class PropertyMirrorQueryDataStructureFormatter extends MirrorQueryDataStructureFormatter
{
    use PropertyDataStructureFormatterTrait;

    public const NAME = 'props';
    public static function getName()
    {
        return self::NAME;
    }
}
