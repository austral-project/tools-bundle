<?php

/*
 * This file is part of the Austral Tools Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\ToolsBundle\Exception;

/**
 * Austral AccessDeniedAndRedirectException.
 *
 * @author Matthieu Beurel <matthieu@austral.dev>
 *
 * @final
 */
class AccessDeniedAndRedirectException extends \RuntimeException
{
  /**
   * @var string|null
   */
  private ?string $routeRedirect;

  /**
   * @var int
   */
  private int $statusCode;

  /**
   * AccessDeniedAndRedirectException constructor
   *
   * @param int             $statusCode
   * @param string          $message
   * @param string|null     $routeRedirect
   * @param \Exception|null $previous
   * @param int             $code
   */
  public function __construct(int $statusCode, string $message, ?string $routeRedirect = null, ?\Exception $previous = null, int $code = 0)
  {
    $this->statusCode = $statusCode;
    $this->routeRedirect = $routeRedirect;
    parent::__construct($message, $code, $previous);
  }

  /**
   * @return int
   */
  public function getStatusCode(): int
  {
    return $this->statusCode;
  }

  /**
   * @return string|null
   */
  public function getRouteRedirect(): ?string
  {
    return $this->routeRedirect;
  }
}
