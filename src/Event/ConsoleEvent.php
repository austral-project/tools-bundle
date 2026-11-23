<?php

/*
 * This file is part of the Austral Tools Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\ToolsBundle\Event;

class ConsoleEvent
{
  public const string EVENT_CONSOLE_EXECUTE_BEFORE = 'austral.event.console.execute.before';
  public const string EVENT_CONSOLE_EXECUTE_AFTER = 'austral.event.console.execute.after';
}
