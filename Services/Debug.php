<?php
/*
 * This file is part of the Austral Tools Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\ToolsBundle\Services;

use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Stopwatch\StopwatchEvent;

/**
 * Austral Debug Service.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
Class Debug
{

  /**
   * @var Stopwatch|null
   */
  protected ?Stopwatch $stopWatch = null;

  /**
   * Debug constructor.
   */
  public function __construct()
  {
  }

  /**
   * @param Stopwatch|null $stopWatch
   */
  public function setDebugStopWatch(?Stopwatch $stopWatch = null)
  {
    $this->stopWatch = $stopWatch;
  }

  /**
   * @param string $name
   * @param string|null $container
   *
   * @return $this
   */
  public function stopWatchStart(string $name, string $container = null): Debug
  {
    if($this->stopWatch)
    {
      $this->stopWatch->start($name, $container);
    }
    return $this;
  }

  /**
   * @param string $name
   *
   * @return $this
   */
  public function stopWatchLap(string $name): Debug
  {
    if($this->stopWatch && $this->stopWatch->isStarted($name))
    {
      $this->stopWatch->lap($name);
    }
    return $this;
  }

  /**
   * @param string $name
   *
   * @return StopwatchEvent
   */
  public function stopWatchStop(string $name): ?StopwatchEvent
  {
    if($this->stopWatch && $this->stopWatch->isStarted($name))
    {
      return $this->stopWatch->stop($name);
    }
    return null;
  }

}