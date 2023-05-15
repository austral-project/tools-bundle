##php##
/*
 * This file is auto generate ##DATE##.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ##ENTITY_NAMESPACE##;

use Austral\EntityBundle\Entity\Entity;
use Austral\EntityBundle\Entity\EntityInterface;##ENTITY_USE##
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * App ##NAME## Entity.
 * @author Matthieu Beurel <matthieu@austral.dev>
##ENTITY_ANNOTATION##
 * @ORM\Table(name="##TABLE_NAME##")
 * @ORM\Entity(repositoryClass="##REPOSITORY_NAMESPACE##\##NAME##Repository")
 * @final
 */
class ##NAME## extends Entity implements EntityInterface##ENTITY_INTERFACE##
{
##ENTITY_TRAITS##
  /**
   * @var string
   * @ORM\Column(name="id", type="string", length=40)
   * @ORM\Id
   */
  protected $id;
##ENTITY_FIELDS##
  /**
   * ##NAME## constructor
   * @throws \Exception
   */
  public function __construct()
  {
    parent::__construct();
    $this->id = Uuid::uuid4()->toString();
    ##ENTITY_CONSTRUCT##
  }

  /**
   * @return string
   * @throws \Exception
   */
  public function __toString()
  {
    ##ENTITY_DEFAULT_FIELD_TO_STRING##
  }
##ENTITY_GETTER_SETTER##
}
