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

  const MESSAGE_TYPE_SECTION = "section";
  const MESSAGE_TYPE_TITLE = "title";
  const MESSAGE_TYPE_TEXT = "text";
  const MESSAGE_TYPE_NOTE = "note";
  const MESSAGE_TYPE_COMMENT = "comment";
  const MESSAGE_TYPE_CAUTION = "caution";
  const MESSAGE_TYPE_ERROR = "error";
  const MESSAGE_TYPE_SUCCESS = "success";


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
  public function setIo(SymfonyStyle $io): static
  {
    $this->io = $io;
    return $this;
  }

  /**
   * @param OutputInterface $output
   *
   * @return $this
   */
  public function setOutput(OutputInterface $output): static
  {
    $this->output = $output;
    return $this;
  }

  /**
   * @param string $message
   * @param string|null $type
   * @param bool $viewMessage
   *
   * @return $this
   */
  protected function viewMessage(string $message, ?string $type = self::MESSAGE_TYPE_SECTION, bool $viewMessage = true): static
  {
    if($this->io && $viewMessage)
    {
      switch($type)
      {
        case self::MESSAGE_TYPE_SECTION:
          $this->io->section($message);
        break;
        case self::MESSAGE_TYPE_TITLE:
          $this->io->title($message);
        break;
        case self::MESSAGE_TYPE_TEXT:
          $this->io->text($message);
        break;
        case self::MESSAGE_TYPE_NOTE:
          $this->io->note($message);
        break;
        case  self::MESSAGE_TYPE_COMMENT:
          $this->io->comment($message);
        break;
        case  self::MESSAGE_TYPE_CAUTION:
          $this->io->caution($message);
        break;
        case  self::MESSAGE_TYPE_ERROR:
          $this->io->error($message);
        break;
        case  self::MESSAGE_TYPE_SUCCESS:
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
  protected function newLine(int $count = 1): static
  {
    $this->io?->newLine($count);
    return $this;
  }


  /**
   * @var array<string, ProgressBar>
   */
  protected array $progressBars = [];

  /**
   * @param string $name
   *
   * @return ProgressBar|null
   */
  public function getProgressBar(string $name = "default"): ?ProgressBar
  {
    return array_key_exists($name, $this->progressBars) ? $this->progressBars[$name] : null;
  }

  /**
   * @param string $name
   * @param int $progressBarValue
   * @param string $format
   *
   * @return $this
   */
  protected function progressStart(string $name = "default", int $progressBarValue = 100, string $format = " %current%/%max% -- %message%"): static
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
  protected function progressAdvance(string $name = "default", int $step = 1, ?string $message = null): static
  {
    if(($progressBar = $this->getProgressBar($name)) instanceof ProgressBar)
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
  protected function progressFinish(string $name = "default"): static
  {
    if(($progressBar = $this->getProgressBar($name)) instanceof ProgressBar)
    {
      $progressBar->finish();
    }
    $this->newLine(2);
    return $this;
  }

}