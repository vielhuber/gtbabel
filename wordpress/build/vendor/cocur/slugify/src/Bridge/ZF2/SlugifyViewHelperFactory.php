<?php

namespace ScopedGtbabel\Cocur\Slugify\Bridge\ZF2;

use ScopedGtbabel\Cocur\Slugify\Slugify;
use ScopedGtbabel\Zend\View\HelperPluginManager;
/**
 * Class SlugifyViewHelperFactory
 * @package    cocur/slugify
 * @subpackage bridge
 * @license    http://www.opensource.org/licenses/MIT The MIT License
 */
class SlugifyViewHelperFactory
{
    /**
     * @param HelperPluginManager $vhm
     *
     * @return SlugifyViewHelper
     */
    public function __invoke($vhm)
    {
        /** @var Slugify $slugify */
        $slugify = $vhm->getServiceLocator()->get('ScopedGtbabel\\Cocur\\Slugify\\Slugify');
        return new \ScopedGtbabel\Cocur\Slugify\Bridge\ZF2\SlugifyViewHelper($slugify);
    }
}
