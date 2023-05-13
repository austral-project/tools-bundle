##php##
/*
 * This file is auto generate ##DATE##.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ##ENTITY_MANAGER_NAMESPACE##;

use ##ENTITY_NAMESPACE##\##NAME##;
use ##REPOSITORY_NAMESPACE##\##NAME##Repository;

use Austral\EntityBundle\EntityManager\EntityManager;

/**
 * App ##NAME## EntityManager.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class ##NAME##EntityManager extends EntityManager
{

  /**
   * @var ##NAME##Repository
   */
  protected $repository;

  /**
   * @param array $values
   *
   * @return ##NAME##
   */
  public function create(array $values = array()): ##NAME##
  {
    return parent::create($values);
  }

}

