<?php

/*
 * This file is part of the Austral Tools Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\ToolsBundle\Tests\DependencyInjection;

use Austral\ToolsBundle\Command\StatusCheckerCommand;
use Austral\ToolsBundle\DependencyInjection\AustralToolsExtension;
use Austral\ToolsBundle\TwigExtension\ToolsTwig;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * The services are defined explicitly: a missing or misnamed argument only shows up when the container
 * is compiled or the service is created. This test loads the extension, compiles the container and
 * creates every service of the bundle.
 */
final class ContainerTest extends TestCase
{
  /**
   * Public aliases: the supported way to get a service from the container.
   */
  private const array PUBLIC_ALIASES = [
    'austral.tools.debug',
    'austral.tools.status-checker',
  ];

  /**
   * Names available in Twig templates, as in 3.1 (plus the add_in_array filter added in 4.0).
   */
  private const array TWIG_FILTERS = [
    'add_in_array', 'array_to_string', 'asset_exist', 'file_exist', 'file_image_size', 'file_is_image',
    'file_mime_type', 'file_size', 'ksort', 'remove_multi_br', 'tools_dump', 'tools_dump_force',
    'tools_dump_kill', 'twig_filter_exists', 'unset_in_array', 'value_by_key',
  ];

  private const array TWIG_FUNCTIONS = [
    'add_in_array', 'array_to_string', 'asset_exist', 'file_exist', 'file_image_size', 'file_is_image',
    'file_mime_type', 'file_size', 'ksort', 'remove_multi_br', 'tools_dump', 'tools_dump_force',
    'tools_dump_kill', 'tools_is_dev', 'twig_filter_exists', 'unset_in_array', 'uuid', 'value_by_key',
  ];

  private const array TWIG_TESTS = ['filter_exists', 'instanceof'];

  /**
   * @return iterable<string, array{string, class-string}>
   */
  public static function serviceIds(): iterable
  {
    foreach (self::serviceClasses() as $id => $class)
    {
      yield $id => [$id, $class];
    }
  }

  /**
   * @param class-string $class
   */
  #[DataProvider('serviceIds')]
  public function testEveryServiceCanBeCreated(string $id, string $class): void
  {
    $container = self::compiledContainer(publicServices: true);

    self::assertInstanceOf($class, $container->get($id));
  }

  public function testOnlyTheAustralAliasesArePublic(): void
  {
    $container = self::loadExtension();

    foreach ($container->getDefinitions() as $id => $definition)
    {
      if (self::isServiceOfTheBundle($id))
      {
        self::assertFalse($definition->isPublic(), sprintf('The service "%s" must be private.', $id));
      }
    }
    $publicAliases = [];
    foreach ($container->getAliases() as $id => $alias)
    {
      if ($alias->isPublic())
      {
        $publicAliases[] = $id;
      }
    }
    sort($publicAliases);
    $expected = self::PUBLIC_ALIASES;
    sort($expected);
    self::assertSame($expected, $publicAliases);
  }

  public function testPublicAliasesCanBeFetched(): void
  {
    $classes = self::aliasClasses();
    $container = self::compiledContainer(publicServices: false);

    foreach (self::PUBLIC_ALIASES as $alias)
    {
      self::assertTrue($container->has($alias), sprintf('The alias "%s" must be public.', $alias));
      self::assertInstanceOf($classes[$alias], $container->get($alias));
    }
  }

  public function testTheCommandIsRegisteredWithItsNameAndTheEventDispatcher(): void
  {
    $container = self::compiledContainer(publicServices: true);

    $tags = $container->findTaggedServiceIds('console.command');
    self::assertArrayHasKey('austral.tools.status-checker.command', $tags);
    self::assertSame('austral:tools:status-checker', $tags['austral.tools.status-checker.command'][0]['command'] ?? null);

    $command = $container->get('austral.tools.status-checker.command');
    self::assertInstanceOf(StatusCheckerCommand::class, $command);
    $dispatcher = new \ReflectionProperty($command, 'eventDispatcher')->getValue($command);
    self::assertInstanceOf(EventDispatcherInterface::class, $dispatcher);
  }

  public function testTheTwigExtensionIsTaggedAndDeclaresTheTemplateNames(): void
  {
    $container = self::compiledContainer(publicServices: true);

    self::assertArrayHasKey(ToolsTwig::class, $container->findTaggedServiceIds('twig.extension'));

    $twig = $container->get(ToolsTwig::class);
    self::assertInstanceOf(ToolsTwig::class, $twig);
    self::assertSame(self::TWIG_FILTERS, self::sortedNames($twig->getFilters()));
    self::assertSame(self::TWIG_FUNCTIONS, self::sortedNames($twig->getFunctions()));
    self::assertSame(self::TWIG_TESTS, self::sortedNames($twig->getTests()));
  }

  /**
   * @param array<int|string, \Twig\TwigFilter|\Twig\TwigFunction|\Twig\TwigTest> $callables
   *
   * @return list<string>
   */
  private static function sortedNames(array $callables): array
  {
    $names = [];
    foreach ($callables as $callable)
    {
      $names[] = $callable->getName();
    }
    sort($names);

    return $names;
  }

  private static function isServiceOfTheBundle(string $id): bool
  {
    return str_starts_with($id, 'Austral\\ToolsBundle\\') || str_starts_with($id, 'austral.tools');
  }

  /**
   * @return array<string, class-string> the class of each service of the bundle, by service id
   */
  private static function serviceClasses(): array
  {
    $classes = [];
    foreach (self::loadExtension()->getDefinitions() as $id => $definition)
    {
      if (self::isServiceOfTheBundle($id))
      {
        $class = $definition->getClass() ?? $id;
        if (!class_exists($class))
        {
          self::fail(sprintf('The class "%s" of the service "%s" does not exist.', $class, $id));
        }
        $classes[$id] = $class;
      }
    }

    return $classes;
  }

  /**
   * @return array<string, class-string> the class of the service behind each public alias
   */
  private static function aliasClasses(): array
  {
    $container = self::loadExtension();
    $services = self::serviceClasses();
    $classes = [];
    foreach (self::PUBLIC_ALIASES as $alias)
    {
      self::assertTrue($container->hasAlias($alias), sprintf('The alias "%s" is not defined.', $alias));
      $target = (string) $container->getAlias($alias);
      self::assertArrayHasKey($target, $services, sprintf('The alias "%s" points to "%s", which is not a service of the bundle.', $alias, $target));
      $classes[$alias] = $services[$target];
    }

    return $classes;
  }

  /**
   * The event dispatcher, which the bundle needs from the application, is added as a minimal definition.
   * debug.stopwatch is optional and left out.
   */
  private static function loadExtension(): ContainerBuilder
  {
    $container = new ContainerBuilder();
    $container->setParameter('kernel.project_dir', sys_get_temp_dir());
    $container->setDefinition('event_dispatcher', new Definition(EventDispatcher::class));
    new AustralToolsExtension()->load([], $container);

    return $container;
  }

  private static function compiledContainer(bool $publicServices): ContainerBuilder
  {
    $container = self::loadExtension();
    if ($publicServices)
    {
      foreach ($container->getDefinitions() as $id => $definition)
      {
        if (self::isServiceOfTheBundle($id))
        {
          $definition->setPublic(true);
        }
      }
    }
    $container->compile();

    return $container;
  }
}
