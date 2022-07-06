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
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Process\Process;

/**
 * Austral Services status checker.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
Class ServicesStatusChecker
{

  /**
   * @var ContainerInterface
   */
  protected ContainerInterface $container;

  /**
   * @var string
   */
  protected string $pathFileStatus;

  /**
   * @var int
   */
  protected int $sleep = 5;

  /**
   * @var array
   */
  protected array $services;

  /**
   * Debug constructor.
   */
  public function __construct(ContainerInterface $container)
  {
    $this->container = $container;
    $this->pathFileStatus = AustralTools::join(
      $this->container->getParameter("kernel.project_dir"),
      "var",
      "services.status"
    );
    $this->services = $this->container->getParameter("austral.tools.services");
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
  public function write(string $forceStatus = null): ServicesStatusChecker
  {
    $servicesStatusChecker = $this->createDefaultServicesStatus();
    foreach($this->services as $key => $service)
    {
      if($forceStatus) {
        $status = $forceStatus;
      }
      else {
        $status = "stop";
        $command = "ps aux | grep -e \"{$service['command']}\" | grep -v grep | awk '{ print $2 }'";
        $process = Process::fromShellCommandline($command);
        $process->run(function ($type, $buffer) use(&$status) {
          if (Process::ERR === $type) {
            $status = "error";
          } else {
            $status = "run";
          }
        });
        $servicesStatusChecker["services"][$key] = $this->createStatusByService($service, $status);
      }

    }
    $this->save($servicesStatusChecker);
    return $this;
  }

  /**
   * @return array
   */
  protected function createDefaultServicesStatus(): array
  {
    $servicesStatus = array();
    $servicesStatus["update"] = date("Y-m-d H:i:s");
    $servicesStatus['refresh_delay'] = $this->sleep;
    $servicesStatus['services'] = array();
    return $servicesStatus;
  }

  /**
   * @return array
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
   * @throws \Exception
   */
  public function getServiceIsRunByCommand(string $command): bool
  {
    $service = AustralTools::getValueByKey($this->getServicesByStatusFinal(), $command, array());
    return AustralTools::getValueByKey($service, "status", "stop") === "run";
  }

  /**
   * @return array
   * @throws \Exception
   */
  protected function getServicesByStatusFinal(): array
  {
    $servicesByStatus = $this->getFileContent();
    $refreshDelay = AustralTools::getValueByKey($servicesByStatus, "refresh_delay", $this->sleep);

    $commandStatus = "stop";
    if($updates = AustralTools::getValueByKey($servicesByStatus, "update"))
    {
      $updatesDate = new \DateTime($updates);
      $dateNow = new \DateTime();
      $dateNow->sub(new \DateInterval('PT'.($refreshDelay*2).'S'));
      if($updatesDate->getTimestamp() >= $dateNow->getTimestamp())
      {
        $commandStatus = "run";
      }
    }

    $servicesByStatusFinal = array();
    foreach($this->services as $key => $service)
    {
      $statusFinal = "stop";
      if($serviceByStatus = AustralTools::getValueByKey(AustralTools::getValueByKey($servicesByStatus, 'services', array()), $key, array()))
      {
        if($commandStatus == "run") {
          $statusFinal = $serviceByStatus["status"];
        }
      }
      $servicesByStatusFinal[$service["command"]] = $this->createStatusByService($service, $statusFinal);
    }
    return $servicesByStatusFinal;
  }

  /**
   * @return array|mixed
   */
  protected function getFileContent()
  {
    if(file_exists($this->pathFileStatus))
    {
      $fileContent = file_get_contents($this->pathFileStatus);
      return json_decode($fileContent, true);
    }
    return array();
  }

  /**
   * @param array $servicesStatus
   *
   * @return void
   */
  protected function save(array $servicesStatus)
  {
    file_put_contents($this->pathFileStatus, json_encode($servicesStatus));
  }

  /**
   * @param array $service
   * @param $status
   *
   * @return array
   */
  protected function createStatusByService(array $service, $status): array
  {
    $serviceStatus = $service;
    $serviceStatus["status"] = $status;
    return $serviceStatus;
  }

}