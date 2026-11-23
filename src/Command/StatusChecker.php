<?php

/*
 * This file is part of the Austral Tools Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\ToolsBundle\Command;

use Austral\ToolsBundle\Command\Base\Command;
use Austral\ToolsBundle\Services\ServicesStatusChecker;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Austral Services status checker Command.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 *
 * @final
 */
class StatusChecker extends Command
{
  /**
   * @var string
   */
  protected string $titleCommande = 'Services Status checker';

  /**
   * {@inheritdoc}
   */
  protected function configure(): void
  {
    $this
      ->setDefinition([
        new InputOption('--service', '-s', InputOption::VALUE_NONE, 'Send service command'),
        new InputOption('--sleep', '', InputOption::VALUE_OPTIONAL, 'Sleep reload command'),
      ])
      ->setDescription($this->titleCommande)
      ->setHelp(
        <<<'EOF'
            The <info>%command.name%</info> conflict detect user

            <info>php %command.full_name%</info>
            <info>php %command.full_name% --service</info>
            <info>php %command.full_name% -s</info>
          EOF
      )
    ;
  }

  /**
   * StatusChecker constructor
   *
   * @param ServicesStatusChecker|null $servicesStatusChecker
   * @param string|null                $name
   * @param callable|null              $code
   */
  public function __construct(
    protected ?ServicesStatusChecker $servicesStatusChecker = null,
    ?string $name = null,
    ?callable $code = null
  ) {
    parent::__construct($name, $code);
  }

  /**
   * @param InputInterface  $input
   * @param OutputInterface $output
   */
  protected function executeCommand(InputInterface $input, OutputInterface $output): void
  {
    if (!$this->servicesStatusChecker instanceof ServicesStatusChecker)
    {
      throw new \LogicException(sprintf("Service 'austral.tools.status-checker' must implement %s", ServicesStatusChecker::class));
    }
    if ($input->getOption('service'))
    {
      $sleep = $input->getOption('sleep') ?: $this->servicesStatusChecker->getSleep();
      while (!$this->commandeStop)
      {
        $this->servicesStatusChecker->write();
        sleep($sleep * 1);
      }
    }
    else
    {
      $this->servicesStatusChecker->write();
    }
  }

  /**
   * @return void
   */
  protected function executeStopCommand(): void
  {
    $this->servicesStatusChecker?->write('stop');
  }
}
