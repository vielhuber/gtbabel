<?php

namespace ScopedGtbabel\Cocur\Slugify\Bridge\ZF2;

use ScopedGtbabel\Cocur\Slugify\Slugify;
use ScopedGtbabel\Zend\ServiceManager\ServiceManager;
/**
 * Class SlugifyService
 * @package    cocur/slugify
 * @subpackage bridge
 * @license    http://www.opensource.org/licenses/MIT The MIT License
 */
class SlugifyService
{
    /**
     * @param ServiceManager $sm
     *
     * @return Slugify
     */
    public function __invoke($sm)
    {
        $config = $sm->get('Config');
        $options = isset($config[\ScopedGtbabel\Cocur\Slugify\Bridge\ZF2\Module::CONFIG_KEY]['options']) ? $config[\ScopedGtbabel\Cocur\Slugify\Bridge\ZF2\Module::CONFIG_KEY]['options'] : [];
        $provider = isset($config[\ScopedGtbabel\Cocur\Slugify\Bridge\ZF2\Module::CONFIG_KEY]['provider']) ? $config[\ScopedGtbabel\Cocur\Slugify\Bridge\ZF2\Module::CONFIG_KEY]['provider'] : null;
        return new \ScopedGtbabel\Cocur\Slugify\Slugify($options, $provider);
    }
}
