<?php
/*
 * This file is part of the App package.
 *
 * (c) Yipikai <support@yipikai.studio>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\ToolsBundle;

use Random\RandomException;
use Symfony\Component\String\Slugger\AsciiSlugger;
use function Symfony\Component\String\u;

/**
 * Austral Tools.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class AustralTools
{

  /**
   * @return bool
   */
  public static function isAppDebug(): bool
  {
    return (isset($_SERVER['APP_DEBUG']) && $_SERVER['APP_DEBUG']);
  }

  /**
   * @return bool
   */
  public static function isDebugTrace(): bool
  {
    return !isset($_SERVER['APP_DEBUG_TRACE_NO']);
  }

  /**
   * @return int
   */
  public static function numberTrace(): int
  {
    return $_SERVER['APP_DEBUG_TRACE_NUMBER'] ?? 1;
  }

  /**
   * Debug all element in argument
   *
   * @return void
   */
  public static function dump(): void
  {
    if(self::isAppDebug())
    {
      self::debug(func_get_args());
    }
  }

  /**
   * Debug all element in argument for prod force
   *
   * @return void
   */
  public static function dumpForce(): void
  {
    self::debug(func_get_args());
  }

  /**
   * Debug all element in argument and Die execution
   *
   * @return void and die execution
   */
  public static function dumpKill(): void
  {
    if(self::isAppDebug())
    {
      self::debug(func_get_args());
      die("AUSTRAL-STOP");
    }
  }

  /**
   * Debug all element in argument and Die execution for prod force and kill
   *
   * @return void and die execution
   */
  public static function dumpKillForce(): void
  {
    self::debug(func_get_args());
    die("AUSTRAL-STOP");
  }

  /**
   * @return void
   */
  private static function debug(): void
  {
    $numargs = func_num_args();
    $params = func_get_args();

    if(self::isDebugTrace())
    {
      $debugBacktraceAll = debug_backtrace();
      $debugBacktrace = self::valueByKey($debugBacktraceAll, self::numberTrace());
      if(array_key_exists("args", $debugBacktrace))
      {
        unset($debugBacktrace["args"]);
      }
      if(isset($debugBacktrace["object"]))
      {
        $objectTwig = $debugBacktrace["object"];
        if(is_object($objectTwig) && method_exists($objectTwig, "getSourceContext"))
        {
          $sourceContext = $objectTwig->getSourceContext();
          if(is_object($sourceContext) && method_exists($sourceContext, "getPath"))
          {
            $debugBacktrace["realPath"] = $sourceContext->getPath();
          }
        }
      }
      $params[] = array($debugBacktrace);
      $numargs += 1;
    }
    for ($i = 0; $i < $numargs; $i++)
    {
      foreach($params[$i] as $param)
      {
        self::isAppDebug() ? dump($param) : var_dump($param);
      }
    }
  }

  /**
   * Returns the value of an array, if the key exists
   * @deprecated
   * @param array<string|int, mixed>|object $array $array
   * @param string|int $key     key to get
   * @param mixed $default      default
   *
   * @return mixed
   */
  public static function getValueByKey(array|object $array, string|int $key, mixed $default = null): mixed
  {
    return self::valueByKey($array, $key, $default);
  }

  /**
   * Returns the value of an array, if the key exists
   *
   * @param array<string|int, mixed>|object $array $array
   * @param string|int $key     key to get
   * @param mixed $default      default
   *
   * @return mixed
   */
  public static function valueByKey(array|object $array, string|int $key, mixed $default = null): mixed
  {
    $returnValue = $default;
    if(is_object($array))
    {
      $key = (string) $key;
      $getter = self::createGetterFunction($key);
      if(method_exists($array, $key))
      {
        $returnValue = $array->$key();
      }
      elseif(method_exists($array, $getter))
      {
        $returnValue =  $array->$getter();
      }
      elseif(method_exists($array, "getTranslateCurrent"))
      {
        $translate = $array->getTranslateCurrent();
        if(method_exists($translate, $key))
        {
          $returnValue = $translate->$key();
        }
        elseif(method_exists($translate, $getter))
        {
          $returnValue =  $translate->$getter();
        }
      }
    }
    elseif(array_key_exists($key, $array))
    {
      $returnValue = $array[$key];
    }
    return ($returnValue !== null) ? $returnValue : $default;
  }

  /**
   * @param array<string, mixed> $array
   * @param array<string|int, mixed>|string $keys
   * @param int $num
   *
   * @return array<string, mixed>
   */
  public static function unsetInArray(array $array, array|string $keys, int $num = 0): array
  {
    if(is_array($keys))
    {
      $key = AustralTools::first($keys);
      unset($keys[array_key_first($keys)]);
      if(count($keys) == 1)
      {
        $keys = AustralTools::first($keys);
      }
      if(str_contains($key, "->"))
      {
        $key = str_replace("->", "", $key);
        $array->$key = AustralTools::unsetInArray($array->$key, $keys, $num+1);
      }
      else
      {
        $array[$key] = AustralTools::unsetInArray($array[$key], $keys, $num+1);
      }
    }
    else
    {
      if(array_key_exists($keys, $array))
      {
        unset($array[$keys]);
      }
    }
    return $array;
  }

  /**
   * get the first value of an array
   *
   * @param array<string|int, mixed>          $array          source array
   * @param mixed                 $default        default
   *
   * @return mixed first value
   */
  public static function first(array $array, mixed $default = null): mixed
  {
    if(empty($array))
    {
      return $default;
    }
    $a = array_shift($array);
    unset($array);
    return $a;
  }

  /**
   * get the last value of an array
   *
   * @param array<string|int, mixed>          $array          source array
   * @param mixed                 $default        default
   *
   * @return mixed last value
   */
  public static function last(array $array, mixed $default = null): mixed
  {
    if(empty($array))
    {
      return $default;
    }
    $a = array_pop($array);
    unset($array);
    return $a;
  }


  const RANDOM_TYPE_ALL = "all";
  const RANDOM_TYPE_LETTERS = "letters";
  const RANDOM_TYPE_NUMBERS = "numbers";

  /**
   * Generate a random string with length and typologie
   *
   * @param integer $length 8
   * @param string $type    all OR letters OR numbers
   *
   * @return string
   * @throws RandomException
   */
  public static function random(int $length = 8, string $type = self::RANDOM_TYPE_ALL): string
  {
    $pool = '';
    if ($type === self::RANDOM_TYPE_ALL || $type === self::RANDOM_TYPE_LETTERS) {
      $pool .= 'abcdefghijklmnopqrstuvwxyz';
    }
    if ($type === self::RANDOM_TYPE_ALL || $type === self::RANDOM_TYPE_NUMBERS) {
      $pool .= '0123456789';
    }
    if ($pool === '') {
      throw new \InvalidArgumentException('No character set has been defined for this type');
    }
    if ($length <= 0) {
      return '';
    }
    $maxIndex = strlen($pool) - 1;
    $out = '';
    for ($i = 0; $i < $length; $i++) {
      $out .= $pool[random_int(0, $maxIndex)];
    }
    return $out;
  }

  /**
   * @var array<string, string>
   */
  private static array $accentsReplacements = array(
    "¥" => "Y", "µ" => "u", "À" => "A", "Á" => "A",
    "Â" => "A", "Ã" => "A", "Ä" => "A", "Å" => "A",
    "Æ" => "A", "Ç" => "C", "È" => "E", "É" => "E",
    "Ê" => "E", "Ë" => "E", "Ì" => "I", "Í" => "I",
    "Î" => "I", "Ï" => "I", "Ð" => "D", "Ñ" => "N",
    "Ò" => "O", "Ó" => "O", "Ô" => "O", "Õ" => "O",
    "Ö" => "O", "Ø" => "O", "Ù" => "U", "Ú" => "U",
    "Û" => "U", "Ü" => "U", "Ý" => "Y", "ß" => "s",
    "à" => "a", "á" => "a", "â" => "a", "ã" => "a",
    "ä" => "a", "å" => "a", "æ" => "a", "ç" => "c",
    "è" => "e", "é" => "e", "ê" => "e", "ë" => "e",
    "ì" => "i", "í" => "i", "î" => "i", "ï" => "i",
    "ð" => "o", "ñ" => "n", "ò" => "o", "ó" => "o",
    "ô" => "o", "õ" => "o", "ö" => "o", "ø" => "o",
    "ù" => "u", "ú" => "u", "û" => "u", "ü" => "u",
    "ý" => "y", "ÿ" => "y");

  /**
   * @param string $string
   *
   * @return string
   */
  public static function removeAccents(string $string): string
  {
    return str_replace('!', '', strtr(trim($string), self::$accentsReplacements));
  }

  /**
   * Strip string value and filtre delete the useless word
   *
   * @param string                            $string        8
   * @param array<string|int|float, string>   $replacements   array
   * Example (default French stop words): "Le blog et le site sont super -> blog-site-sont-super"
   *
   * @return string striped
   */
  public static function stripFilter(string $string, array $replacements = []): string
  {
    $replacements = $replacements ? : array("a", "de", "du", "des", "ou", "et", "en", "les", "la", "le", "l");
    $words = explode("-", self::strip($string));
    foreach($words as $it => $word)
      if (in_array($word, $replacements)) unset($words[$it]);
    return implode("-", $words);
  }

  /**
   * Strip string value
   *
   * @param string $result
   * @param boolean $toLower
   * @param boolean $removeAccent
   * @param boolean $removeNoChars
   * Example "The blog is very large -> the-blog-is-very-large"
   *
   * @return string striped
   */
  public static function strip(string $result, bool $toLower = true, bool $removeAccent = true, bool $removeNoChars = true): string
  {
    if($removeAccent)
      $result  = self::removeAccents($result);

    if ($toLower)
      $result = strtolower($result);

    // Unify separators: turn hyphens into spaces
    $result = str_replace('-', ' ', $result);

    // Collapse multiple spaces into a single one
    $result = preg_replace('/\s+/', ' ', $result) ?? $result;

    if ($removeNoChars) {
      // Replace non-alphanumeric characters with a space
      $result = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $result) ?? $result;
    }

    // Replace spaces with hyphens
    $result = preg_replace('/\s+/', '-', $result) ?? $result;

    // Trim leading and trailing hyphens
    return trim($result, '-');
  }

  /**
   * @param string $string
   * @param bool $withSlash
   * @param bool $usedStrip
   *
   * @return string
   */
  public static function slugger(string $string, bool $withSlash = false, bool $usedStrip = false): string
  {
    $slugger = new AsciiSlugger();
    if($withSlash)
    {
      $strings = explode("/", $string);
      $slugFinalArray = [];
      foreach($strings as $string)
      {
        $slugFinalArray[] = $slugger->slug($usedStrip ? self::strip($string) : u($string)->snake())->lower()->toString();
      }
      $slugFinal = implode("/", $slugFinalArray);
    }
    else
    {
      $slugFinal = $slugger->slug($usedStrip ? self::strip($string) : u($string)->snake())->lower()->toString();
    }
    return $slugFinal;
  }

  /**
   * @param string $string
   *
   * @return string
   */
  public static function generatorKey(string $string): string
  {
    return self::strip($string, true, true, false);
  }

  /**
   * executeClosureFunction
   *
   * @param object $self
   * @param \Closure(object|null): mixed|string|array<int, string> $closureOrString
   * @param object|null $object
   * @param mixed $default
   *
   * @return mixed
   */
  public static function executeFunction(object $self, \Closure|string|array $closureOrString, object|null $object = null, mixed $default = []): mixed
  {
    if($closureOrString instanceof \Closure)
    {
      if($object)
      {
        return $closureOrString->call($self, $object);
      }
      return $default;
    }
    if($closureOrString)
    {
      $value = null;
      $parameter = null;
      if(is_array($closureOrString))
      {
        list($closureOrString, $parameter) = $closureOrString;
      }
      if(is_object($object) && method_exists($object, $closureOrString))
      {
        $value = $object->{$closureOrString}($parameter);
      }
      else
      {
        $getter = AustralTools::createGetterFunction($closureOrString);
        if(is_object($object) && method_exists($object, $getter))
        {
          $value = $object->$getter($parameter);
        }
      }
      if($value instanceof \DateTime && $parameter) {
        $value = $value->format($parameter);
      }
      return $value;
    }
    return $default;
  }

  /**
   * @param string $value
   *
   * @return string
   */
  public static function createGetterFunction(string $value): string
  {
    return self::createFunction($value, "get");
  }

  /**
   * @param string $value
   *
   * @return string
   */
  public static function createSetterFunction(string $value): string
  {
    return self::createFunction($value, "set");
  }

  /**
   * @param string $value
   * @param string|null $before
   * @param string|null $after
   *
   * @return string
   */
  public static function createFunction(string $value, ?string $before = null, ?string $after = null): string
  {
    $value = u($value)->camel()->title();
    $value = $before ? $value->ensureStart($before) : $value;
    $value = $after ? $value->ensureEnd($after) : $value;
    return $value->toString();
  }

  /**
   * @return string
   */
  public static function join(): string
  {
    if ('/' !== DIRECTORY_SEPARATOR)
    {
      self::dumpKill('IS NOT UNIX SYSTEM');
    }
    $parts = func_get_args();
    $dirtyPath = implode('/', $parts);
    if(str_contains($dirtyPath, '//'))
    {
      $dirtyPath = preg_replace('|(/{2,})|', '/', $dirtyPath);
    }
    $cleanPath = $dirtyPath ? trim($dirtyPath, '/') : "";
    return '/'.$cleanPath;
  }

  /**
   * @param string|int|float $filePath
   * @param string|null $unit
   * @param int $precision
   * @param bool $withUnit
   *
   * @return string
   */
  public static function humanizeSize(string|int|float $filePath, ?string $unit = null, int $precision = 1, bool $withUnit = true): string
  {
    $bytes = is_numeric($filePath) ? $filePath : filesize($filePath);
    if(($bytes >= (1024**3) && !$unit) || $unit == "Go")
    {
      $size = (round($bytes/(1024**3), $precision, PHP_ROUND_HALF_UP)).($withUnit ? " Go" : "");
    }
    elseif(($bytes >= (1024**2) && !$unit) || $unit == "Mo")
    {
      $size = (round($bytes/(1024**2), $precision, PHP_ROUND_HALF_UP)).($withUnit ? " Mo" : "");
    }
    elseif(($bytes >= 1024 && !$unit) || $unit == "Ko")
    {
      $size = (round($bytes/1024, 0, PHP_ROUND_HALF_UP)).($withUnit ? " Ko" : "");
    }
    else
    {
      $size = $bytes.($withUnit ? " o" : "");
    }
    return $size;
  }

  /**
   * @param string|null $sizeHumanize
   *
   * @return float|int
   */
  public static function unHumanizeSize(?string $sizeHumanize = null): float|int
  {
    $result = 0;
    if($sizeHumanize) {
      preg_match("/(\d{1,})([a-z]{1,2})/", strtolower($sizeHumanize), $matches);
      if(count($matches) == 3) {
        if(str_starts_with($matches[2], "g")) {
          $result = $matches[1] * (1024 ** 3);
        }
        elseif(str_starts_with($matches[2], "m")) {
          $result = $matches[1] * (1024 ** 2);
        }
        elseif(str_starts_with($matches[2], "k")) {
          $result = $matches[1] * 1024;
        }
        else {
          $result = $matches[1] * 1;
        }
      }
    }
    return $result;
  }

  /**
   * @param string $filePath
   *
   * @return string|null
   */
  public static function mimeType(string $filePath): ?string
  {
    $value = null;
    if(file_exists($filePath))
    {
      if($finfo = finfo_open(FILEINFO_MIME_TYPE))
      {
        $value = finfo_file($finfo, $filePath);
      }
    }
    return $value === false ? null : $value;
  }

  /**
   * @param string $filePath
   *
   * @return string|null
   */
  public static function extension(string $filePath): ?string
  {
    $pathInfo = null;
    if(file_exists($filePath))
    {
      $pathInfo = pathinfo($filePath, PATHINFO_EXTENSION);
    }
    return $pathInfo;
  }

  /**
   * @param string $filename
   *
   * @return bool
   */
  public static function isImage(string $filename): bool
  {
    $filename = strtolower($filename);
    return (bool) preg_match("([^\s]+(\.(?i)(jpg|jpeg|png|gif|svg|webp))$)", $filename);
  }

  /**
   * @param string $filePath
   * @param bool $returnArray
   * @param string $implode
   * @return array<string, int>|string
   */
  public static function imageDimension(string $filePath, bool $returnArray = false, string $implode = " x "): array|string
  {
    $value = [];
    if(file_exists($filePath) && self::isImage($filePath))
    {
      $array = getimagesize($filePath);
      if($array !== false)
      {
        list($width, $height) = $array;
        $value = array(
          "width"   =>  $width,
          "height"  =>  $height
        );
      }
    }
    return $returnArray ? $value : implode($implode, $value);
  }

  /**
   * @param string $class
   * @param string $classUsed
   *
   * @return bool
   */
  public static function usedClass(string $class, string $classUsed): bool
  {
    $return = false;
    $classUses = class_uses($class);
    $classParents = class_parents($class);
    $classUses = array_merge(is_array($classUses) ? $classUses : [], is_array($classParents) ? $classParents : []);
    if(in_array($classUsed, $classUses))
    {
      return true;
    }
    elseif($parents = class_parents($class))
    {
      foreach($parents as $parent)
      {
        if(self::usedClass($parent, $classUsed))
        {
          $return = true;
        }
      }
    }
    return $return;
  }

  /**
   * @param mixed $class
   * @param array<int|string, string>|string $implementClasses
   *
   * @return bool
   */
  public static function usedImplements(mixed $class, array|string $implementClasses): bool
  {
    $targets = (array) $implementClasses;
    return array_any($targets, fn($iface) => is_a($class, $iface, true));
  }

  /**
   * @param string $value
   * @param string $cryptKey
   *
   * @return string
   */
  private static function generateCryptByKey(string $value, string $cryptKey = "default_austral_key"): string
  {
    $cryptKey = sha1($cryptKey);
    $counter = 0;
    $var = "";
    for($ctr = 0; $ctr < strlen($value); $ctr++)
    {
      if($counter == strlen($cryptKey))
      {
        $counter = 0;
      }
      $var .= substr($value, $ctr, 1) ^ substr($cryptKey, $counter, 1);
      $counter++;
    }
    return $var;
  }

  /**
   * @param array<int|string, mixed>|string $value
   * @param string $cryptKey
   *
   * @return string
   */
  public static function encrypte(array|string $value, string $cryptKey = "default_austral_key"): string
  {
    if(is_array($value))
    {
      $value = json_encode($value);
      $value = $value === false ? "" : $value;
    }
    srand((int) microtime()*1000000);
    $key = md5((string) rand(0,32000));
    $counter=0;
    $encryptValue = "";
    for ($ctr=0; $ctr < strlen($value); $ctr++)
    {
      if($counter == strlen($key))
      {
        $counter=0;
      }
      $encryptValue .= substr($key, $counter, 1) . (substr($value, $ctr, 1) ^ substr($key, $counter, 1));
      $counter++;
    }
    return base64_encode(self::generateCryptByKey($encryptValue, $cryptKey));
  }

  /**
   * @param string $encryptValue
   * @param string $cryptKey
   *
   * @return string|array<string|int, mixed>
   */
  public static function decrypte(string $encryptValue, string $cryptKey = "default_austral_key"): array|string
  {
    $encryptValue = self::generateCryptByKey(base64_decode($encryptValue), $cryptKey);
    $value = "";
    for ($ctr=0; $ctr < strlen($encryptValue); $ctr++)
    {
      $key = substr($encryptValue, $ctr, 1);
      $ctr++;
      $value .= (substr($encryptValue, $ctr, 1) ^ $key);
    }
    $valueJson = json_decode($value, true);
    if(is_array($valueJson))
    {
      return $valueJson;
    }
    return $value;
  }

  /**
   * @param string|null $value
   * @param string $preg
   *
   * @return array<string, string>
   */
  public static function getKeysInValue(?string $value = null, string $preg = '|(%\S+%)|iuU'): array
  {
    $values = [];
    if($value)
    {
      preg_match_all($preg, $value, $matchs);
      $matchContentValues = AustralTools::getValueByKey($matchs, 1, []);
      if(count($matchContentValues))
      {
        foreach($matchContentValues as $matchContentValue)
        {
          $values[$matchContentValue] = $matchContentValue;
        }
      }
    }
    return $values;
  }

  /**
   * @param string|null $initialVal
   * @param array<string|int, mixed> $valuesToReplace
   * @param bool $removeEmpty true: keeps the keys without a value in the text; false: replaces them with ""
   *
   * @return string|null
   */
  public static function replaceKeyByValue(?string $initialVal, array $valuesToReplace = [], bool $removeEmpty = true): ?string
  {
    if ($initialVal === null || $initialVal === '') {
      return $initialVal;
    }
    $values = self::getKeysInValue($initialVal);
    if ($values) {
      $replaceValues = [];
      foreach ($values as $key => $value) {
        $keyObject = str_replace(['%'], '', $value);
        if ($variableReplace = AustralTools::getValueByKey($valuesToReplace, $keyObject, null)) {
          $replaceValues[$value] = $variableReplace;
        } elseif ($removeEmpty) {
          unset($values[$key]);
        } else {
          $replaceValues[$value] = '';
        }
      }
      if (count($replaceValues)) {
        $initialVal = str_replace($values, $replaceValues, $initialVal);
      }
    }
    return $initialVal;
  }

  /**
   * @param string $delimiter
   * @param array<string|int, mixed> $items
   * @param string $prepend
   *
   * @return array<string, string>
   */
  public static function flattenArray(string $delimiter = '.', array $items = [], string $prepend = ''): array
  {
    $flatten = [];
    foreach ($items as $key => $value) {
      $key = str_replace("\x00*\x00", "", $key);
      if(is_numeric($key))
      {
        $flatten[trim($prepend, ".")][$key] = $value;
      }
      elseif(is_object($value))
      {
        $flatten = array_merge(
          $flatten,
          self::flattenArray($delimiter, (array) $value, $prepend.$key.$delimiter)
        );
      }
      else
      {
        if (is_array($value) && !empty($value)) {
          $flatten = array_merge(
            $flatten,
            self::flattenArray($delimiter, $value, $prepend.$key.$delimiter)
          );
        } else {
          $flatten[$prepend.$key] = $value;
        }
      }
    }
    return $flatten;
  }

  /**
   * @param array<string|int, mixed> $items
   * @param string $delimiter
   *
   * @return array<string, mixed>
   */
  public static function arrayByFlatten(array $items, string $delimiter = '.'): array
  {
    if ($delimiter === '') {
      throw new \InvalidArgumentException('Delimiter must be a non-empty string.');
    }

    $new = [];

    foreach ($items as $key => $value) {
      /** @var mixed $value */
      $key = (string) $key;
      $composedKey = "{$key}{$delimiter}element";

      $segments = explode($delimiter, $composedKey);
      $last = &$new[$segments[0]];

      if ($last !== null && !is_array($last)) {
        throw new \LogicException(sprintf(
          "The '%s' key has already been defined as being '%s'",
          $segments[0],
          gettype($last)
        ));
      }

      foreach ($segments as $k => $segment) {
        if ($k !== 0) {
          /** @var array<string, mixed> $last */
          $last = &$last[$segment];
        }
      }

      $last = is_array($value)
        ? self::arrayByFlatten($value, $delimiter)
        : $value;
    }
    return $new;
  }

  /**
   * @param array<string|int, mixed> $array
   * @param bool $html
   * @param int $lvl
   *
   * @return string
   */
  public static function arrayToString(array $array = [], bool $html = false, int $lvl = 0): string
  {
    $text = "";
    $hasTitle = false;
    foreach($array as $key => $values)
    {
      $hasTitle = is_string($key);
      $text .= $html ? "<li class='austral-value-container'>" : "";
      if($hasTitle) {
        $text .= $html ? "<span class='austral-title-key'>{$key}</span>" : $key."\n";
      }
      $text .= $html ? "<div class='austral-value'>" : "";
      if(is_array($values))
      {
        if(count($values) > 0)
        {
          $text .= self::arrayToString($values, $html, $lvl+1);
        }
        else
        {
          $text .= " - ";
        }
      }
      else
      {
        $text .= $values ?? " - ";
      }
      $text .= $html ? "</div>" : "";
      $text .= $html ? "</li>" : "\n";
    }
    $content = $html ? "<ul class='austral-array-to-string lvl-{$lvl} ".($hasTitle ? "has-title" : "no-title")."'>" : "";
    $content .= $text;
    $content .= $html ? "</ul>" : "";
    return $content;
  }

  /**
   * @var array<string, int|float>
   */
  private static array $memoryUsageKeys = [];

  /**
   * memoryUsageStart
   *
   * @param string $name
   * @return void
   */
  public static function memoryUsageStart(string $name = "default"): void
  {
    self::$memoryUsageKeys[$name] = memory_get_usage();
  }

  /**
   * memoryUsageEnd
   *
   * @param string $name
   * @param bool $dump
   * @return string
   */
  public static function memoryUsageEnd(string $name = "default", bool $dump = true): string
  {
    $end = memory_get_usage();
    $start = self::getValueByKey(self::$memoryUsageKeys, $name, 0);
    $memoryUsed = ($end - $start)." octets";
    if($dump) {
      AustralTools::dump($memoryUsed);
    }
    return $memoryUsed;
  }



}
