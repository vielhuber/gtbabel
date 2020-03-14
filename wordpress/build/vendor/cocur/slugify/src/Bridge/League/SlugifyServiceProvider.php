<?php

namespace ScopedGtbabel\Cocur\Slugify\Bridge\League;

use ScopedGtbabel\Cocur\Slugify\RuleProvider\DefaultRuleProvider;
use ScopedGtbabel\Cocur\Slugify\RuleProvider\RuleProviderInterface;
use ScopedGtbabel\Cocur\Slugify\Slugify;
use ScopedGtbabel\Cocur\Slugify\SlugifyInterface;
use ScopedGtbabel\League\Container\ServiceProvider\AbstractServiceProvider;
class SlugifyServiceProvider extends \ScopedGtbabel\League\Container\ServiceProvider\AbstractServiceProvider
{
    protected $provides = [\ScopedGtbabel\Cocur\Slugify\SlugifyInterface::class];
    public function register()
    {
        $this->container->share(\ScopedGtbabel\Cocur\Slugify\SlugifyInterface::class, function () {
            $options = [];
            if ($this->container->has('config.slugify.options')) {
                $options = $this->container->get('config.slugify.options');
            }
            $provider = null;
            if ($this->container->has(\ScopedGtbabel\Cocur\Slugify\RuleProvider\RuleProviderInterface::class)) {
                /* @var RuleProviderInterface $provider */
                $provider = $this->container->get(\ScopedGtbabel\Cocur\Slugify\RuleProvider\RuleProviderInterface::class);
            }
            return new \ScopedGtbabel\Cocur\Slugify\Slugify($options, $provider);
        });
    }
}
