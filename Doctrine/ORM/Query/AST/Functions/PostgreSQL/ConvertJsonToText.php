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

/**
 * Austral Convert Json to text PostgreSQL.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @abstract
 */
class ConvertJsonToText extends BaseFunction
{
  /**
   * @return void
   */
  protected function customiseFunction(): void
  {
    $this->setFunctionPrototype('(%s ->> %s)');
    $this->addNodeMapping('StringPrimary');
    $this->addNodeMapping('StringPrimary');
  }
}
