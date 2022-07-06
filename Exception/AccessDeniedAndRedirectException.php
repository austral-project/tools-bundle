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
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class AccessDeniedAndRedirectException extends \RuntimeException
{

  /**
   * @var mixed|null
   */
  private $routeRedirect;

  /**
   * @var mixed
   */
  private $statusCode;

  public function __construct($statusCode, $message = null, $routeRedirect = null, \Exception $previous = null, $code = 0)
  {
    $this->statusCode = $statusCode;
    $this->routeRedirect = $routeRedirect;
    parent::__construct($message, $code, $previous);
  }

  /**
   * @return mixed
   */
  public function getStatusCode()
  {
    return $this->statusCode;
  }

  /**
   * @return mixed|null
   */
  public function getRouteRedirect()
  {
    return $this->routeRedirect;
  }
}
