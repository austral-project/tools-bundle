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
 * Austral Parameters Absract.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @abstract
 */
abstract Class BaseConfiguration implements ConfigurationInterface
{
  /**
   * @var array
   */
  protected array $config;
  /**
   * @var array
   */
  protected array $flatten;

  /**
   * @var string|null
   */
  protected ?string $prefix;

  /**
   * @var int|null
   */
  protected ?int $niveauMax = 0;

  /**
   * Initialize the service
   *
   * @param array $config Config
   */
  public function __construct(array $config = array())
  {
    $this->config = $config;
    $this->flatten = $this->init(".", $config, "{$this->prefix}.");
  }

  /**
   * @param string $delimiter
   * @param null $items
   * @param string|null $prepend
   * @param int $niveau
   *
   * @return array
   */
  protected function init(string $delimiter = '.', $items = null, ?string $prepend = '', int $niveau = 1): array
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
   * @return array
   */
  public function all(): array
  {
    return $this->flatten;
  }

  /**
   * @return array
   */
  public function allConfig(): array
  {
    return $this->config;
  }

  /**
   * @param string $key
   * @param null $default
   *
   * @return array|mixed|string|null
   */
  public function getConfig(string $key, $default = null)
  {
    return AustralTools::getValueByKey($this->config, $key, $default);
  }

  /**
   * @param string $key
   * @param null $default
   *
   * @return array|string
   */
  public function get(string $key, $default = null)
  {
    return AustralTools::getValueByKey($this->flatten, sprintf("%s.%s", $this->prefix, $key), $default);
  }

}