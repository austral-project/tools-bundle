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

use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;

/**
 * Austral Random PostgreSQL.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
class Random extends FunctionNode
{
  public function parse(Parser $parser)
  {
    $parser->match(Lexer::T_IDENTIFIER);
    $parser->match(Lexer::T_OPEN_PARENTHESIS);
    $parser->match(Lexer::T_CLOSE_PARENTHESIS);
  }

  public function getSql(SqlWalker $sqlWalker)
  {
    return 'RANDOM()';
  }
}