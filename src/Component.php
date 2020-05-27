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
    use YAMLServicesTrait;

    // const VERSION = '0.1.0';

    public static function getDependedComponentClasses(): array
    {
        return [
            \PoP\API\Component::class,
        ];
    }
    /**
     * Initialize services
     */
    protected static function doInitialize(array $configuration = [], bool $skipSchema = false): void
    {
        parent::doInitialize($configuration, $skipSchema);
        self::initYAMLServices(dirname(__DIR__));
        ServiceConfiguration::initialize();
    }
}
