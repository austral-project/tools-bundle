<?php
/*
 * This file is part of the Austral Tools Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Austral\ToolsBundle\Tests\Tools;

use Austral\ToolsBundle\AustralTools;
use PHPUnit\Framework\TestCase;
use Random\RandomException;

/**
 * @covers \Austral\ToolsBundle\AustralTools
 */
class AustralToolsTest extends TestCase
{
  /**
   * @var string|null
   */
  private ?string $tmpDir = null;

  /**
   * setUp
   *
   * @return void
   * @throws RandomException
   */
  protected function setUp(): void
  {
    parent::setUp();
    $this->tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'austral_tools_tests_' . bin2hex(random_bytes(4));
    if (!is_dir($this->tmpDir)) {
      mkdir($this->tmpDir, 0777, true);
    }
    // Force APP_DEBUG to true by default for dump-related tests
    $_SERVER['APP_DEBUG'] = 1;
    unset($_SERVER['APP_DEBUG_TRACE_NO'], $_SERVER['APP_DEBUG_TRACE_NUMBER']);
  }

  /**
   * tearDown
   *
   * @return void
   */
  protected function tearDown(): void
  {
    // Clean up temp directory
    if ($this->tmpDir && is_dir($this->tmpDir)) {
      $files = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($this->tmpDir, \FilesystemIterator::SKIP_DOTS),
        \RecursiveIteratorIterator::CHILD_FIRST
      );
      foreach ($files as $fileinfo) {
        $fileinfo->isDir() ? rmdir($fileinfo->getPathname()) : @unlink($fileinfo->getPathname());
      }
      @rmdir($this->tmpDir);
    }
    parent::tearDown();
  }

  // ----------------------------------------
  // Debug flags
  // ----------------------------------------

  /**
   * testIsAppDebug
   *
   * @return void
   */
  public function testIsAppDebug(): void
  {
    $_SERVER['APP_DEBUG'] = 1;
    $this->assertTrue(AustralTools::isAppDebug());

    $_SERVER['APP_DEBUG'] = 0;
    $this->assertFalse(AustralTools::isAppDebug());
  }

  /**
   * testIsDebugTraceAndNumberTrace
   *
   * @return void
   */
  public function testIsDebugTraceAndNumberTrace(): void
  {
    // Default: no "APP_DEBUG_TRACE_NO"
    $this->assertTrue(AustralTools::isDebugTrace());

    $_SERVER['APP_DEBUG_TRACE_NO'] = 1;
    $this->assertFalse(AustralTools::isDebugTrace());

    unset($_SERVER['APP_DEBUG_TRACE_NO']);

    // Default trace number = 1
    $this->assertSame(1, AustralTools::numberTrace());

    $_SERVER['APP_DEBUG_TRACE_NUMBER'] = 3;
    $this->assertSame(3, AustralTools::numberTrace());
  }

  // ⚠️ Do not call these (they terminate the process).
  // public function testDumpAndDumpForceDoNotThrow(): void { AustralTools::dump(); AustralTools::dumpForce(); $this->assertTrue(true); }
  // public function testDumpKillWouldExit(): void { AustralTools::dumpKill(); }
  // public function testDumpKillForceWouldExit(): void { AustralTools::dumpKillForce(); }

  // ----------------------------------------
  // Array/object key access
  // ----------------------------------------

  /**
   * testGetValueByKeyAndValueByKeyOnArray
   *
   * @return void
   */
  public function testGetValueByKeyAndValueByKeyOnArray(): void
  {
    $data = ['name' => 'John', 'age' => 30];
    $this->assertSame('John', AustralTools::getValueByKey($data, 'name'));
    $this->assertSame(30, AustralTools::valueByKey($data, 'age'));
    $this->assertSame('default', AustralTools::valueByKey($data, 'unknown', 'default'));
  }

  /**
   * testValueByKeyOnObjectWithGetters
   *
   * @return void
   */
  public function testValueByKeyOnObjectWithGetters(): void
  {
    $obj = new class {
      private string $name = 'Alice';
      public function getName(): string { return $this->name; }
      // Simulates a "translate current" getter
      public function getTranslateCurrent(): object { return new class {
        private string $title = 'Bonjour';
        public function getTitle(): string { return $this->title; }
      }; }
    };

    // Lookup via getName()
    $this->assertSame('Alice', AustralTools::valueByKey($obj, 'name'));
    // Fallback via getTranslateCurrent()
    $this->assertSame('Bonjour', AustralTools::valueByKey($obj, 'title'));
    // Missing key => default
    $this->assertSame('def', AustralTools::valueByKey($obj, 'nope', 'def'));
  }

  /**
   * testUnsetInArray
   *
   * @return void
   */
  public function testUnsetInArray(): void
  {
    $arr = [
      'level1' => [
        'level2' => [
          'keep' => 1,
          'kill' => 2,
        ],
      ],
      'killRoot' => true,
    ];

    // Remove simple key
    $res = AustralTools::unsetInArray($arr, 'killRoot');
    $this->assertArrayNotHasKey('killRoot', $res);

    // Remove nested keys
    $res2 = AustralTools::unsetInArray($arr, ['level1', 'level2', 'kill']);
    $this->assertArrayHasKey('keep', $res2['level1']['level2']);
    $this->assertArrayNotHasKey('kill', $res2['level1']['level2']);
  }

  /**
   * testFirstAndLast
   *
   * @return void
   */
  public function testFirstAndLast(): void
  {
    $this->assertSame(1, AustralTools::first([1,2,3]));
    $this->assertSame(3, AustralTools::last([1,2,3]));
    $this->assertSame('def', AustralTools::first([], 'def'));
    $this->assertSame('def', AustralTools::last([], 'def'));
  }

  // ----------------------------------------
  // Random
  // ----------------------------------------

  /**
   * @throws RandomException
   */
  public function testRandomAllLettersNumbers(): void
  {
    $s = AustralTools::random(12, AustralTools::RANDOM_TYPE_ALL);
    $this->assertSame(12, strlen($s));
    $this->assertMatchesRegularExpression('/^[a-z0-9]+$/', $s);
  }

  /**
   * testRandomLettersOnly
   *
   * @return void
   * @throws RandomException
   */
  public function testRandomLettersOnly(): void
  {
    $s = AustralTools::random(10, AustralTools::RANDOM_TYPE_LETTERS);
    $this->assertSame(10, strlen($s));
    $this->assertMatchesRegularExpression('/^[a-z]+$/', $s);
  }

  /**
   * testRandomNumbersOnly
   *
   * @return void
   * @throws RandomException
   */
  public function testRandomNumbersOnly(): void
  {
    $s = AustralTools::random(8, AustralTools::RANDOM_TYPE_NUMBERS);
    $this->assertSame(8, strlen($s));
    $this->assertMatchesRegularExpression('/^[0-9]+$/', $s);
  }

  /**
   * testRandomInvalidReturnsEmpty
   *
   * @return void
   * @throws RandomException
   */
  public function testRandomInvalidReturnsEmpty(): void
  {
    $this->assertSame('', AustralTools::random(0, AustralTools::RANDOM_TYPE_ALL));
  }

  // ----------------------------------------
  // Strings / Slugs
  // ----------------------------------------

  /**
   * testRemoveAccents
   *
   * @return void
   */
  public function testRemoveAccents(): void
  {
    $this->assertSame('Elephant', AustralTools::removeAccents('Éléphant'));
    $this->assertSame('Bonjour', AustralTools::removeAccents('Bonjour!'));
  }

  /**
   * testStripFilter
   *
   * @return void
   */
  public function testStripFilter(): void
  {
    // Default stop-words: a, de, du, des, ou, et, en, les, la, le, l
    $this->assertSame('blog-site-sont-super', AustralTools::stripFilter('Le blog et le site sont super'));
  }

  /**
   * testStrip
   *
   * @return void
   */
  public function testStrip(): void
  {
    $this->assertSame('the-blog-is-very-large', AustralTools::strip('The   blog—is very  large!'));
    $this->assertSame('bonjour-a-tous', AustralTools::strip('Bonjour à tous'));
    // toLower = false
    $this->assertSame('Bonjour-a-tous', AustralTools::strip('Bonjour à tous', false));
  }

  /**
   * testCreateGetterSetterFunctionAndCreateFunction
   *
   * @return void
   */
  public function testCreateGetterSetterFunctionAndCreateFunction(): void
  {
    $this->assertSame('getFirstName', AustralTools::createGetterFunction('first_name'));
    $this->assertSame('setFirstName', AustralTools::createSetterFunction('first-name'));
    $this->assertSame('getUserIdX',   AustralTools::createFunction('user_id', 'get', 'X'));
  }

  /**
   * testGeneratorKey
   *
   * @return void
   */
  public function testGeneratorKey(): void
  {
    // With removeNoChars=false, extra chars are preserved
    $this->assertSame('bonjour-a-tous', AustralTools::generatorKey('Bonjour à tous'));
  }

  /**
   * testJoin
   *
   * @return void
   */
  public function testJoin(): void
  {
    if (DIRECTORY_SEPARATOR !== '/') {
      $this->markTestSkipped('join() will exit if not on UNIX.');
    }
    $this->assertSame('/var/www/html', AustralTools::join('/var/', '/www//', 'html/'));
    $this->assertSame('/a/b/c', AustralTools::join('///a', 'b/', '/c///'));
  }

  // ----------------------------------------
  // Sizes / Files / Images
  // ----------------------------------------

  /**
   * testHumanizeAndUnHumanizeSizeOnNumber
   *
   * @return void
   */
  public function testHumanizeAndUnHumanizeSizeOnNumber(): void
  {
    $this->assertSame('1 o', AustralTools::humanizeSize(1));
    $this->assertSame('1 Ko', AustralTools::humanizeSize(1024, null, 0));
    $this->assertSame('2 Ko', AustralTools::humanizeSize(2048, "Ko", 0));
    $this->assertSame(1024, AustralTools::unHumanizeSize('1k'));
    $this->assertSame(1024 * 1024, AustralTools::unHumanizeSize('1M'));
    $this->assertSame(1024 * 1024 * 1024, AustralTools::unHumanizeSize('1G'));
  }

  /**
   * testMimeTypeAndExtension
   *
   * @return void
   */
  public function testMimeTypeAndExtension(): void
  {
    $file = $this->tmpDir . DIRECTORY_SEPARATOR . 'sample.txt';
    file_put_contents($file, "hello");
    $ext = AustralTools::extension($file);
    $this->assertSame('txt', $ext);

    $mime = AustralTools::mimeType($file);
    $this->assertTrue($mime === 'text/plain' || $mime === null);
  }

  /**
   * testIsImageAndImageDimension
   *
   * @return void
   */
  public function testIsImageAndImageDimension(): void
  {
    // Generate a 1x1 PNG
    $pngBase64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAASsJTYQAAAAASUVORK5CYII=';
    $png = base64_decode($pngBase64);
    $path = $this->tmpDir . DIRECTORY_SEPARATOR . 'one.png';
    file_put_contents($path, $png);

    $this->assertTrue(AustralTools::isImage($path));
    $dims = AustralTools::imageDimension($path, true);
    $this->assertSame(['width' => 1, 'height' => 1], $dims);

    $dimsString = AustralTools::imageDimension($path, false);
    $this->assertSame('1 x 1', $dimsString);
  }

  // ----------------------------------------
  // Reflection: traits / interfaces
  // ----------------------------------------

  /**
   * testUsedClass
   *
   * @return void
   */
  public function testUsedClass(): void
  {
    $this->assertTrue(AustralTools::usedClass(\Austral\ToolsBundle\Tests\TestUsedClass\ChildClass::class, \Austral\ToolsBundle\Tests\TestUsedClass\MyTrait::class));
    $this->assertTrue(AustralTools::usedClass(\Austral\ToolsBundle\Tests\TestUsedClass\ChildClass::class, \Austral\ToolsBundle\Tests\TestUsedClass\ParentClass::class));
    $this->assertFalse(AustralTools::usedClass(\Austral\ToolsBundle\Tests\TestUsedClass\ChildClass::class, \ArrayObject::class));
  }

  /**
   * testUsedImplements
   *
   * @return void
   */
  public function testUsedImplements(): void
  {
    $this->assertTrue(AustralTools::usedImplements(\Austral\ToolsBundle\Tests\TestUsedClass\ImplementsChild::class, [\Austral\ToolsBundle\Tests\TestUsedClass\MyInterface::class]));
    $this->assertFalse(AustralTools::usedImplements(\Austral\ToolsBundle\Tests\TestUsedClass\ImplementsChild::class, [\Countable::class]));
  }

  // ----------------------------------------
  // Encryption
  // ----------------------------------------

  /**
   * testEncryptDecryptString
   *
   * @return void
   */
  public function testEncryptDecryptString(): void
  {
    $key = 'my_secret_key';
    $plain = 'Hello World!';
    $enc = AustralTools::encrypte($plain, $key);
    $dec = AustralTools::decrypte($enc, $key);
    $this->assertSame($plain, $dec);
  }

  /**
   * testEncryptDecryptArray
   *
   * @return void
   */
  public function testEncryptDecryptArray(): void
  {
    $key = 'another_key';
    $data = ['a' => 1, 'b' => 'x'];
    $enc = AustralTools::encrypte($data, $key);
    $dec = AustralTools::decrypte($enc, $key);
    $this->assertSame($data, $dec);
  }

  // ----------------------------------------
  // Placeholders / replacements
  // ----------------------------------------

  /**
   * testGetKeysInValue
   *
   * @return void
   */
  public function testGetKeysInValue(): void
  {
    $str = 'Hello %name%, id=%id%!';
    $keys = AustralTools::getKeysInValue($str);
    $this->assertSame(['%name%' => '%name%', '%id%' => '%id%'], $keys);
  }

  /**
   * testReplaceKeyByValue
   *
   * @return void
   */
  public function testReplaceKeyByValue(): void
  {
    $str = 'Hello %name%, id=%id%!';
    $replaced = AustralTools::replaceKeyByValue($str, ['name' => 'Alice', 'id' => 42]);
    $this->assertSame('Hello Alice, id=42!', $replaced);

    // Missing key => no replacement
    $str2 = 'Key:%missing%';
    $this->assertSame('Key:%missing%', AustralTools::replaceKeyByValue($str2, ['name' => 'Bob']));
  }

  // ----------------------------------------
  // Flatten / Unflatten / ToString
  // ----------------------------------------

  /**
   * testFlattenArray
   *
   * @return void
   */
  public function testFlattenArray(): void
  {
    $nested = [
      'a' => ['b' => 1, 'c' => [ 'd' => 2 ]],
      'e' => 3,
    ];
    $flat = AustralTools::flattenArray('.', $nested);
    $this->assertSame(1, $flat['a.b']);
    $this->assertSame(2, $flat['a.c.d']);
    $this->assertSame(3, $flat['e']);
  }

  /**
   * testArrayByFlatten
   *
   * @return void
   */
  public function testArrayByFlatten(): void
  {
    $flat = [
      'a.b'   => 1,
      'a.c.d' => 2,
      'e'     => 3,
    ];
    $nested = AustralTools::arrayByFlatten($flat, '.');
    $this->assertSame(1, $nested['a']['b']["element"]);
    $this->assertSame(2, $nested['a']['c']['d']["element"]);
    $this->assertSame(3, $nested['e']["element"]);
  }

  /**
   * testArrayToString
   *
   * @return void
   */
  public function testArrayToString(): void
  {
    $arr = ['title' => ['k1' => 'v1', 'k2' => 'v2'], 'other' => 'x'];
    $text = AustralTools::arrayToString($arr, false);

    $this->assertStringContainsString("title\n", $text);
    $this->assertStringContainsString("k1\nv1\n", $text);

    $html = AustralTools::arrayToString($arr, true);
    $this->assertStringContainsString("<ul", $html);
    $this->assertStringContainsString("class='austral-title-key'>title</span>", $html);
  }

  // ----------------------------------------
  // Memory usage helpers
  // ----------------------------------------

  /**
   * testMemoryUsageStartEnd
   *
   * @return void
   */
  public function testMemoryUsageStartEnd(): void
  {
    $name = 'test';
    AustralTools::memoryUsageStart($name);
    $junk = range(1, 500);
    $s = AustralTools::memoryUsageEnd($name, false);
    $this->assertStringEndsWith(' octets', $s);
    $num = (int) trim(str_replace(' octets', '', $s));
    $this->assertGreaterThanOrEqual(0, $num);
    unset($junk);
  }
}
