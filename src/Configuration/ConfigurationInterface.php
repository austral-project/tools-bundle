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
 * Austral Configuration Interface.
 * @author Matthieu Beurel <matthieu@austral.dev>
 */
interface ConfigurationInterface
{

  /**
   * @return array<mixed>
   */
  public function all(): array;

  /**
   * @param string $key
   * @param mixed|null $default
   *
   * @return mixed
   */
  public function get(string $key, mixed $default = null): mixed;

}