<?php
/*
 * This file is part of the Austral Tools Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Austral\ToolsBundle\DependencyInjection\Compiler;

use Austral\ToolsBundle\AustralTools;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Austral Tools Load Doctrine Resolve.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class DoctrineResolveTargetEntityPass implements CompilerPassInterface
{
  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container)
  {
    $extensionDoctrineConfig = AustralTools::first($container->getExtensionConfig("doctrine"));

    if(AustralTools::getValueByKey($extensionDoctrineConfig['dbal'],'driver', null) === "pdo_pgsql")
    {
      $ormConfigDef = $container->findDefinition('doctrine.orm.default_configuration');
      $ormConfigDef->addMethodCall('addCustomStringFunction', ["JSON_GET_FIELD_AS_TEXT", "MartinGeorgiev\Doctrine\ORM\Query\AST\Functions\JsonGetFieldAsText"]);
      $ormConfigDef->addMethodCall('addCustomStringFunction', ["JSONB_EACH_TEXT", "MartinGeorgiev\Doctrine\ORM\Query\AST\Functions\JsonbEachText"]);
    }
  }
}