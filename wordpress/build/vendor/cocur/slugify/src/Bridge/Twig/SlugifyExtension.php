<?php

/**
 * This file is part of cocur/slugify.
 *
 * (c) Florian Eckerstorfer <florian@eckerstorfer.co>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace ScopedGtbabel\Cocur\Slugify\Bridge\Twig;

use ScopedGtbabel\Cocur\Slugify\SlugifyInterface;
use ScopedGtbabel\Twig\Extension\AbstractExtension;
use ScopedGtbabel\Twig\TwigFilter;
/**
 * SlugifyExtension
 *
 * @package    cocur/slugify
 * @subpackage bridge
 * @author     Florian Eckerstorfer <florian@eckerstorfer.co>
 * @copyright  2012-2015 Florian Eckerstorfer
 * @license    http://www.opensource.org/licenses/MIT The MIT License
 */
class SlugifyExtension extends \ScopedGtbabel\Twig\Extension\AbstractExtension
{
    /**
     * @var SlugifyInterface
     */
    private $slugify;
    /**
     * Constructor.
     *
     * @param SlugifyInterface $slugify
     *
     * @codeCoverageIgnore
     */
    public function __construct(\ScopedGtbabel\Cocur\Slugify\SlugifyInterface $slugify)
    {
        $this->slugify = $slugify;
    }
    /**
     * Returns the Twig functions of this extension.
     *
     * @return TwigFilter[]
     */
    public function getFilters()
    {
        return [new \ScopedGtbabel\Twig\TwigFilter('slugify', [$this, 'slugifyFilter'])];
    }
    /**
     * Slugify filter.
     *
     * @param string      $string
     * @param string|null $separator
     *
     * @return string
     */
    public function slugifyFilter($string, $separator = null)
    {
        return $this->slugify->slugify($string, $separator);
    }
    /**
     * get Name
     *
     * @return string
     */
    public function getName()
    {
        return "SlugifyExtension";
    }
}
