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

use Austral\ToolsBundle\AustralTools;
use Symfony\Component\Process\Process;

/**
 * Austral Services status checker.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class ServicesStatusChecker
{
  /**
   * @var string
   */
  protected string $pathFileStatus;

  /**
   * @var int
   */
  protected int $sleep = 5;

  /**
   * ServicesStatusChecker constructor
   *
   * @param string               $projectDir
   * @param array<string, mixed> $services
   */
  public function __construct(
    string $projectDir,
    protected array $services
  ) {
    $this->pathFileStatus = AustralTools::join(
      $projectDir,
      'var',
      'services.status'
    );
  }

  /**
   * @return int
   */
  public function getSleep(): int
  {
    return $this->sleep;
  }

  /**
   * @param string|null $forceStatus
   *
   * @return $this
   */
  public function write(?string $forceStatus = null): static
  {
    $servicesStatusChecker = $this->createDefaultServicesStatus();
    foreach ($this->services as $key => $service)
    {
      if ($forceStatus)
      {
        $status = $forceStatus;
      }
      else
      {
        $status = 'stop';
        $command = "ps aux | grep -e \"{$service['command']}\" | grep -v grep | awk '{ print $2 }'";
        $process = Process::fromShellCommandline($command);
        $process->run(function ($type) use (&$status) {
          if (Process::ERR === $type)
          {
            $status = 'error';
          }
          else
          {
            $status = 'run';
          }
        });
      }
      $servicesStatusChecker['services'][$key] = $this->createStatusByService($service, $status);
    }
    $this->save($servicesStatusChecker);

    return $this;
  }

  /**
   * @return array<string, mixed>
   */
  protected function createDefaultServicesStatus(): array
  {
    $servicesStatus = [];
    $servicesStatus['update'] = date('Y-m-d H:i:s');
    $servicesStatus['refresh_delay'] = $this->sleep;
    $servicesStatus['services'] = [];

    return $servicesStatus;
  }

  /**
   * @return array<string, mixed>
   *
   * @throws \Exception
   */
  public function read(): array
  {
    return $this->getServicesByStatusFinal();
  }

  /**
   * @param string $command
   *
   * @return bool
   *
   * @throws \Exception
   */
  public function getServiceIsRunByCommand(string $command): bool
  {
    $service = AustralTools::valueByKey($this->getServicesByStatusFinal(), $command, []);

    return 'run' === AustralTools::valueByKey($service, 'status', 'stop');
  }

  /**
   * @return array<string, mixed>
   *
   * @throws \Exception
   */
  protected function getServicesByStatusFinal(): array
  {
    $servicesByStatus = $this->getFileContent();
    $refreshDelay = AustralTools::valueByKey($servicesByStatus, 'refresh_delay', $this->sleep);

    $commandStatus = 'stop';
    if ($updates = AustralTools::valueByKey($servicesByStatus, 'update'))
    {
      $updatesDate = new \DateTime($updates);
      $dateNow = new \DateTime();
      $dateNow->sub(new \DateInterval('PT'.($refreshDelay * 2).'S'));
      if ($updatesDate->getTimestamp() >= $dateNow->getTimestamp())
      {
        $commandStatus = 'run';
      }
    }

    $servicesByStatusFinal = [];
    foreach ($this->services as $key => $service)
    {
      $statusFinal = 'stop';
      if ($serviceByStatus = AustralTools::valueByKey(AustralTools::valueByKey($servicesByStatus, 'services', []), $key, []))
      {
        if ('run' == $commandStatus)
        {
          $statusFinal = $serviceByStatus['status'];
        }
      }
      $servicesByStatusFinal[$service['command']] = $this->createStatusByService($service, $statusFinal);
    }

    return $servicesByStatusFinal;
  }

  /**
   * @return array<string, mixed>
   */
  protected function getFileContent(): array
  {
    if (file_exists($this->pathFileStatus))
    {
      $fileContent = file_get_contents($this->pathFileStatus);
      $jsonEncode = json_decode(is_string($fileContent) ? $fileContent : '{}', true);

      return is_array($jsonEncode) ? $jsonEncode : [];
    }

    return [];
  }

  /**
   * @param array<string, mixed> $servicesStatus
   *
   * @return $this
   */
  protected function save(array $servicesStatus): static
  {
    $directory = \dirname($this->pathFileStatus);
    if (!is_dir($directory) && !mkdir($directory, 0o775, true) && !is_dir($directory))
    {
      throw new \RuntimeException(sprintf('Unable to create the directory "%s".', $directory));
    }
    file_put_contents($this->pathFileStatus, json_encode($servicesStatus));

    return $this;
  }

  /**
   * @param array<string, mixed> $service
   * @param string               $status
   *
   * @return array<string, mixed>
   */
  protected function createStatusByService(array $service, string $status): array
  {
    $serviceStatus = $service;
    $serviceStatus['status'] = $status;

    return $serviceStatus;
  }
}
