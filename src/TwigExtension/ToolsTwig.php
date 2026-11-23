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
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 *
 * @final
 */
class ToolsTwig extends AbstractExtension
{
  /**
   * Initialize
   *
   * @param string $kernelProjectDir
   */
  public function __construct(protected string $kernelProjectDir)
  {
  }

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
      new TwigFilter('array_to_string', [$this, 'arrayToString']),
      new TwigFilter('unset_in_array', [$this, 'unsetInArray']),
      new TwigFilter('add_in_array', [$this, 'addInArray']),
      new TwigFilter('ksort', [$this, 'ksort']),

      new TwigFilter('twig_filter_exists', [$this, 'twigFilterExists'], ['needs_environment' => true]),
      new TwigFilter('remove_multi_br', [$this, 'removeMultiBr']),

      new TwigFilter('asset_exist', [$this, 'assetExist']),
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
      new TwigTest('instanceof', [$this, 'isInstanceOf']),
      new TwigTest('filter_exists', [$this, 'twigFilterExists'], ['needs_environment' => true]),
    ];
  }

  /**
   * @return TwigFunction[]
   */
  public function getFunctions(): array
  {
    return [
      'tools_dump' => new TwigFunction('tools_dump', [$this, 'dump']),
      'tools_dump_force' => new TwigFunction('tools_dump_force', [$this, 'dumpForce']),
      'tools_dump_kill' => new TwigFunction('tools_dump_kill', [$this, 'dumpKill']),
      'is_dev' => new TwigFunction('tools_is_dev', [$this, 'isDev']),

      'value_by_key' => new TwigFunction('value_by_key', [$this, 'valueByKey']),
      'array_to_string' => new TwigFunction('array_to_string', [$this, 'arrayToString']),
      'unset_in_array' => new TwigFunction('unset_in_array', [$this, 'unsetInArray']),
      'add_in_array' => new TwigFunction('add_in_array', [$this, 'addInArray']),
      'ksort' => new TwigFunction('ksort', [$this, 'ksort']),

      'twig_filter_exists' => new TwigFunction('twig_filter_exists', [$this, 'twigFilterExists'], ['needs_environment' => true]),
      'uuid' => new TwigFunction('uuid', [$this, 'uuid']),
      'remove_multi_br' => new TwigFunction('remove_multi_br', [$this, 'removeMultiBr']),

      'assets_exist' => new TwigFunction('asset_exist', [$this, 'assetExist']),
      'file_exist' => new TwigFunction('file_exist', [$this, 'fileExist']),
      'file_size' => new TwigFunction('file_size', [$this, 'fileSize']),
      'file_mime_type' => new TwigFunction('file_mime_type', [$this, 'fileMimeType']),
      'file_is_image' => new TwigFunction('file_is_image', [$this, 'isImage']),
      'file_image_size' => new TwigFunction('file_image_size', [$this, 'imageSize']),
    ];
  }

  /**
   * dump
   *
   * @param mixed ...$values
   *
   * @return void
   */
  public function dump(mixed ...$values): void
  {
    $_SERVER['APP_DEBUG_TRACE_NUMBER'] = 3;
    AustralTools::dump($values);
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
   * @param mixed ...$values
   *
   * @return void
   */
  public function dumpForce(mixed ...$values): void
  {
    AustralTools::dumpForce($values);
  }

  /**
   * @param mixed ...$values
   *
   * @return void
   */
  public function dumpKill(mixed ...$values): void
  {
    AustralTools::dumpKill($values);
  }

  /**
   * Returns the value of an array if the key exists
   *
   * @param array<string|int, mixed>|object $array   $array
   * @param string|int                      $key     key to get
   * @param mixed                           $default default
   *
   * @return mixed
   */
  public function valueByKey(array|object $array, string|int $key, mixed $default = null): mixed
  {
    return AustralTools::valueByKey($array, $key, $default);
  }

  /**
   * @param array<string|int, mixed> $array
   * @param bool                     $html
   * @param int                      $lvl
   *
   * @return string
   */
  public static function arrayToString(array $array = [], bool $html = false, int $lvl = 0): string
  {
    return AustralTools::arrayToString($array, $html);
  }

  /**
   * @param array<string, mixed>        $array
   * @param array<string, mixed>|string $keys
   * @param int                         $num
   *
   * @return array<string, mixed>
   */
  public static function unsetInArray(array $array, array|string $keys, int $num = 0): array
  {
    return AustralTools::unsetInArray($array, $keys);
  }

  /**
   * @param array<int|string, mixed> $array
   * @param int|string|null          $key
   * @param mixed|null               $values
   *
   * @return array<int|string, mixed>
   */
  public function addInArray(array $array, int|string|null $key = null, mixed $values = null): array
  {
    $array[$key] = $values;

    return $array;
  }

  /**
   * @param array<int|string, mixed> $array
   *
   * @return array<int|string, mixed>
   */
  public function ksort(array $array): array
  {
    ksort($array);

    return $array;
  }

  /**
   * @param Environment $twig
   * @param string      $name
   *
   * @return bool
   */
  public function twigFilterExists(Environment $twig, string $name): bool
  {
    return (bool) $twig->getFilter($name);
  }

  /**
   * @return string
   *
   * @throws \Exception
   */
  public function uuid(): string
  {
    return Uuid::uuid4()->toString();
  }

  /**
   * @param \IteratorAggregate  $var
   * @param object|class-string $instance
   *
   * @return bool
   *
   * @throws \ReflectionException
   */
  public function isInstanceOf(\IteratorAggregate $var, object|string $instance): bool
  {
    $reflexionClass = new \ReflectionClass($instance);

    return $reflexionClass->isInstance($var);
  }

  /**
   * removeMultiBr
   *
   * @param string $value
   * @param string $tagName
   *
   * @return string
   */
  public function removeMultiBr(string $value, string $tagName = 'p'): string
  {
    $normalized = str_replace(["\n", "\r"], '', $value);

    return preg_replace(
      '#(<br\s*/?>){2,}#i',
      "</{$tagName}><{$tagName}>",
      $normalized
    ) ?? $normalized;
  }

  /**
   * @param string $assetPath
   *
   * @return bool
   */
  public function assetExist(string $assetPath): bool
  {
    $realAssetPath = AustralTools::join($this->kernelProjectDir, 'public', $assetPath);

    return $this->fileExist($realAssetPath);
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
   * @param bool   $humanize
   *
   * @return false|int|string
   */
  public function fileSize(string $filePath, bool $humanize = false): false|int|string
  {
    if ($this->fileExist($filePath))
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
   * @param bool   $returnArray
   *
   * @return array<string, int>|string
   */
  public function imageSize(string $filePath, bool $returnArray = true): array|string
  {
    return AustralTools::imageDimension($filePath, $returnArray);
  }
}
