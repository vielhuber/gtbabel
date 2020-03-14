<?php

namespace ScopedGtbabel\Cocur\Slugify\Bridge\Latte;

use ScopedGtbabel\Cocur\Slugify\SlugifyInterface;
/**
 * SlugifyHelper
 *
 * @package    cocur/slugify
 * @subpackage bridge
 * @author     Lukáš Unger <looky.msc@gmail.com>
 * @license    http://www.opensource.org/licenses/MIT The MIT License
 */
class SlugifyHelper
{
    /** @var SlugifyInterface */
    private $slugify;
    /**
     * @codeCoverageIgnore
     */
    public function __construct(\ScopedGtbabel\Cocur\Slugify\SlugifyInterface $slugify)
    {
        $this->slugify = $slugify;
    }
    /**
     * @param string      $string
     * @param string|null $separator
     *
     * @return string
     */
    public function slugify($string, $separator = null)
    {
        return $this->slugify->slugify($string, $separator);
    }
}
