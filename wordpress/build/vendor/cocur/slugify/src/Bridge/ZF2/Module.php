<?php

namespace ScopedGtbabel\Cocur\Slugify\Bridge\ZF2;

use ScopedGtbabel\Zend\ModuleManager\Feature\ServiceProviderInterface;
use ScopedGtbabel\Zend\ModuleManager\Feature\ViewHelperProviderInterface;
/**
 * Class Module
 * @package    cocur/slugify
 * @subpackage bridge
 * @license    http://www.opensource.org/licenses/MIT The MIT License
 */
class Module implements \ScopedGtbabel\Zend\ModuleManager\Feature\ServiceProviderInterface, \ScopedGtbabel\Zend\ModuleManager\Feature\ViewHelperProviderInterface
{
    const CONFIG_KEY = 'cocur_slugify';
    /**
     * Expected to return \Zend\ServiceManager\Config object or array to
     * seed such an object.
     *
     * @return array<string,array<string,string>>
     */
    public function getServiceConfig()
    {
        return ['factories' => ['ScopedGtbabel\\Cocur\\Slugify\\Slugify' => 'ScopedGtbabel\\Cocur\\Slugify\\Bridge\\ZF2\\SlugifyService'], 'aliases' => ['slugify' => 'ScopedGtbabel\\Cocur\\Slugify\\Slugify']];
    }
    /**
     * Expected to return \Zend\ServiceManager\Config object or array to
     * seed such an object.
     *
     * @return array<string,array<string,string>>|\Zend\ServiceManager\Config
     */
    public function getViewHelperConfig()
    {
        return ['factories' => ['slugify' => 'ScopedGtbabel\\Cocur\\Slugify\\Bridge\\ZF2\\SlugifyViewHelperFactory']];
    }
}
