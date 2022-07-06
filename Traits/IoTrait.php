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

}