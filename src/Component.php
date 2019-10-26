<?php
namespace PoP\APIMirrorQuery;

use PoP\Root\Component\AbstractComponent;
use PoP\APIMirrorQuery\Config\ServiceConfiguration;

/**
 * Initialize component
 */
class Component extends AbstractComponent
{
    // const VERSION = '0.1.0';

    /**
     * Initialize services
     */
    public static function init()
    {
        parent::init();
        ServiceConfiguration::init();
    }
}
