<?php

/*
 * This file is part of the Austral Tools Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\ToolsBundle\Tests\Services;

use Austral\ToolsBundle\Services\ServicesStatusChecker;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ServicesStatusCheckerTest extends KernelTestCase
{
  /** @var ContainerInterface|null */
  private ?ContainerInterface $container = null;

  protected function setUp(): void
  {
    parent::setUp();
    $this->container = self::getContainer();
  }

  /**
   * Helper: build the SUT (adjust signature if your constructor differs).
   *
   * If the ServicesStatusChecker constructor is something like:
   *   __construct(ContainerInterface $container, #[Autowire('%kernel.project_dir%')] string $projectDir),
   * this will match. If not, adapt accordingly.
   */
  private function createSut(): ServicesStatusChecker
  {
    if ($this->container instanceof ContainerInterface)
    {
      $servicesStatusChecker = $this->container->get(ServicesStatusChecker::class);
      if ($servicesStatusChecker instanceof ServicesStatusChecker)
      {
        return $servicesStatusChecker;
      }
    }
    throw new \RuntimeException('Container is not initialized');
  }

  /**
   * getServices
   *
   * @return array<mixed>
   */
  private function getServices(): array
  {
    if ($this->container instanceof ContainerInterface)
    {
      $services = $this->container->getParameter('austral.tools.services');

      return is_array($services) ? $services : [];
    }
    throw new \RuntimeException('Container is not initialized');
  }

  /**
   * testCreateStatusByService
   *
   * @return void
   *
   * @throws \ReflectionException
   */
  public function testCreateStatusByService(): void
  {
    $sut = $this->createSut();
    $service = [
      'name' => 'queue:worker',
      'command' => 'php bin/console messenger:consume',
      'status' => 'run',
    ];

    // Access protected method via Reflection
    $ref = new \ReflectionClass($sut);
    $method = $ref->getMethod('createStatusByService');
    $result = $method->invoke($sut, $service, 'run');

    $this->assertIsArray($result);
    $this->assertSame('queue:worker', $result['name']);
    $this->assertSame('php bin/console messenger:consume', $result['command']);
    $this->assertSame('run', $result['status']);
  }

  /**
   * When forcing status in write(), shell checks should be bypassed and no exception thrown.
   * We also verify services array is still well-formed after write.
   *
   * @throws \ReflectionException
   */
  public function testWriteWithForceStatusDoesNotExecuteShellAndReturnsSelf(): void
  {
    $sut = $this->createSut();

    // Call write() with a forced status to avoid any Process/ps invocation
    $result = $sut->write('run');
    $this->assertSame($sut, $result, 'write() should be fluent (return $this).');
    $readStatus = $sut->read();

    $services = $this->getServices();
    $this->assertCount(count($services), $readStatus);

    foreach ($services as $serviceValues)
    {
      $this->assertIsArray($readStatus[$serviceValues['command']]);
      foreach ($serviceValues as $key => $value)
      {
        if ('status' === $key)
        {
          $this->assertSame('run', $readStatus[$serviceValues['command']][$key]);
        }
        else
        {
          $this->assertSame($value, $readStatus[$serviceValues['command']][$key]);
        }
      }
    }
  }

  /**
   * If createDefaultServicesStatus() exists and is protected, we can sanity-check its shape.
   * This test is optional—uncomment if present in your class.
   */
  public function testCreateDefaultServicesStatusShapeIfPresent(): void
  {
    $sut = $this->createSut();
    $result = $sut->write('run');
    $ref = new \ReflectionClass($sut);

    if ($ref->hasMethod('createDefaultServicesStatus'))
    {
      $m = $ref->getMethod('createDefaultServicesStatus');
      $default = $m->invoke($sut);

      $this->assertIsArray($default);
      // Do not assume a fixed structure—just basic sanity checks

      $this->assertArrayHasKey('update', $default);
      $this->assertArrayHasKey('refresh_delay', $default);
      $this->assertArrayHasKey('services', $default);
      $this->assertIsArray($default['services']);
    }
    else
    {
      $this->markTestSkipped('createDefaultServicesStatus() not present in class.');
    }
  }
}
