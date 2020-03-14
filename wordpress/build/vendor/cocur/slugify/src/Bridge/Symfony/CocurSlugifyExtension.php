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

use ScopedGtbabel\Symfony\Component\DependencyInjection\ContainerBuilder;
use ScopedGtbabel\Symfony\Component\DependencyInjection\Definition;
use ScopedGtbabel\Symfony\Component\DependencyInjection\Reference;
use ScopedGtbabel\Symfony\Component\HttpKernel\DependencyInjection\Extension;
/**
 * CocurSlugifyExtension
 *
 * @package    cocur/slugify
 * @subpackage bridge
 * @author     Florian Eckerstorfer <florian@eckerstorfer.co>
 * @copyright  2012-2014 Florian Eckerstorfer
 * @license    http://www.opensource.org/licenses/MIT The MIT License
 */
class CocurSlugifyExtension extends \ScopedGtbabel\Symfony\Component\HttpKernel\DependencyInjection\Extension
{
    /**
     * {@inheritDoc}
     *
     * @param mixed[]          $configs
     * @param ContainerBuilder $container
     */
    public function load(array $configs, \ScopedGtbabel\Symfony\Component\DependencyInjection\ContainerBuilder $container)
    {
        $configuration = new \ScopedGtbabel\Cocur\Slugify\Bridge\Symfony\Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        if (empty($config['rulesets'])) {
            unset($config['rulesets']);
        }
        // Extract slugify arguments from config
        $slugifyArguments = \array_intersect_key($config, \array_flip(['lowercase', 'trim', 'strip_tags', 'separator', 'regexp', 'rulesets']));
        $container->setDefinition('cocur_slugify', new \ScopedGtbabel\Symfony\Component\DependencyInjection\Definition('ScopedGtbabel\\Cocur\\Slugify\\Slugify', [$slugifyArguments]));
        $container->setDefinition('cocur_slugify.twig.slugify', new \ScopedGtbabel\Symfony\Component\DependencyInjection\Definition('ScopedGtbabel\\Cocur\\Slugify\\Bridge\\Twig\\SlugifyExtension', [new \ScopedGtbabel\Symfony\Component\DependencyInjection\Reference('cocur_slugify')]))->addTag('twig.extension')->setPublic(\false);
        $container->setAlias('slugify', 'cocur_slugify');
        $container->setAlias('ScopedGtbabel\\Cocur\\Slugify\\SlugifyInterface', 'cocur_slugify');
    }
}
