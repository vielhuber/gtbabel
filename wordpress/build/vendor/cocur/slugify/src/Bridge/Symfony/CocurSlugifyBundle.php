<?php

/**
 * This file is part of cocur/slugify.
 *
 * (c) Florian Eckerstorfer <florian@eckerstorfer.co>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace ScopedGtbabel\Cocur\Slugify\Bridge\Symfony;

use ScopedGtbabel\Symfony\Component\HttpKernel\Bundle\Bundle;
/**
 * CocurSlugifyBundle
 *
 * @package    cocur/slugify
 * @subpackage bridge
 * @author     Florian Eckerstorfer <florian@eckerstorfer.co>
 * @copyright  2012-2014 Florian Eckerstorfer
 * @license    http://www.opensource.org/licenses/MIT The MIT License
 */
class CocurSlugifyBundle extends \ScopedGtbabel\Symfony\Component\HttpKernel\Bundle\Bundle
{
    public function getContainerExtension()
    {
        return new \ScopedGtbabel\Cocur\Slugify\Bridge\Symfony\CocurSlugifyExtension();
    }
}
