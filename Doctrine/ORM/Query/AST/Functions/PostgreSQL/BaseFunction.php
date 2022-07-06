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

namespace Austral\ToolsBundle\Doctrine\ORM\Query\AST\Functions\PostgreSQL;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

/**
 * Austral Base Function PostgreSQL ORM.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @abstract
 */
abstract class BaseFunction extends FunctionNode
{
  /**
   * @var string
   */
  protected string $functionPrototype;

  /**
   * @var array
   */
  protected array $nodesMapping = array();

  /**
   * @var array
   */
  protected array $nodes = array();

  /**
   * @return void
   */
  abstract protected function customiseFunction(): void;

  /**
   * @param string $functionPrototype
   *
   * @return void
   */
  protected function setFunctionPrototype(string $functionPrototype): void
  {
      $this->functionPrototype = $functionPrototype;
  }

  /**
   * @param string $parserMethod
   *
   * @return void
   */
  protected function addNodeMapping(string $parserMethod): void
  {
      $this->nodesMapping[] = $parserMethod;
  }

  /**
   * @param Parser $parser
   *
   * @return void
   * @throws \Doctrine\ORM\Query\QueryException
   */
  public function parse(Parser $parser): void
  {
    $this->customiseFunction();
    $parser->match(Lexer::T_IDENTIFIER);
    $parser->match(Lexer::T_OPEN_PARENTHESIS);
    $this->parseWithNodes($parser);
    $parser->match(Lexer::T_CLOSE_PARENTHESIS);
  }

  /**
   * @param Parser $parser
   *
   * @return void
   * @throws \Doctrine\ORM\Query\QueryException
   */
  protected function parseWithNodes(Parser $parser): void
  {
    $nodesMappingCount = \count($this->nodesMapping);
    $lastNode = $nodesMappingCount - 1;
    for ($i = 0; $i < $nodesMappingCount; $i++) {
      $parserMethod = $this->nodesMapping[$i];
      $this->nodes[$i] = $parser->{$parserMethod}();
      if ($i < $lastNode) {
          $parser->match(Lexer::T_COMMA);
      }
    }
  }

  /**
   * @param SqlWalker $sqlWalker
   *
   * @return string
   */
  public function getSql(SqlWalker $sqlWalker): string
  {
    $dispatched = array();
    foreach ($this->nodes as $node) {
        $dispatched[] = $node->dispatch($sqlWalker);
    }
    return \vsprintf($this->functionPrototype, $dispatched);
  }
}
