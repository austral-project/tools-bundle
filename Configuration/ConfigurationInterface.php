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

/**
 * Austral Parameters Interface.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
interface ConfigurationInterface
{

  /**
   * @return array
   */
  public function all(): array;

  /**
   * @param string $key
   * @param null $default
   *
   * @return array|string
   */
  public function get(string $key, $default = null);

}