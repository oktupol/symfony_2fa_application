<?php
/**
 * Created by PhpStorm.
 * User: Sebastian
 * Date: 2016-10-16
 * Time: 12:39
 */

namespace AppBundle\DependencyInjection;


use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OverrideSchebTwoFactorCompilerPass implements CompilerPassInterface 
{
    /**
     * @param ContainerBuilder $container
     *
     * Setting a longer length for the google authenticator secret
     */
    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition('scheb_two_factor.security.google');

        $definition->setArguments(array(6, 30));
    }
}