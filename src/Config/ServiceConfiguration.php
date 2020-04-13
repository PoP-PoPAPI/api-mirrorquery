<?php

declare(strict_types=1);

namespace PoP\APIMirrorQuery\Config;

use PoP\ComponentModel\Container\ContainerBuilderUtils;
use PoP\Root\Component\PHPServiceConfigurationTrait;

class ServiceConfiguration
{
    use PHPServiceConfigurationTrait;

    protected static function configure()
    {
        ContainerBuilderUtils::injectServicesIntoService(
            'data_structure_manager',
            'PoP\\APIMirrorQuery\\DataStructureFormatters',
            'add'
        );
    }
}
