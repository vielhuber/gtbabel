<?php

/**
 * This file is part of cocur/slugify.
 *
 * (c) Florian Eckerstorfer <florian@eckerstorfer.co>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace ScopedGtbabel\Cocur\Slugify\Bridge\Laravel;

use ScopedGtbabel\Cocur\Slugify\Slugify;
use ScopedGtbabel\Illuminate\Support\ServiceProvider as LaravelServiceProvider;
/**
 * SlugifyServiceProvider
 *
 * @package    cocur/slugify
 * @subpackage bridge
 * @author     Florian Eckerstorfer <florian@eckerstorfer.co>
 * @author     Colin Viebrock
 * @copyright  2012-2014 Florian Eckerstorfer
 * @license    http://www.opensource.org/licenses/MIT The MIT License
 */
class SlugifyServiceProvider extends \ScopedGtbabel\Illuminate\Support\ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = \true;
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('slugify', function () {
            return new \ScopedGtbabel\Cocur\Slugify\Slugify();
        });
    }
    /**
     * Get the services provided by the provider.
     *
     * @return string[]
     */
    public function provides()
    {
        return ['slugify'];
    }
}
