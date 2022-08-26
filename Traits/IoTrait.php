<?php
/*
 * This file is part of the Austral Tools Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Austral\ToolsBundle\Traits;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Austral Io Trait.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
trait IoTrait
{

  /**
   * @var SymfonyStyle|null
   */
  protected ?SymfonyStyle $io = null;

  /**
   * @var OutputInterface|null
   */
  protected ?OutputInterface $output = null;

  /**
   * @param SymfonyStyle $io
   *
   * @return $this
   */
  public function setIo(SymfonyStyle $io)
  {
    $this->io = $io;
    return $this;
  }

  /**
   * @param OutputInterface $output
   *
   * @return $this
   */
  public function setOutput(OutputInterface $output)
  {
    $this->output = $output;
    return $this;
  }

  /**
   * @param $message
   * @param string|null $type
   * @param bool $viewMessage
   *
   * @return $this
   */
  protected function viewMessage($message, ?string $type = "section", bool $viewMessage = true)
  {
    if($this->io && $viewMessage)
    {
      switch($type)
      {
        case "section" :
          $this->io->section($message);
        break;
        case "title" :
          $this->io->title($message);
        break;
        case "text" :
          $this->io->text($message);
        break;
        case "note" :
          $this->io->note($message);
        break;
        case "comment" :
          $this->io->comment($message);
        break;
        case "caution" :
          $this->io->caution($message);
        break;
        case "error" :
          $this->io->error($message);
        break;
        case "success" :
          $this->io->success($message);
        break;
      }
    }
    return $this;
  }

  /**
   * @param int $count
   *
   * @return $this
   */
  protected function newLine(int $count = 1)
  {
    if($this->io) {
      $this->io->newLine($count);
    }
    return $this;
  }


  /**
   * @var array
   */
  protected array $progressBars = array();

  /**
   * @param string $name
   *
   * @return ProgressBar|null
   */
  public function getProgressBar(string $name = "default"): ?ProgressBar
  {
    if(array_key_exists($name, $this->progressBars))
    {
      return $this->progressBars[$name];
    }
    return null;
  }

  /**
   * @param string $name
   * @param int $progressBarValue
   * @param string $format
   *
   * @return $this
   */
  protected function progressStart(string $name = "default", int $progressBarValue = 100, string $format = " %current%/%max% -- %message%")
  {
    if($this->output) {
      ProgressBar::setFormatDefinition('austral', $format);
      $this->progressBars[$name] = new ProgressBar($this->output, $progressBarValue);
      $this->progressBars[$name]->setFormat('austral');
    }
    return $this;
  }

  /**
   * @param string $name
   * @param int $step
   * @param string|null $message
   *
   * @return $this
   */
  protected function progressAdvance(string $name = "default", int $step = 1, ?string $message = null)
  {
    /** @var ProgressBar $progressBar */
    if($progressBar = $this->getProgressBar($name))
    {
      if($message) {
        $progressBar->setMessage($message);
      }
      $progressBar->advance($step);
    }
    return $this;
  }

  /**
   * @param string $name
   *
   * @return $this
   */
  protected function progressFinish(string $name = "default")
  {
    /** @var ProgressBar $progressBar */
    if($progressBar = $this->getProgressBar($name))
    {
      $progressBar->finish();
    }
    $this->newLine(2);
    return $this;
  }

}