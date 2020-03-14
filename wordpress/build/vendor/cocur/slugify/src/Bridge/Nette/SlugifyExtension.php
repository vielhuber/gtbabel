<?php

namespace ScopedGtbabel\Cocur\Slugify\Bridge\Nette;

use ScopedGtbabel\Nette\DI\CompilerExtension;
use ScopedGtbabel\Nette\DI\ServiceDefinition;
/**
 * SlugifyExtension
 *
 * @package    cocur/slugify
 * @subpackage bridge
 * @author     Lukáš Unger <looky.msc@gmail.com>
 * @license    http://www.opensource.org/licenses/MIT The MIT License
 */
class SlugifyExtension extends \ScopedGtbabel\Nette\DI\CompilerExtension
{
    public function loadConfiguration()
    {
        $builder = $this->getContainerBuilder();
        $builder->addDefinition($this->prefix('slugify'))->setClass('ScopedGtbabel\\Cocur\\Slugify\\SlugifyInterface')->setFactory('ScopedGtbabel\\Cocur\\Slugify\\Slugify');
        $builder->addDefinition($this->prefix('helper'))->setClass('ScopedGtbabel\\Cocur\\Slugify\\Bridge\\Latte\\SlugifyHelper')->setAutowired(\false);
    }
    public function beforeCompile()
    {
        $builder = $this->getContainerBuilder();
        $self = $this;
        $registerToLatte = function (\ScopedGtbabel\Nette\DI\ServiceDefinition $def) use($self) {
            $def->addSetup('addFilter', ['slugify', [$self->prefix('@helper'), 'slugify']]);
        };
        $latteFactory = $builder->getByType('ScopedGtbabel\\Nette\\Bridges\\ApplicationLatte\\ILatteFactory') ?: 'nette.latteFactory';
        if ($builder->hasDefinition($latteFactory)) {
            $registerToLatte($builder->getDefinition($latteFactory));
        }
        if ($builder->hasDefinition('nette.latte')) {
            $registerToLatte($builder->getDefinition('nette.latte'));
        }
    }
}
