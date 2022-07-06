<?php
/*
 * This file is part of the Austral Tools Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
 
namespace Austral\ToolsBundle\TwigExtension;

use Austral\ToolsBundle\AustralTools;

use Ramsey\Uuid\Uuid;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

/**
 * Austral Tools Twig Extension.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class ToolsTwig extends AbstractExtension
{

  /**
   * @return TwigFilter[]
   */
  public function getFilters(): array
  {
    return [
      new TwigFilter('tools_dump', [$this, 'dump']),
      new TwigFilter('tools_dump_force', [$this, 'dumpForce']),
      new TwigFilter('tools_dump_kill', [$this, 'dumpKill']),
      new TwigFilter('value_by_key', [$this, 'valueByKey']),
      new TwigFilter('unset_in_array', [$this, 'unsetInArray']),
      new TwigFilter('ksort', [$this, 'ksort']),
      new TwigFilter('twig_filter_exists', [$this, 'twigFilterExists'], ['needs_environment' => true]),

      new TwigFilter('file_exist', [$this, 'fileExist']),
      new TwigFilter('file_size', [$this, 'fileSize']),
      new TwigFilter('file_mime_type', [$this, 'fileMimeType']),
      new TwigFilter('file_is_image', [$this, 'isImage']),
      new TwigFilter('file_image_size', [$this, 'imageSize']),
    ];
  }

  /**
   * @return TwigTest[]
   */
  public function getTests(): array
  {
    return [
      new TwigTest('instanceof', array($this, 'isInstanceOf')),
      new TwigTest('filter_exists', array($this, 'twigFilterExists'), ['needs_environment' => true]),
    ];
  }

  /**
   * @return TwigFunction[]
   */
  public function getFunctions(): array
  {
    return array(
      "tools_dump"              => new TwigFunction("tools_dump", array($this, "dump")),
      "tools_dump_force"        => new TwigFunction("tools_dump_force", array($this, "dumpForce")),
      "tools_dump_kill"         => new TwigFunction("tools_dump_kill", array($this, "dumpKill")),
      "is_dev"                  => new TwigFunction("tools_is_dev", array($this, "isDev")),
      "value_by_key"            => new TwigFunction("value_by_key", array($this, "valueByKey")),
      "unset_in_array"          => new TwigFunction("unset_in_array", array($this, "unsetInArray")),
      "ksort"                   => new TwigFunction("ksort", array($this, "ksort")),
      "uuid"                    => new TwigFunction("uuid", array($this, "uuid")),
      "add_in_array"            => new TwigFunction("add_in_array", array($this, "addInArray")),
      "twig_filter_exists"      => new TwigFunction('twig_filter_exists', array($this, 'twigFilterExists'), ['needs_environment' => true]),

      "file_exist"              => new TwigFunction("file_exist", array($this, "fileExist")),
      "file_size"               => new TwigFunction("file_size", array($this, "fileSize")),
      "file_mime_type"          => new TwigFunction("file_mime_type", array($this, "fileMimeType")),
      "file_is_image"           => new TwigFunction("file_is_image", array($this, "isImage")),
      "file_image_size"         => new TwigFunction("file_image_size", array($this, "imageSize")),
    );
  }

  /**
   */
  public function dump()
  {
    $_SERVER['APP_DEBUG_TRACE_NUMBER'] = 3;
    AustralTools::dump(func_get_args());
    $_SERVER['APP_DEBUG_TRACE_NUMBER'] = 1;
  }

  /**
   * @return bool
   */
  public function isDev(): bool
  {
    return AustralTools::isAppDebug();
  }

  /**
   * @return void
   */
  public function dumpForce()
  {
    AustralTools::dumpForce(func_get_args());
  }

  /**
   * @return void
   */
  public function dumpKill()
  {
    AustralTools::dumpKill(func_get_args());
  }

  /**
   * @param $array
   * @param string $key
   * @param null $default
   *
   * @return mixed|string|string[]
   */
  public function valueByKey($array, string $key, $default = null)
  {
    return AustralTools::getValueByKey($array, $key, $default);
  }

  /**
   * @return string
   * @throws \Exception
   */
  public function uuid(): string
  {
    return Uuid::uuid4()->toString();
  }

  /**
   * @param array $array
   * @param array $keys
   *
   * @return array
   */
  public function unsetInArray(array $array, array $keys): array
  {
    return AustralTools::unsetInArray($array, $keys);
  }

  /**
   * @param array $array
   * @param null $key
   * @param null $values
   *
   * @return array
   */
  public function addInArray(array $array, $key = null, $values = null): array
  {
    $array[$key] = $values;
    return $array;
  }

  /**
   * @param array $array
   *
   * @return array
   */
  public function ksort(array $array): array
  {
    ksort($array);
    return $array;
  }

  /**
   * @param Environment $twig
   * @param $name
   *
   * @return bool
   */
  public function twigFilterExists(Environment $twig, $name): bool
  {
    return (bool)$twig->getFilter($name);
  }

  /**
   * @param $var
   * @param $instance
   *
   * @return bool
   */
  public function isInstanceOf($var, $instance): bool
  {
    $reflexionClass = new \ReflectionClass($instance);
    return $reflexionClass->isInstance($var);
  }

  /**
   * @param string $filePath
   *
   * @return bool
   */
  public function fileExist(string $filePath): bool
  {
    return file_exists($filePath) && is_file($filePath);
  }

  /**
   * @param string $filePath
   * @param bool $humanize
   *
   * @return false|int|string
   */
  public function fileSize(string $filePath, bool $humanize = false)
  {
    if($this->fileExist($filePath))
    {
      return $humanize ? AustralTools::humanizeSize($filePath) : filesize($filePath);
    }
    return 0;
  }

  /**
   * @param string $filePath
   *
   * @return string|null
   */
  public function fileMimeType(string $filePath): ?string
  {
    return AustralTools::mimeType($filePath);
  }

  /**
   * @param string $filePath
   *
   * @return bool
   */
  public function isImage(string $filePath): bool
  {
    return AustralTools::isImage($filePath);
  }

  /**
   * @param string $filePath
   * @param bool $returnArray
   *
   * @return array|string
   */
  public function imageSize(string $filePath, bool $returnArray = true)
  {
    return AustralTools::imageDimension($filePath, $returnArray);
  }

}