<?php
/*
 * This file is part of the Austral Tools Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
 
namespace Austral\ToolsBundle\Configuration;

use Austral\ToolsBundle\AustralTools;

/**
 * Austral Configuration Abstract.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @abstract
 */
abstract Class BaseConfiguration implements ConfigurationInterface
{
  /**
   * @var array<mixed>
   */
  protected array $config;
  /**
   * @var array<mixed>
   */
  protected array $flatten;

  /**
   * @var string|null
   */
  protected ?string $prefix = null;

  /**
   * @var int|null
   */
  protected ?int $niveauMax = 0;

  /**
   * Initialize the service
   *
   * @param array<mixed> $config
   */
  public function __construct(array $config = [])
  {
    $this->config = $config;
    $this->flatten = $this->init(".", $config, "{$this->prefix}.");
  }

  /**
   * @param string $delimiter
   * @param array<mixed> $items
   * @param string $prepend
   * @param int $niveau
   *
   * @return array<mixed>
   */
  protected function init(string $delimiter = '.', array $items = [], string $prepend = '', int $niveau = 1): array
  {
    $flatten = [];
    foreach ($items as $key => $value) {
      if(is_numeric($key))
      {
        $flatten[trim($prepend, ".")][$key] = $value;
      }
      else
      {
        if (is_array($value) && !empty($value) && ($this->niveauMax === null || $niveau < $this->niveauMax)) {
          $flatten = array_merge(
            $flatten,
            $this->init($delimiter, $value, $prepend.$key.$delimiter, $niveau+1)
          );
        } else {
          $flatten[$prepend.$key] = $value;
        }
      }
    }
    return $flatten;
  }

  /**
   * @return array<mixed>
   */
  public function all(): array
  {
    return $this->flatten;
  }

  /**
   * @return array<mixed>
   */
  public function allConfig(): array
  {
    return $this->config;
  }

  /**
   * @param string $key
   * @param null $default
   *
   * @return mixed
   */
  public function getConfig(string $key, $default = null): mixed
  {
    return AustralTools::valueByKey($this->config, $key, $default);
  }

  /**
   * @param string $key
   * @param mixed|null $default
   *
   * @return mixed
   */
  public function get(string $key, mixed $default = null): mixed
  {
    return AustralTools::valueByKey($this->flatten, sprintf("%s.%s", $this->prefix, $key), $default);
  }

}