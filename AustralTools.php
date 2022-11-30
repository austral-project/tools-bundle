<?php
/*
 * This file is part of the Austral Tools Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\ToolsBundle;

use Symfony\Component\String\Slugger\AsciiSlugger;
use function Symfony\Component\String\u;

/**
 * Austral Tools.
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
    return isset($_SERVER['APP_DEBUG_TRACE_NO']) ? false : true;
  }

  /**
   * @return int|mixed
   */
  public static function numberTrace()
  {
    return isset($_SERVER['APP_DEBUG_TRACE_NUMBER']) ? $_SERVER['APP_DEBUG_TRACE_NUMBER'] : 1;
  }

  /**
   * Debug all element in argument
   * 
   * @param mixed
   * 
   * @return void
   */
  public static function dump()
  {
    if(self::isAppDebug())
    {
      self::debug(func_get_args());
    }
  }

  /**
   * Debug all element in argument for prod force
   * 
   * @param mixed
   * 
   * @return void
   */
  public static function dumpForce()
  {
    self::debug(func_get_args());
  }
  
  /**
   * Debug all element in argument and Die execution
   * 
   * @param mixed
   * 
   * @return void and die execution
   */
  public static function dumpKill()
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
   * @param mixed
   * 
   * @return void and die execution
   */
  public static function dumpKillForce()
  {
    self::debug(func_get_args());
    die("AUSTRAL-STOP");
  }

  /**
   * @return void
   */
  private static function debug()
  {
    $numargs = func_num_args();
    $params = func_get_args();

    if(self::isDebugTrace())
    {
      $debugBacktraceAll = debug_backtrace();
      $debugBacktrace = self::getValueByKey($debugBacktraceAll, self::numberTrace());
      if(array_key_exists("args", $debugBacktrace))
      {
        unset($debugBacktrace["args"]);
      }
      if(isset($debugBacktrace["object"]))
      {
        $objectTwig = $debugBacktrace["object"];
        if(method_exists($objectTwig, "getSourceContext"))
        {
          $sourceContext = $objectTwig->getSourceContext();
          if(method_exists($sourceContext, "getPath"))
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
        self::isAppDebug() ? dump($param) : (var_dump($param).(print("<br/><br/>")));
      }
    }
  }

  /**
   * Returns the value of an array, if the key exists
   *
   * @param $array
   * @param string|int $key key to get
   * @param null|string|array $default default
   *
   * @return mixed
   */
  public static function getValueByKey($array, $key, $default = null)
  {
    $returnValue = $default;
    if(is_object($array))
    {
      $getter = self::createGetterFunction($key);
      if(method_exists($array, $key))
      {
        $returnValue = $array->$key();
      }
      elseif(method_exists($array, $getter))
      {
        $returnValue =  $array->$getter();
      }
      elseif(self::usedImplements(get_class($array), "Austral\EntityBundle\Entity\Interfaces\TranslateMasterInterface"))
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
    elseif(is_array($key) || !is_array($array))
    {
      $returnValue = 'Error Tools::getValueByKey() -> $Key is array or $array is not array';
    }
    elseif(array_key_exists($key, $array))
    {
      $returnValue = $array[$key];
    }
    return ($returnValue !== null) ? $returnValue : $default;
  }

  /**
   * @param array $array
   * @param string|array $keys
   * @param int $num
   *
   * @return array
   */
  public static function unsetInArray(array $array, $keys, int $num = 0): array
  {
    if(is_array($keys))
    {
      $key = AustralTools::first($keys);
      unset($keys[array_key_first($keys)]);
      if(count($keys) == 1)
      {
        $keys = AustralTools::first($keys);
      }
      if(strpos($key, "->") !== false)
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
   * get first value of an array
   *
   * @param array               $array          source array
   * @param string|array        $default        default
   *
   * @return mixed first value
   */
  public static function first(array $array, $default = null)
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
   * get last value of an array
   *
   * @param array             $array          source array
   * @param string|array      $default        default
   *
   * @return mixed last value
   */
  public static function last(array $array, $default = null)
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
   * Generate random string with length and typologie
   *
   * @param integer    $length         8
   * @param string     $type           all OR letters OR numbers
   *
   * @return string
   */
  public static function random(int $length = 8, string $type = self::RANDOM_TYPE_ALL): string
  {
    $val = $values = "";
    $nbValues = 0;
    if($type === self::RANDOM_TYPE_ALL || $type === self::RANDOM_TYPE_LETTERS)
    {
      $values .= 'abcdefghijklmnopqrstuvwxyz';
      $nbValues += 25;
    }
    if($type === self::RANDOM_TYPE_ALL || $type === self::RANDOM_TYPE_NUMBERS)
    {
      $values .= "0123456789";
      $nbValues += 9;
    }
    for($i = 0; $i < $length; $i++)
    {
      $val .= $values[rand( 0, $nbValues )];
    }
    return $val;
  }

  /**
   * @var array
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
   * @param string $string         8
   * @param mixed  $degagements   null
   * Exemple "Le blog et le site sont super -> blog-site-sont-super"
   *
   * @return string striped
   */
  public static function stripFilter(string $string, $degagements = null): string
  {
    $degagements = $degagements ? : array("a", "de", "du", "des", "ou", "et", "en", "les", "la", "le", "l");
    $words = explode("-", self::strip($string));
    foreach($words as $it => $word)
      if (in_array($word, $degagements)) unset($words[$it]);
    return implode("-", $words);
  }
  
  /**
   * Strip string value
   *
   * @param string $result
   * @param boolean $tolower
   * @param boolean $removeAccent
   * @param boolean $removeNoChars
   * Exemple "The blog is very large -> the-blog-is-very-large"
   *
   * @return string striped
   */
  public static function strip(string $result, bool $tolower = true, bool $removeAccent = true, bool $removeNoChars = true): string
  {
    if($removeAccent)
      $result  = self::removeAccents($result);

    if ($tolower)
      $result = strtolower($result);

    $result = preg_replace('!\-!', ' ', $result);
    $result = preg_replace('!\s+!', ' ', $result);

    // strip all non word chars
    if ($removeNoChars)
      $result = preg_replace('/\W/', ' ', $result);

    // replace all white space sections with a dash
    $result = preg_replace('/\ +/', '-', $result);

    // trim dashes
    $result = preg_replace('/\-$/', '', $result);
    $result = preg_replace('/^\-/', '', $result);

    return $result;
  }

  /**
   * @param $string
   * @param bool $withSlash
   * @param bool $usedStrip
   *
   * @return string
   */
  public static function slugger($string, bool $withSlash = false, bool $usedStrip = false): string
  {
    $slugger = new AsciiSlugger();
    if($withSlash)
    {
      $strings = explode("/", $string);
      $slugFinalArray = array();
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
   * @param $string
   *
   * @return string
   */
  public static function generatorKey($string): string
  {
    return self::strip($string, true, true, false);
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
  public static function createFunction(string $value, string $before = null, string $after = null): string
  {
    $value = u($value)->camel()->title();
    $value = $before ? $value->ensureStart($before) : $value;
    $value = $after ? $value->ensureEnd($before) : $value;
    return $value->toString();
  }
 
  /**
   * @return string
   */
  public static function join(): string
  {
    $parts = func_get_args();
    $dirtyPath = implode('/', $parts);
    if(strpos($dirtyPath, '//') !== false)
    {
      $dirtyPath = preg_replace('|(/{2,})|', '/', $dirtyPath);
    }
    $cleanPath = trim($dirtyPath, '/');
    if ('/' === DIRECTORY_SEPARATOR)
    {
      $cleanPath = '/'.$cleanPath;
    }
    else
    {
      self::dumpKill('IS NOT UNIX SYSTEM');
    }
    return $cleanPath;
  }

  /**
   * @param $filePath
   * @param string $unit
   * @param int $precision
   * @param bool $withUnit
   *
   * @return string
   */
  public static function humanizeSize($filePath, string $unit = "", int $precision = 1, bool $withUnit = true): string
  {
    $bytes = is_numeric($filePath) ? $filePath : filesize($filePath);
    if(($bytes > (1024**3) && !$unit) || $unit == "Go")
    {
      $size = (round($bytes/(1024**3), $precision)).($withUnit ? " Go" : "");
    }
    elseif(($bytes > (1024**2) && !$unit) || $unit == "Mo")
    {
      $size = (round($bytes/(1024**2), $precision)).($withUnit ? " Mo" : "");
    }
    elseif(($bytes>1024 && !$unit) || $unit == "Ko")
    {
      $size = (round($bytes/1024)).($withUnit ? " Ko" : "");
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
   * @return float|int|mixed
   */
  public static function unHumanizeSize(string $sizeHumanize = null)
  {
    $result = 0;
    if($sizeHumanize) {
      preg_match("/(\d{1,})([a-z]{1,2})/", strtolower($sizeHumanize), $matches);
      if(count($matches) == 3) {
        if(substr($matches[2], 0, 1) == "g") {
          $result = $matches[1] * (1024 ** 3);
        }
        elseif(substr($matches[2], 0, 1) == "m") {
          $result = $matches[1] * (1024 ** 2);
        }
        elseif(substr($matches[2], 0, 1) == "k") {
          $result = $matches[1] * 1024;
        }
        else {
          $result = $matches[1];
        }
      }
    }
    return $result;
  }

  /**
   * @param string $filePath
   *
   * @return string
   */
  public static function mimeType(string $filePath): ?string
  {
    $value = null;
    if(file_exists($filePath))
    {
      $value = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $filePath);
    }
    return $value;
  }

  /**
   * @param string $filePath
   *
   * @return string
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
   *
   * @return array|string
   */
  public static function imageDimension(string $filePath, bool $returnArray = false)
  {
    $value = array();
    if(file_exists($filePath) && self::isImage($filePath))
    {
      list($width, $height) = getimagesize($filePath);
      $value = array(
        "width"   =>  $width,
        "height"  =>  $height
      );
    }
    return $returnArray ? $value : implode("x", $value);
  }

  /**
   * @param $class
   * @param $classUsed
   *
   * @return bool
   */
  public static function usedClass($class, $classUsed): bool
  {
    $return = false;
    $classUses = array_merge(class_uses($class), class_parents($class));
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
   * @param $class
   * @param $implementClass
   *
   * @return bool
   */
  public static function usedImplements($class, $implementClass): bool
  {
    $return = false;
    $implementClassUses = class_implements($class);
    if(in_array($implementClass, $implementClassUses))
    {
      return true;
    }
    elseif($parents = class_parents($class))
    {
      foreach($parents as $parent)
      {
        if(self::usedImplements($parent, $implementClassUses))
        {
          $return = true;
        }
      }
    }
    return $return;
  }

  /**
   * @param string $value
   * @param null $cryptKey
   *
   * @return string
   */
  private static function generateCryptByKey(string $value, $cryptKey = null): string
  {
    $cryptKey = $cryptKey ?? "default_austral_key";
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
   * @param string|array $value
   * @param null $cryptKey
   *
   * @return string
   */
  public static function encrypte($value, $cryptKey = null): string
  {
    if(is_array($value))
    {
      $value = json_encode($value);
    }
    srand((double)microtime()*1000000);
    $key = md5(rand(0,32000));
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
   * @param string|array $encryptValue
   * @param string|null $cryptKey
   *
   * @return string|array
   */
  public static function decrypte($encryptValue, string $cryptKey = null)
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
   * @return array
   */
  public static function getKeysInValue(?string $value = null, string $preg = '|(%\S+%)|iuU'): array
  {
    $values = array();
    if($value)
    {
      preg_match_all($preg, $value, $matchs);
      $matchContentValues = AustralTools::getValueByKey($matchs, 1, array());
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
   * @param array $valuesToReplace
   *
   * @return array|string|string[]
   */
  public static function replaceKeyByValue(string $initialVal = null, array $valuesToReplace = array())
  {
    if($initialVal)
    {
      $values = self::getKeysInValue($initialVal);
      if($values)
      {
        $replaceValues = array();
        foreach($values as $key => $value)
        {
          $keyObject = str_replace(array("%"), "", $value);
          if($variableReplace = AustralTools::getValueByKey($valuesToReplace, $keyObject, null))
          {
            $replaceValues[$value] = $variableReplace;
          }
          else
          {
            unset($values[$key]);
          }
        }
        if(count($replaceValues))
        {
          $initialVal = str_replace($values, $replaceValues, $initialVal);
        }
      }
    }
    return $initialVal;
  }

  /**
   * @param string $delimiter
   * @param null $items
   * @param string|null $prepend
   *
   * @return array
   */
  public static function flattenArray(string $delimiter = '.', $items = null, ?string $prepend = ''): array
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
   * @param array $items
   * @param string $delimiter
   *
   * @return array
   */
  public static function arrayByFlatten(array $items, $delimiter='.'): array
  {
    $new = array();
    foreach ($items as $key => $value) {
      $key = "{$key}{$delimiter}element";
      if (strpos($key, $delimiter) === false) {
        $new[$key] = is_array($value) ? self::arrayByFlatten($value, $delimiter) : $value;
        continue;
      }

      $segments = explode($delimiter, $key);
      $last = &$new[$segments[0]];
      if (!is_null($last) && !is_array($last)) {
        throw new \LogicException(sprintf("The '%s' key has already been defined as being '%s'", $segments[0], gettype($last)));
      }

      foreach ($segments as $k => $segment) {
        if ($k != 0) {
          $last = &$last[$segment];
        }
      }
      $last = is_array($value) ? self::arrayByFlatten($value, $delimiter) : $value;
    }
    return $new;
  }

  /**
   * @param array $array
   * @param bool $html
   * @param int $lvl
   *
   * @return string
   */
  public static function arrayToString(array $array = array(), bool $html = false, int $lvl = 0): string
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


}
