<?php

/**
 * This file is part of cocur/slugify.
 *
 * (c) Florian Eckerstorfer <florian@eckerstorfer.co>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace ScopedGtbabel\Cocur\Slugify\Bridge\Plum;

use ScopedGtbabel\Plum\Plum\Converter\ConverterInterface;
use ScopedGtbabel\Cocur\Slugify\Slugify;
use ScopedGtbabel\Cocur\Slugify\SlugifyInterface;
/**
 * SlugifyConverter
 *
 * @package   Cocur\Slugify\Bridge\Plum
 * @author    Florian Eckerstorfer <florian@eckerstorfer.co>
 * @copyright 2015 Florian Eckerstorfer
 */
class SlugifyConverter implements \ScopedGtbabel\Plum\Plum\Converter\ConverterInterface
{
    /** @var Slugify */
    private $slugify;
    /**
     * @param SlugifyInterface|null $slugify
     */
    public function __construct(\ScopedGtbabel\Cocur\Slugify\SlugifyInterface $slugify = null)
    {
        if ($slugify === null) {
            $slugify = new \ScopedGtbabel\Cocur\Slugify\Slugify();
        }
        $this->slugify = $slugify;
    }
    /**
     * @param string $item
     *
     * @return string
     */
    public function convert($item)
    {
        return $this->slugify->slugify($item);
    }
}
