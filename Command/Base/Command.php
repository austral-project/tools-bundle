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

use Austral\ToolsBundle\Command\Exception\CommandException;
use Austral\ToolsBundle\Traits as Traits;

use Exception;
use LogicException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Austral Abstract Command.
 * @author Matthieu Beurel <matthieu@austral.dev>
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
   * @var string
   */
  protected string $titleCommande = "";

  /**
   * @var integer
   */
  protected int $timeStartScript;

  /**
   * @var KernelInterface
   */
  protected KernelInterface $kernel;

  /**
   * @var Filesystem
   */
  protected Filesystem $filesystem;

  /**
   * @param InputInterface $input
   * @param OutputInterface $output
   *
   * @return $this
   */
  protected function init(InputInterface $input, OutputInterface $output): Command
  {
    $this->kernel = $this->getApplication()->getKernel();
    $this->container = $this->getContainer();
    $this->setIo(new SymfonyStyle($input, $output));
    $this->filesystem = new Filesystem();
    return $this;
  }

  /**
   * @return $this
   */
  protected function start(): Command
  {
    $this->io->title($this->titleCommande);
    return $this;
  }

  /**
   * @return $this
   */
  protected function stop(): Command
  {
    $timeend = microtime(true);
    $date = date('d/m/Y H:i:s', $timeend);
    $messageEnd = sprintf("Script end : %s -> %s", $date, $this->humanizeTimes($this->timeStartScript, $timeend));
    $this->viewMessage($messageEnd, "success");
    return $this;
  }

  /**
   * @param $timesStart
   * @param $timesEnd
   *
   * @return string
   */
  protected function humanizeTimes($timesStart, $timesEnd): string
  {
    $time = $timesEnd - $timesStart;
    $hours = (int) ($time / 60 / 60);
    $minutes = (int) ($time / 60) - $hours * 60;
    $seconds = (int) $time - $hours * 60 * 60 - $minutes * 60;
    return sprintf("%s hour(s), %s minute(s) et %s second(s)", $hours, $minutes, $seconds);
  }

  /**
   * @param InputInterface $input
   * @param OutputInterface $output
   *
   * @return int|null
   */
  protected function execute(InputInterface $input, OutputInterface $output): ?int
  {
    try {
      pcntl_signal(SIGTERM, [$this, 'stopCommand']);
      pcntl_signal(SIGINT, [$this, 'stopCommand']);
    }
    catch(Exception $e){
      $this->viewMessage($e->getMessage(), "error");
      $this->executeStopCommand();
    }
    $this->timeStartScript = microtime(true);
    $this->init($input, $output)->start();
    try {
      if($this->container->has("austral.entity.mapping.listener"))
      {
        $mappingListener = $this->container->get('austral.entity.mapping.listener');
        $mappingListener->initEntityAnnotations();
      }
      $this->executeCommand($input, $output);
    }
    catch(Exception $e) {
      $this->viewMessage($e->getMessage(), "error");
      $this->executeStopCommand();
    }
    $this->stop();
    return 0;
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

  protected function executeStopCommand() {}

  /**
   * @param InputInterface $input
   * @param OutputInterface $output
   */
  abstract protected function executeCommand(InputInterface $input, OutputInterface $output);

  /**
   * @return ContainerInterface
   *
   * @throws LogicException
   */
  protected function getContainer(): ContainerInterface
  {
    if (null === $this->container) {
      $application = $this->getApplication();
      if (null === $application) {
        throw new LogicException('The container cannot be retrieved as the application instance is not yet set.');
      }

      $this->container = $application->getKernel()->getContainer();
    }

    return $this->container;
  }

  /**
   * @param ContainerInterface $container
   *
   * @return void
   */
  public function setContainer(ContainerInterface $container)
  {
    $this->container = $container;
  }

}
