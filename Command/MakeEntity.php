<?php
/*
 * This file is part of the Austral Tools Bundle package.
 *
 * (c) Austral <support@austral.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Austral\ToolsBundle\Command;

use Austral\ToolsBundle\Command\Base\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use function Symfony\Component\String\u;

/**
 * Austral Services status checker Command.
 * @author Matthieu Beurel <matthieu@austral.dev>
 * @final
 */
class MakeEntity extends Command
{

  /**
   * @var string
   */
  protected static $defaultName = 'austral:make:entity';

  /**
   * @var string
   */
  protected string $titleCommande = "Generate Entity / EntityManager / Repository";

  /**
   * {@inheritdoc}
   */
  protected function configure()
  {
    $this
      ->setDefinition([
      ])
      ->setDescription($this->titleCommande)
      ->setHelp(<<<'EOF'
The <info>%command.name%</info> generate Entity / EntityManager / Repository file

  <info>php %command.full_name%</info>
EOF
      )
    ;
  }

  /**
   * @var string
   */
  protected string $projectPath;

  /**
   * @var array
   */
  protected array $paths = array();

  /**
   * @var array
   */
  protected array $bundlesList = array();

  /**
   * @var string
   */
  protected string $namespace;

  /**
   * @var string
   */
  protected string $skeletonPath;

  /**
   * @var InputInterface
   */
  protected InputInterface $input;

  /**
   * @var OutputInterface|null
   */
  protected ?OutputInterface $output;

  /**
   * @var array
   */
  protected array $templateParameters = array(
    "##php##"                               =>  "<?php",
    "##NAME##"                              =>  null,
    "##TABLE_NAME##"                        =>  null,

    "##ENTITY_NAMESPACE##"                  =>  null,
    "##ENTITY_USE##"                        =>  null,
    "##ENTITY_ANNOTATION##"                 =>  null,
    "##ENTITY_INTERFACE##"                  =>  null,
    "##ENTITY_TRAITS##"                     =>  null,
    "##ENTITY_CONSTRUCT##"                  =>  null,
    "##ENTITY_FIELDS##"                     =>  null,
    "##ENTITY_DEFAULT_FIELD_TO_STRING##"    =>  null,
    "##ENTITY_GETTER_SETTER##"              =>  null,

    "##ENTITY_MANAGER_NAMESPACE##"          =>  null,

    "##REPOSITORY_NAMESPACE##"              =>  null,
  );

  /**
   * @var array
   */
  protected array $propertiesUsed = array(
    "timestampable"     =>  false,
    "blockComponent"    =>  false,
    "translate"         =>  false,
    "domainFilter"      =>  false,
    "url"               =>  false,
    "treePage"          =>  false,
    "file"              =>  false,
    "cropper"           =>  false
  );

  /**
   * @param InputInterface $input
   * @param OutputInterface $output
   *
   * @throws \Exception
   */
  protected function executeCommand(InputInterface $input, OutputInterface $output)
  {
    $this->input = $input;
    $this->output = $output;

    $this->projectPath = $this->container->getParameter("kernel.project_dir");
    $this->paths = array(
      "Entity"          =>  "{$this->projectPath}/src/Entity",
      "EntityManager"   =>  "{$this->projectPath}/src/EntityManager",
      "Repository"      =>  "{$this->projectPath}/src/Repository"
    );
    $this->templateParameters["##ENTITY_NAMESPACE##"] = "App\\Entity";
    $this->templateParameters["##ENTITY_MANAGER_NAMESPACE##"] = "App\\EntityManager";
    $this->templateParameters["##REPOSITORY_NAMESPACE##"] = "App\\Repository";
    $this->bundlesList = $this->container->getParameter('kernel.bundles');

    foreach($this->bundlesList as $bundleName => $bundle)
    {
      if($bundleName === "AustralToolsBundle")
      {
        $reflectionClass = new \ReflectionClass($bundle);
        $bundleDir = dirname($reflectionClass->getFileName());
        $skeletonDir = "{$bundleDir}/Skeleton";
        if(file_exists($skeletonDir) && is_dir($skeletonDir))
        {
          $this->skeletonPath = $skeletonDir;
        }
      }
    }
    if($this->skeletonPath) {
      $this->create();
    }
  }

  /**
   * bundleIsEnabled
   *
   * @param $bundleName
   *
   * @return bool
   */
  protected function bundleIsEnabled($bundleName): bool
  {
    return array_key_exists($bundleName, $this->bundlesList);
  }


  /**
   * @throws \Exception
   */
  protected function create()
  {
    $name = $this->askName();

    $helper = $this->getHelper('question');
    $question = new ConfirmationQuestion('Entity has created and updated date ? (y|o)', false, "/^(y|o|1)/i");
    if ($helper->ask($this->input, $this->output, $question)) {
      $this->propertiesUsed["timestampable"] = true;
    }

    if($this->bundleIsEnabled("AustralEntityTranslateBundle"))
    {
      $this->askUsedAustralEntityTranslateBundle();
    }
    if($this->bundleIsEnabled("AustralContentBlockBundle"))
    {
      $this->askUsedAustralContentBlockBundle();
    }
    if($this->bundleIsEnabled("AustralHttpBundle"))
    {
      $this->askUsedAustralHttpBundle();
    }
    if($this->bundleIsEnabled("AustralEntityFileBundle"))
    {
      $this->askUsedAustralEntityFileBundle();
    }

    if($this->bundleIsEnabled("AustralSeoBundle"))
    {
      $this->askUsedAustralSeoBundle();
    }

    $this->generateFile($name);
    if($this->propertiesUsed['translate'] === true)
    {
      $this->generateFile($name, true);
    }

  }

  /**
   * generateFile
   * @param string $name
   * @param bool $isTranslate
   * @return $this
   */
  protected function generateFile(string $name, bool $isTranslate = false): MakeEntity
  {
    if($isTranslate)
    {
      $name .= "Translate";
    }
    $templateParameters = $this->templateParameters;

    $templateParameters["##NAME##"] = u($name)->camel()->title();
    $templateParameters["##TABLE_NAME##"] = "app_".u($name)->snake();
    $templateParameters["##DATE##"] = (new \DateTime())->format("Y-m-d H:i:s");

    $useClass = array();
    $traitsClass = array();
    $annotationsClass = array();
    $interfacesClass = array();
    $getterSetter = array();
    $fields = array();
    $construct = array();

    if($this->propertiesUsed["timestampable"])
    {
      $useClass[] = "use Austral\EntityBundle\Entity\Traits\EntityTimestampableTrait;";
      $traitsClass[] = "use EntityTimestampableTrait;";
    }

    $templateParameters['##ENTITY_DEFAULT_FIELD_TO_STRING##'] = 'return $this->id;';

    if($this->propertiesUsed["translate"] && $isTranslate === false)
    {
      $useClass[] = "use Austral\EntityBundle\Entity\Interfaces\TranslateMasterInterface;";
      $useClass[] = "use Austral\EntityTranslateBundle\Entity\Traits\EntityTranslateMasterTrait;";
      $useClass[] = "use Austral\EntityTranslateBundle\Annotation\Translate;";
      $useClass[] = "use Doctrine\Common\Collections\Collection;";
      $useClass[] = "use Doctrine\Common\Collections\ArrayCollection;";
      $interfacesClass[] = "TranslateMasterInterface";
      $annotationsClass[] = "* @Translate(relationClass=\"{$templateParameters["##ENTITY_NAMESPACE##"]}\\{$name}Translate\")";
      $traitsClass[] = "use EntityTranslateMasterTrait;";

      $fields[] = '/**
   * @ORM\OneToMany(targetEntity="'.$templateParameters["##ENTITY_NAMESPACE##"].'\\'.$name.'Translate", mappedBy="master", cascade={"persist", "remove"})
   */
  protected Collection $translates;';
      $construct[] = '$this->translates = new ArrayCollection();';
      $templateParameters['##ENTITY_DEFAULT_FIELD_TO_STRING##'] = 'return $this->getTranslateCurrent() ? $this->getTranslateCurrent()->__toString() : "";';

      $getterSetter[] = '/**
   * @return '.$name.'Translate
   * @throws \Exception
   */
  public function createNewTranslateByLanguage(): '.$name.'Translate
  {
    $translate = new '.$name.'Translate();
    $translate->setMaster($this);
    $translate->setIsReferent(count($this->getTranslatesByLanguage()) > 0);
    $translate->setLanguage($this->getLanguageCurrent());
    $this->addTranslateByLanguage($translate);
    return $translate;
  }';

    }
    elseif($isTranslate)
    {
      $useClass[] = "use Austral\EntityBundle\Entity\Interfaces\TranslateChildInterface;";
      $useClass[] = "use Austral\EntityTranslateBundle\Entity\Traits\EntityTranslateChildTrait;";
      $interfacesClass[] = "TranslateChildInterface";
      $traitsClass[] = "use EntityTranslateChildTrait;";
    }

    if($this->propertiesUsed["blockComponent"])
    {
      $useClass[] = "use Austral\EntityBundle\Entity\Interfaces\ComponentsInterface;";
      $interfacesClass[] = "ComponentsInterface";

      if($this->propertiesUsed["translate"] && $isTranslate === false)
      {
        $useClass[] = "use Austral\EntityTranslateBundle\Entity\Traits\EntityTranslateMasterComponentsTrait;";
        $traitsClass[] = "use EntityTranslateMasterComponentsTrait;";
      }
      else
      {
        $useClass[] = "use Austral\ContentBlockBundle\Entity\Traits\EntityComponentsTrait;";
        $traitsClass[] = "use EntityComponentsTrait;";
      }
    }

    if($isTranslate === false)
    {
      if($this->propertiesUsed['domainFilter'])
      {
        $useClass[] = "use Austral\HttpBundle\Annotation\DomainFilter;";
        $annotationsClass[] = "* @DomainFilter(forAllDomainEnabled=".($this->propertiesUsed['domainFilter']['forAllDomainEnabled'] ? "true" : "false").", autoDomainId=".($this->propertiesUsed['domainFilter']['autoDomainId'] ? "true" : "false").")";
        $useClass[] = "use Austral\HttpBundle\Entity\Traits\FilterByDomainTrait;";
        $traitsClass[] = "use FilterByDomainTrait;";
      }

      if($this->propertiesUsed['file'])
      {
        $useClass[] = "use Austral\EntityBundle\Entity\Interfaces\FileInterface;";
        $useClass[] = "use Austral\EntityFileBundle\Entity\Traits\EntityFileTrait;";
        $interfacesClass[] = "FileInterface";
        $traitsClass[] = "use EntityFileTrait;";
      }

      if($this->propertiesUsed["cropper"])
      {
        if($this->propertiesUsed["translate"] && $isTranslate === false)
        {
          $useClass[] = "use Austral\EntityTranslateBundle\Entity\Traits\EntityTranslateMasterFileCropperTrait;";
          $traitsClass[] = "use EntityTranslateMasterFileCropperTrait;";
        }
        else
        {
          $useClass[] = "use Austral\EntityFileBundle\Entity\Traits\EntityFileCropperTrait;";
          $traitsClass[] = "use EntityFileCropperTrait;";
        }
      }

      if($this->propertiesUsed['url'])
      {
        $useClass[] = "use Austral\SeoBundle\Annotation\UrlParameterObject;";
        $annotationsClass[] = "* @UrlParameterObject(methodGenerateLastPath=\"stringToLastPath\")";
        $getterSetter[] = '/**
   * @return string|null
   * @throws \Exception
   */
  public function stringToLastPath(): ?string
  {
    return $this->__toString();
  }';
        $useClass[] = "use Austral\SeoBundle\Entity\Traits\UrlParameterTrait;";
        $traitsClass[] = "use UrlParameterTrait;";
      }
      if($this->propertiesUsed['treePage'])
      {
        $useClass[] = "use Austral\SeoBundle\Entity\Interfaces\TreePageInterface;";
        $useClass[] = "use Austral\SeoBundle\Entity\Traits\TreePageParentTrait;";
        $interfacesClass[] = "TreePageInterface";
        $traitsClass[] = "use TreePageParentTrait;";
      }
    }

    if($useClass) {
      $templateParameters["##ENTITY_USE##"] = "
".implode("
", $useClass)."
";
    }

    if($interfacesClass) {
      $templateParameters["##ENTITY_INTERFACE##"] = ", ".implode(", ", $interfacesClass);
    }

    if($annotationsClass) {
      $templateParameters["##ENTITY_ANNOTATION##"] = "
 ".implode("
 ", $annotationsClass)."
 ";
    }
    else
    {
      $templateParameters["##ENTITY_ANNOTATION##"] = " *";
    }

    if($traitsClass) {
      $templateParameters["##ENTITY_TRAITS##"] = "
  ".implode("
  ", $traitsClass)."
  ";
    }

    if($getterSetter) {
      $templateParameters["##ENTITY_GETTER_SETTER##"] = "
  ".implode("

  ", $getterSetter)."
  ";
    }

    if($fields) {
      $templateParameters["##ENTITY_FIELDS##"] = "
  ".implode("
  ", $fields)."
  ";
    }

    if($construct) {
      $templateParameters["##ENTITY_CONSTRUCT##"] = implode("
    ", $construct);
    }

    $filePathEntity = "{$this->paths["Entity"]}/{$templateParameters["##NAME##"]}.php";
    $fileContent = file_get_contents("{$this->skeletonPath}/Entity.php");
    $fileContent = str_replace(array_keys($templateParameters), array_values($templateParameters), $fileContent);
    if($this->writeFile($filePathEntity, "Entity/{$templateParameters["##NAME##"]}.php"))
    {
      file_put_contents($filePathEntity, $fileContent);
    }

    if($isTranslate === false)
    {
      $filePathEntityManager = "{$this->paths["EntityManager"]}/{$templateParameters["##NAME##"]}EntityManager.php";
      $fileContent = file_get_contents("{$this->skeletonPath}/EntityManager.php");
      $fileContent = str_replace(array_keys($templateParameters), array_values($templateParameters), $fileContent);
      if($this->writeFile($filePathEntityManager, "EntityManager/{$templateParameters["##NAME##"]}EntityManager.php"))
      {
        file_put_contents($filePathEntityManager, $fileContent);
      }
    }

    $filePathRepository = "{$this->paths["Repository"]}/{$templateParameters["##NAME##"]}Repository.php";
    $fileContent = file_get_contents("{$this->skeletonPath}/Repository.php");
    $fileContent = str_replace(array_keys($templateParameters), array_values($templateParameters), $fileContent);
    if($this->writeFile($filePathRepository, "Repository/{$templateParameters["##NAME##"]}Repository.php"))
    {
      file_put_contents($filePathRepository, $fileContent);
    }

    return $this;
  }

  public function writeFile($filePath, $name)
  {
    $write = true;
    if(file_exists($filePath))
    {
      $write = false;
      $helper = $this->getHelper('question');
      $question = new ConfirmationQuestion("File {$name} already exists, do you want to overwrite the file ? (y|o)", false, "/^(y|o|1)/i");
      if ($helper->ask($this->input, $this->output, $question)) {
        $write = true;
      }
    }
    return $write;
  }


  /**
   * askName
   * @return string
   * @throws \Exception
   */
  protected function askName(): string
  {
    $helper = $this->getHelper('question');
    $question = new ConfirmationQuestion('Entity Name ?', false);
    $question->setNormalizer(function ($value) {
      return $value ? trim($value) : '';
    });
    if (!$name = $helper->ask($this->input, $this->output, $question)) {
      throw new \Exception("Name is required");
    }
    return $name;
  }

  /**
   * askUsedAustralEntityTranslateBundle
   * @return void
   */
  protected function askUsedAustralEntityTranslateBundle()
  {
    $helper = $this->getHelper('question');
    $question = new ConfirmationQuestion('This Entity is translatable ? (y|o)', false, "/^(y|o|1)/i");
    if ($helper->ask($this->input, $this->output, $question)) {
      $this->propertiesUsed["translate"] = true;
    }
  }

  /**
   * askUsedAustralContentBlockBundle
   * @return void
   */
  protected function askUsedAustralContentBlockBundle()
  {
    $helper = $this->getHelper('question');
    $question = new ConfirmationQuestion('This Entity has BlockComponent ? (y|o)', false, "/^(y|o|1)/i");
    if ($helper->ask($this->input, $this->output, $question)) {
      $this->propertiesUsed["blockComponent"] = true;
    }
  }

  /**
   * askUsedAustralHttpBundle
   * @return void
   */
  protected function askUsedAustralHttpBundle()
  {
    $helper = $this->getHelper('question');
    $question = new ConfirmationQuestion('This Entity has a link with domain ? (y|o)', false, "/^(y|o|1)/i");
    if ($helper->ask($this->input, $this->output, $question)) {
      $this->propertiesUsed["domainFilter"] = array(
        "forAllDomainEnabled" =>  false,
        "autoDomainId"        =>  false
      );

      $questionAllDomain = new ConfirmationQuestion('All domain is enabled ? (y|o)', false, "/^(y|o|1)/i");
      $this->propertiesUsed["domainFilter"]["forAllDomainEnabled"] = $helper->ask($this->input, $this->output, $questionAllDomain);

      $questionAllDomain = new ConfirmationQuestion('The domain is attached automatically ? (y|o)', false, "/^(y|o|1)/i");
      $this->propertiesUsed["domainFilter"]["autoDomainId"] = $helper->ask($this->input, $this->output, $questionAllDomain);
    }
  }

  /**
   * askUsedAustralEntityFileBundle
   * @return void
   */
  protected function askUsedAustralEntityFileBundle()
  {
    $helper = $this->getHelper('question');
    $question = new ConfirmationQuestion('This Entity has files ? (y|o)', false, "/^(y|o|1)/i");
    if ($helper->ask($this->input, $this->output, $question)) {
      $this->propertiesUsed["file"] = true;
      $question = new ConfirmationQuestion('This files use crop  ? (y|o)', false, "/^(y|o|1)/i");
      if ($helper->ask($this->input, $this->output, $question)) {
        $this->propertiesUsed["cropper"] = true;
      }
    }
  }

  /**
   * askUsedAustralSeoBundle
   * @return void
   */
  protected function askUsedAustralSeoBundle()
  {
    $helper = $this->getHelper('question');
    $question = new ConfirmationQuestion('This Entity has a url ? (y|o)', false, "/^(y|o|1)/i");
    if ($helper->ask($this->input, $this->output, $question)) {
      $this->propertiesUsed["url"] = true;
      $helper = $this->getHelper('question');
      $question = new ConfirmationQuestion('This url has a link with WebsitePage ? (y|o)', false, "/^(y|o|1)/i");
      if ($helper->ask($this->input, $this->output, $question)) {
        $this->propertiesUsed["treePage"] = true;
      }
    }
  }



}