<?php

/*
 * This file is part of the Austral Tools Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\ToolsBundle\Tests\Command;

use Austral\ToolsBundle\Command\Base\Command;
use Austral\ToolsBundle\Event\ConsoleEvent;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class BaseCommandTest extends KernelTestCase
{
  public function testCommandGetsTheContainerAndDispatchesTheConsoleEvents(): void
  {
    $kernel = self::bootKernel();

    $command = new class('austral:test:base') extends Command {
      public ?ContainerInterface $containerUsed = null;

      protected function executeCommand(InputInterface $input, OutputInterface $output): void
      {
        $this->containerUsed = $this->container;
      }
    };
    $command->setApplication(new Application($kernel));

    $dispatcher = $kernel->getContainer()->get('event_dispatcher');
    self::assertInstanceOf(EventDispatcherInterface::class, $dispatcher);
    $events = [];
    $dispatcher->addListener(ConsoleEvent::EVENT_CONSOLE_EXECUTE_BEFORE, function () use (&$events): void {
      $events[] = 'before';
    });
    $dispatcher->addListener(ConsoleEvent::EVENT_CONSOLE_EXECUTE_AFTER, function () use (&$events): void {
      $events[] = 'after';
    });

    $tester = new CommandTester($command);
    $tester->execute([]);

    self::assertSame(Command::SUCCESS, $tester->getStatusCode(), $tester->getDisplay());
    self::assertNotNull($command->containerUsed, 'The command must get the container of the kernel.');
    self::assertSame(['before', 'after'], $events);
  }
}
