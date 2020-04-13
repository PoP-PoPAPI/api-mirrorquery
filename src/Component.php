<?php

declare(strict_types=1);

namespace PoP\APIMirrorQuery;

use PoP\Root\Component\AbstractComponent;
use PoP\APIMirrorQuery\Config\ServiceConfiguration;
use PoP\Root\Component\YAMLServicesTrait;

/**
 * Initialize component
 */
class Component extends AbstractComponent
{
    // const VERSION = '0.1.0';
    use YAMLServicesTrait;
    /**
     * Initialize services
     */
    public static function init()
    {
        parent::init();
        self::initYAMLServices(dirname(__DIR__));
        ServiceConfiguration::init();
    }
}
