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
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\EventDispatcher\EventDispatcher;

final class BaseCommandTest extends TestCase
{
  public function testConsoleEventsAreDispatchedAroundTheCommand(): void
  {
    $events = [];
    $dispatcher = new EventDispatcher();
    $dispatcher->addListener(ConsoleEvent::EVENT_CONSOLE_EXECUTE_BEFORE, function () use (&$events): void {
      $events[] = 'before';
    });
    $dispatcher->addListener(ConsoleEvent::EVENT_CONSOLE_EXECUTE_AFTER, function () use (&$events): void {
      $events[] = 'after';
    });
    $command = self::command(function () use (&$events): void {
      $events[] = 'execute';
    });
    $command->setEventDispatcher($dispatcher);

    $tester = new CommandTester($command);
    $tester->execute([]);

    self::assertSame(Command::SUCCESS, $tester->getStatusCode(), $tester->getDisplay());
    self::assertSame(['before', 'execute', 'after'], $events);
  }

  public function testCommandRunsWithoutEventDispatcher(): void
  {
    $executed = false;
    $command = self::command(function () use (&$executed): void {
      $executed = true;
    });

    $tester = new CommandTester($command);
    $tester->execute([]);

    self::assertSame(Command::SUCCESS, $tester->getStatusCode(), $tester->getDisplay());
    self::assertTrue($executed);
  }

  public function testExceptionDispatchesConsoleErrorAndReturnsFailure(): void
  {
    $errors = [];
    $dispatcher = new EventDispatcher();
    $dispatcher->addListener(ConsoleEvent::EVENT_CONSOLE_EXECUTE_AFTER, function () use (&$errors): void {
      $errors[] = 'after';
    });
    $dispatcher->addListener('console.error', function (ConsoleErrorEvent $event) use (&$errors): void {
      $errors[] = $event->getError()->getMessage();
    });
    $command = self::command(function (): void {
      throw new \RuntimeException('Something went wrong');
    });
    $command->setEventDispatcher($dispatcher);

    $tester = new CommandTester($command);
    $tester->execute([]);

    self::assertSame(Command::FAILURE, $tester->getStatusCode());
    self::assertSame(['Something went wrong'], $errors, 'The "after" event must not be dispatched when the command fails.');
    self::assertStringContainsString('Something went wrong', $tester->getDisplay());
  }

  /**
   * @param \Closure(): void $execute
   */
  private static function command(\Closure $execute): Command
  {
    $command = new class('austral:test:base', $execute) extends Command {
      public function __construct(string $name, private readonly \Closure $execute)
      {
        parent::__construct($name);
      }

      protected function executeCommand(InputInterface $input, OutputInterface $output): void
      {
        ($this->execute)();
      }
    };
    $command->setApplication(new Application());

    return $command;
  }
}
