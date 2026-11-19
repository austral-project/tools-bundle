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
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Twig\Attribute\AsTwigFilter;
use Twig\Attribute\AsTwigFunction;
use Twig\Attribute\AsTwigTest;
use Twig\Environment;

/**
 * Austral Tools Twig Extension.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 *
 * @final
 */
class ToolsTwig
{
  /**
   * @var string
   */
  protected string $kernelProjectDir;

  /**
   * Initialize tinymce helper
   *
   * @param string $kernelProjectDir
   */
  public function __construct(
    #[Autowire('%kernel.project_dir%')] string $kernelProjectDir
  ) {
    $this->kernelProjectDir = $kernelProjectDir;
  }

  /**
   * dump
   *
   * @param mixed ...$values
   *
   * @return void
   */
  #[AsTwigFilter('tools_dump')]
  #[AsTwigFunction('tools_dump')]
  public function dump(mixed ...$values): void
  {
    $_SERVER['APP_DEBUG_TRACE_NUMBER'] = 3;
    AustralTools::dump($values);
    $_SERVER['APP_DEBUG_TRACE_NUMBER'] = 1;
  }

  /**
   * @return bool
   */
  #[AsTwigFunction('is_dev')]
  public function isDev(): bool
  {
    return AustralTools::isAppDebug();
  }

  /**
   * @param mixed ...$values
   *
   * @return void
   */
  #[AsTwigFilter('tools_dump_force')]
  #[AsTwigFunction('tools_dump_force')]
  public function dumpForce(mixed ...$values): void
  {
    AustralTools::dumpForce($values);
  }

  /**
   * @param mixed ...$values
   *
   * @return void
   */
  #[AsTwigFilter('tools_dump_kill')]
  #[AsTwigFunction('tools_dump_kill')]
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
  #[AsTwigFilter('value_by_key')]
  #[AsTwigFunction('value_by_key')]
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
  #[AsTwigFilter('array_to_string')]
  #[AsTwigFunction('array_to_string')]
  public static function arrayToString(array $array = [], bool $html = false, int $lvl = 0): string
  {
    return AustralTools::arrayToString($array, $html);
  }

  /**
   * @return string
   *
   * @throws \Exception
   */
  #[AsTwigFunction('uuid')]
  public function uuid(): string
  {
    return Uuid::uuid4()->toString();
  }

  /**
   * @param array<string, mixed>        $array
   * @param array<string, mixed>|string $keys
   * @param int                         $num
   *
   * @return array<string, mixed>
   */
  #[AsTwigFilter('unset_in_array')]
  #[AsTwigFunction('unset_in_array')]
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
  #[AsTwigFunction('add_in_array')]
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
  #[AsTwigFilter('ksort')]
  #[AsTwigFunction('ksort')]
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
  #[AsTwigFilter('twig_filter_exists', needsEnvironment: true)]
  #[AsTwigTest('filter_exists', needsEnvironment: true)]
  #[AsTwigFunction('twig_filter_exists', needsEnvironment: true)]
  public function twigFilterExists(Environment $twig, string $name): bool
  {
    return (bool) $twig->getFilter($name);
  }

  /**
   * @param \IteratorAggregate  $var
   * @param object|class-string $instance
   *
   * @return bool
   *
   * @throws \ReflectionException
   */
  #[AsTwigTest('instanceof')]
  public function isInstanceOf(\IteratorAggregate $var, object|string $instance): bool
  {
    $reflexionClass = new \ReflectionClass($instance);

    return $reflexionClass->isInstance($var);
  }

  /**
   * @param string $assetPath
   *
   * @return bool
   */
  #[AsTwigFilter('asset_exist')]
  #[AsTwigFunction('asset_exist')]
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
  #[AsTwigFilter('file_exist')]
  #[AsTwigFunction('file_exist')]
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
  #[AsTwigFilter('file_size')]
  #[AsTwigFunction('file_size')]
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
  #[AsTwigFilter('file_mime_type')]
  #[AsTwigFunction('file_mime_type')]
  public function fileMimeType(string $filePath): ?string
  {
    return AustralTools::mimeType($filePath);
  }

  /**
   * @param string $filePath
   *
   * @return bool
   */
  #[AsTwigFilter('file_is_image')]
  #[AsTwigFunction('file_is_image')]
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
  #[AsTwigFilter('file_image_size')]
  #[AsTwigFunction('file_image_size')]
  public function imageSize(string $filePath, bool $returnArray = true): array|string
  {
    return AustralTools::imageDimension($filePath, $returnArray);
  }

  /**
   * removeMultiBr
   *
   * @param string $value
   * @param string $tagName
   *
   * @return string
   */
  #[AsTwigFilter('remove_multi_br')]
  #[AsTwigFunction('remove_multi_br')]
  public function removeMultiBr(string $value, string $tagName = 'p'): string
  {
    $normalized = str_replace(["\n", "\r"], '', $value);

    return preg_replace(
      '#(<br\s*/?>){2,}#i',
      "</{$tagName}><{$tagName}>",
      $normalized
    ) ?? $normalized;
  }
}
