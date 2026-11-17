<?php
/*
 * This file is part of the Austral Tools Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\Tools\Event;

class ConsoleEvent
{
  const EVENT_CONSOLE_EXECUTE_BEFORE = "austral.event.console.execute.before";
  const EVENT_CONSOLE_EXECUTE_AFTER = "austral.event.console.execute.after";
}