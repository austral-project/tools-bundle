<?php

/*
 * This file is part of the Austral Tools Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\ToolsBundle\Command\Base;

use Austral\Tools\Event\ConsoleEvent;
use Austral\ToolsBundle\Traits;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Austral Abstract Command.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 *
 * @abstract
 */
abstract class Command extends BaseCommand
{
  use Traits\IoTrait;

  /**
   * @var ContainerInterface|null
   */
  protected ?ContainerInterface $container = null;

  /**
   * @var EventDispatcherInterface|null
   */
  protected ?EventDispatcherInterface $dispatcher = null;

  /**
   * @var string
   */
  protected string $titleCommande = '';

  /**
   * @var bool
   */
  protected bool $initEntityMapping = true;

  /**
   * @var float
   */
  protected float $timeStartScript = 0.0;

  /**
   * @var Filesystem
   */
  protected Filesystem $filesystem;

  /**
   * @var Application|null
   */
  protected ?Application $application = null;

  /**
   * Command constructor
   *
   * @param string|null              $name
   * @param (callable(): mixed)|null $code
   */
  public function __construct(?string $name = null, ?callable $code = null)
  {
    parent::__construct($name, $code);
    $this->filesystem = new Filesystem();
  }

  /**
   * @param ContainerInterface $container
   *
   * @return void
   */
  public function setContainer(ContainerInterface $container): void
  {
    $this->container = $container;
    $dispatcher = $this->container->get('event_dispatcher');
    if (!$dispatcher instanceof EventDispatcherInterface)
    {
      throw new \LogicException(sprintf("Service 'event_dispatcher' must implement %s", EventDispatcherInterface::class));
    }
    $this->dispatcher = $dispatcher;
  }

  /**
   * @param InputInterface  $input
   * @param OutputInterface $output
   *
   * @return $this
   */
  protected function init(InputInterface $input, OutputInterface $output): Command
  {
    $this->application = $this->getApplication();
    if (null === $this->application)
    {
      throw new \LogicException('The container cannot be retrieved as the application instance is not yet set.');
    }
    $this->setIo(new SymfonyStyle($input, $output));

    return $this;
  }

  /**
   * @return $this
   */
  protected function start(): Command
  {
    if ($this->titleCommande)
    {
      $this->viewMessage($this->titleCommande, self::MESSAGE_TYPE_TITLE);
    }

    return $this;
  }

  /**
   * @return $this
   */
  protected function stop(): Command
  {
    $time = (int) microtime(true);
    $date = date('d/m/Y H:i:s', $time);
    $messageEnd = sprintf('Script end : %s -> %s', $date, $this->humanizeTimes($this->timeStartScript, $time));
    $this->viewMessage($messageEnd, 'success');

    return $this;
  }

  /**
   * @param float $timesStart
   * @param float $timesEnd
   *
   * @return string
   */
  protected function humanizeTimes(float $timesStart, float $timesEnd): string
  {
    $time = $timesEnd - $timesStart;
    $hours = (int) ($time / 60 / 60);
    $minutes = (int) ($time / 60) - $hours * 60;
    $seconds = (int) $time - $hours * 60 * 60 - $minutes * 60;

    return sprintf('%s hour(s), %s minute(s) et %s second(s)', $hours, $minutes, $seconds);
  }

  /**
   * @param InputInterface  $input
   * @param OutputInterface $output
   *
   * @return int
   */
  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    $this->timeStartScript = microtime(true);
    $this->init($input, $output)->start();
    try
    {
      $consoleEvent = new ConsoleEvent();
      $this->dispatcher?->dispatch($consoleEvent, ConsoleEvent::EVENT_CONSOLE_EXECUTE_BEFORE);
      $this->executeCommand($input, $output);
      $this->dispatcher?->dispatch($consoleEvent, ConsoleEvent::EVENT_CONSOLE_EXECUTE_AFTER);
      $this->stop();

      return self::SUCCESS;
    }
    catch (\Exception $e)
    {
      if ($this->dispatcher)
      {
        $errorConsole = new ConsoleErrorEvent($input, $output, $e, $this);
        $this->dispatcher->dispatch($errorConsole, 'console.error');
      }
      $this->viewMessage($e->getMessage(), 'error');
      $this->executeStopCommand();
      $this->stop();

      return self::FAILURE;
    }
  }

  /**
   * @return array<int>
   */
  public function getSubscribedSignals(): array
  {
    // The pcntl extension (and its SIGINT / SIGTERM constants) is optional
    return \defined('SIGINT') ? [\constant('SIGINT'), \constant('SIGTERM')] : [];
  }

  /**
   * @param int       $signal
   * @param int|false $previousExitCode
   *
   * @return false|int
   */
  public function handleSignal(int $signal, int|false $previousExitCode = 0): false|int
  {
    $this->stopCommand();

    return parent::handleSignal($signal, $previousExitCode);
  }

  /**
   * @var bool
   */
  protected bool $commandeStop = false;

  /**
   * @return $this
   */
  public function stopCommand(): Command
  {
    $this->commandeStop = true;
    $this->executeStopCommand();

    return $this;
  }

  /**
   * executeStopCommand
   *
   * @return void
   */
  protected function executeStopCommand(): void
  {
  }

  /**
   * @param InputInterface  $input
   * @param OutputInterface $output
   *
   * @return void
   */
  abstract protected function executeCommand(InputInterface $input, OutputInterface $output): void;
}
