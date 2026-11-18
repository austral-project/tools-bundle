<?php

/*
 * This file is part of the Austral Tools Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\ToolsBundle\EventSubscriber;

use Austral\ToolsBundle\Event\ConsoleEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConsoleEventSubscriber implements EventSubscriberInterface
{
  /**
   * @return array<string, string|array{0: string, 1: int}|list<array{0: string, 1?: int}>>
   */
  public static function getSubscribedEvents(): array
  {
    return [
      ConsoleEvent::EVENT_CONSOLE_EXECUTE_BEFORE => ['before', 1024],
      ConsoleEvent::EVENT_CONSOLE_EXECUTE_AFTER => ['after', 1024],
    ];
  }

  public function before(ConsoleEvent $consoleEvent): void
  {
  }

  public function after(ConsoleEvent $consoleEvent): void
  {
  }
}
