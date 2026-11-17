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

use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Filesystem\Filesystem;
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
  protected string $titleCommande = "Generate Entity / EntityManager / Repository";

  /**
   * {@inheritdoc}
   */
  protected function configure(): void
  {
    $this
      ->setDefinition([
        new InputOption('--all-properties', '-a', InputOption::VALUE_NONE, 'Enabled all properties'),
        new InputOption('--dirname', '-d', InputOption::VALUE_OPTIONAL, 'Defined dir'),
        new InputOption('--path-file', '-p', InputOption::VALUE_OPTIONAL, 'Path file'),
        new InputOption('--namespace', '', InputOption::VALUE_OPTIONAL, 'Namespace'),
      ])
      ->addArgument("name", InputArgument::OPTIONAL, "Entity name")
      ->setDescription($this->titleCommande)
      ->setHelp(<<<'EOF'
The <info>%command.name%</info> generate Entity / EntityManager / Repository file

  <info>php %command.full_name%</info>
EOF
      )
    ;
  }

  /**
   * @var array<mixed>
   */
  protected array $paths = [];

  /**
   * @var array<mixed>
   */
  protected array $bundlesList = [];

  /**
   * @var string|null
   */
  protected ?string $namespace = null;

  /**
   * @var string|null
   */
  protected ?string $skeletonPath = null;

  /**
   * @var InputInterface|null
   */
  protected ?InputInterface $input = null;

  /**
   * @var OutputInterface|null
   */
  protected ?OutputInterface $output = null;

  /**
   * @var array<string, string|null>
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
   * @var array<string, bool|array<string, bool>>
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
  protected function executeCommand(InputInterface $input, OutputInterface $output): void
  {
    $this->input = $input;
    $this->output = $output;

    $dirname = $this->input->getOption("dirname");
    if(!$pathFile = $this->input->getOption("path-file"))
    {
      $kernelProjectDir = $this->container?->getParameter("kernel.project_dir") ?? "";
      $kernelProjectDir = !is_string($kernelProjectDir) ? "" : $kernelProjectDir;
      $pathFile = "{$kernelProjectDir}/src";
    }
    if(!$namespace = $this->input->getOption("namespace"))
    {
      $namespace = "App";
    }

    $this->paths = array(
      "Entity"          =>  "{$pathFile}/Entity".($dirname ? "/{$dirname}" : ""),
      "EntityManager"   =>  "{$pathFile}/EntityManager".($dirname ? "/{$dirname}" : ""),
      "Repository"      =>  "{$pathFile}/Repository".($dirname ? "/{$dirname}" : "")
    );
    $this->templateParameters["##ENTITY_NAMESPACE##"] = "{$namespace}\\Entity".($dirname ? "\\{$dirname}" : "");
    $this->templateParameters["##ENTITY_MANAGER_NAMESPACE##"] = "{$namespace}\\EntityManager".($dirname ? "\\{$dirname}" : "");
    $this->templateParameters["##REPOSITORY_NAMESPACE##"] = "{$namespace}\\Repository".($dirname ? "\\{$dirname}" : "");


    $bundlesList = $this->container?->getParameter("kernel.bundles") ?? "";
    $bundlesList = !is_array($bundlesList) ? [] : $bundlesList;
    $this->bundlesList = $bundlesList;

    foreach($this->bundlesList as $bundleName => $bundle)
    {
      if($bundleName === "AustralToolsBundle")
      {
        $reflectionClass = new \ReflectionClass($bundle);
        if($reflectionFileName = $reflectionClass->getFileName())
        {
          $bundleDir = dirname($reflectionFileName);
          $skeletonDir = "{$bundleDir}/Skeleton";
          if(file_exists($skeletonDir) && is_dir($skeletonDir))
          {
            $this->skeletonPath = $skeletonDir;
          }
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
   * @param string $bundleName
   *
   * @return bool
   */
  protected function bundleIsEnabled(string $bundleName): bool
  {
    return array_key_exists($bundleName, $this->bundlesList);
  }


  /**
   * @throws \Exception
   */
  protected function create(): void
  {
    if(!$name = $this->input?->getArgument("name"))
    {
      $name = $this->askName();
    }

    if($this->input?->getOption("all-properties"))
    {
      /**
       * @var string $key
       * @var mixed $value
       */
      foreach ($this->propertiesUsed as $key => $value)
      {
        if($key === "domainFilter")
        {
          $this->propertiesUsed[$key] = array(
            "forAllDomainEnabled" =>  true,
            "autoDomainId"        =>  true
          );
        }
        else
        {
          $this->propertiesUsed[$key] = true;
        }
      }
    }
    else
    {
      $this->askConfirmationQuestion(
        'Entity has created and updated date ? (y|o)',
        "timestampable"
      );
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
    }

    $filesystem = new Filesystem();
    foreach ($this->paths as $path)
    {
      if(!$filesystem->exists($path))
      {
        $filesystem->mkdir($path);
      }
    }

    $this->generateFile($name);
    if($this->propertiesUsed['translate'] === true)
    {
      $this->generateFile($name, true);
    }

  }


  /**
   * askQuestion
   *
   * @param string $entitled
   * @param string|null $property
   * @return bool
   */
  protected function askConfirmationQuestion(string $entitled, ?string $property = null): bool
  {
    $helper = $this->getHelper('question');
    if($helper instanceof QuestionHelper && $this->input && $this->output)
    {
      if ($helper->ask($this->input, $this->output, new ConfirmationQuestion($entitled, false, "/^(y|o|1)/i"))) {
        if($property !== null)
        {
          $this->propertiesUsed[$property] = true;
        }
        return true;
      }
    }
    if($property !== null)
    {
      $this->propertiesUsed[$property] = false;
    }
    return false;
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

    $useClass = [];
    $traitsClass = [];
    $annotationsClass = [];
    $interfacesClass = [];
    $getterSetter = [];
    $fields = [];
    $construct = [];

    if($this->propertiesUsed["timestampable"])
    {
      $useClass[] = "use Austral\EntityBundle\Entity\Traits\EntityTimestampableTrait;";
      $traitsClass[] = "use EntityTimestampableTrait;";
    }

    $templateParameters['##ENTITY_DEFAULT_FIELD_TO_STRING##'] = 'return $this->id;';

    if($this->propertiesUsed["translate"] && $isTranslate === false)
    {
      $useClass[] = "use Austral\Contracts\Translate\TranslateMasterInterface;";
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
      $useClass[] = "use Austral\Contracts\Translate\TranslateMasterInterface;";
      $useClass[] = "use Austral\Contracts\Translate\TranslateChildInterface;";
      $useClass[] = "use Austral\EntityTranslateBundle\Entity\Traits\EntityTranslateChildTrait;";
      $interfacesClass[] = "TranslateChildInterface";
      $traitsClass[] = "use EntityTranslateChildTrait;";
      $nameMaster = str_replace("Translate", "", $name);
      $fields[] = '/**
   * @var '.$nameMaster.'|TranslateMasterInterface
   *
   * @ORM\ManyToOne(targetEntity="'.$templateParameters["##ENTITY_NAMESPACE##"].'\\'.$nameMaster.'", inversedBy="translates", cascade={"persist", "remove"})
   * @ORM\JoinColumn(name="master_id", referencedColumnName="id")
   */
  protected TranslateMasterInterface $master;';

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
      if(is_array($this->propertiesUsed['domainFilter']))
      {
        $useClass[] = "use Austral\HttpBundle\Annotation\DomainFilter;";
        $annotationsClass[] = "* @DomainFilter(forAllDomainEnabled=".($this->propertiesUsed['domainFilter']['forAllDomainEnabled'] ? "true" : "false").", autoDomainId=".($this->propertiesUsed['domainFilter']['autoDomainId'] ? "true" : "false").")";
        $useClass[] = "use Austral\HttpBundle\Entity\Traits\FilterByDomainTrait;";
        $traitsClass[] = "use FilterByDomainTrait;";
      }

      if($this->propertiesUsed['file'])
      {
        $useClass[] = "use Austral\Contracts\File\FileInterface;";
        $useClass[] = "use Austral\EntityFileBundle\Entity\Traits\EntityFileTrait;";
        $interfacesClass[] = "FileInterface";
        $traitsClass[] = "use EntityFileTrait;";
      }

      if($this->propertiesUsed["cropper"])
      {
        if($this->propertiesUsed["translate"])
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
    elseif($isTranslate)
    {
      if($this->propertiesUsed["cropper"])
      {
        $useClass[] = "use Austral\EntityFileBundle\Entity\Traits\EntityFileCropperTrait;";
        $traitsClass[] = "use EntityFileCropperTrait;";
      }
    }

    if($this->propertiesUsed['file'])
    {
      $useClass[] = "use Austral\EntityFileBundle\Annotation as AustralFile;";
    }

    if(count($useClass) > 0) {
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
    if(is_string($fileContent))
    {
      $fileContent = str_replace(array_keys($templateParameters), array_values($templateParameters), $fileContent);
      if($this->writeFile($filePathEntity, "Entity/{$templateParameters["##NAME##"]}.php"))
      {
        file_put_contents($filePathEntity, $fileContent);
      }
    }

    if($isTranslate === false)
    {
      $filePathEntityManager = "{$this->paths["EntityManager"]}/{$templateParameters["##NAME##"]}EntityManager.php";
      $fileContent = file_get_contents("{$this->skeletonPath}/EntityManager.php");
      if(is_string($fileContent))
      {
        $fileContent = str_replace(array_keys($templateParameters), array_values($templateParameters), $fileContent);
        if ($this->writeFile($filePathEntityManager, "EntityManager/{$templateParameters["##NAME##"]}EntityManager.php"))
        {
          file_put_contents($filePathEntityManager, $fileContent);
        }
      }
    }

    $filePathRepository = "{$this->paths["Repository"]}/{$templateParameters["##NAME##"]}Repository.php";
    $fileContent = file_get_contents("{$this->skeletonPath}/Repository.php");
    if(is_string($fileContent))
    {
      $fileContent = str_replace(array_keys($templateParameters), array_values($templateParameters), $fileContent);
      if($this->writeFile($filePathRepository, "Repository/{$templateParameters["##NAME##"]}Repository.php"))
      {
        file_put_contents($filePathRepository, $fileContent);
      }
    }

    return $this;
  }

  /**
   * writeFile
   *
   * @param string $filePath
   * @param string $name
   * @return bool
   */
  public function writeFile(string $filePath, string $name): bool
  {
    if(file_exists($filePath))
    {
      return $this->askConfirmationQuestion("File {$name} already exists, do you want to overwrite the file ? (y|o)");
    }
    return true;
  }


  /**
   * askName
   * @return string
   * @throws \Exception
   */
  protected function askName(): string
  {
    $helper = $this->getHelper('question');
    $name = "";
    if($helper instanceof QuestionHelper && $this->input && $this->output)
    {
      $question = new ConfirmationQuestion('Entity Name ?', false);
      $question->setNormalizer(function ($value) {
        return $value ? trim($value) : '';
      });
      if (!$name = $helper->ask($this->input, $this->output, $question)) {
        throw new \Exception("Name is required");
      }
    }
    return $name;
  }

  /**
   * askUsedAustralEntityTranslateBundle
   * @return void
   */
  protected function askUsedAustralEntityTranslateBundle(): void
  {
    $this->askConfirmationQuestion(
      'This Entity is translatable ? (y|o)',
      "translate"
    );
  }

  /**
   * askUsedAustralContentBlockBundle
   * @return void
   */
  protected function askUsedAustralContentBlockBundle(): void
  {
    $this->askConfirmationQuestion(
      'This Entity has BlockComponent ? (y|o)',
      "blockComponent"
    );
  }

  /**
   * askUsedAustralHttpBundle
   * @return void
   */
  protected function askUsedAustralHttpBundle(): void
  {
    if($this->askConfirmationQuestion('This Entity has a link with domain ? (y|o)'))
    {
      $this->propertiesUsed["domainFilter"] = array(
        "forAllDomainEnabled" =>  false,
        "autoDomainId"        =>  false
      );
      $this->propertiesUsed["domainFilter"]["forAllDomainEnabled"] = $this->askConfirmationQuestion('All domain is enabled ? (y|o)');
      $this->propertiesUsed["domainFilter"]["autoDomainId"] = $this->askConfirmationQuestion('The domain is attached automatically ? (y|o)');
    }
  }

  /**
   * askUsedAustralEntityFileBundle
   * @return void
   */
  protected function askUsedAustralEntityFileBundle(): void
  {
    if($this->askConfirmationQuestion('This Entity has files ? (y|o)'))
    {
      $this->propertiesUsed["file"] = true;
      $this->propertiesUsed["cropper"] = $this->askConfirmationQuestion('This files use crop ? (y|o)');
    }
  }

  /**
   * askUsedAustralSeoBundle
   * @return void
   */
  protected function askUsedAustralSeoBundle(): void
  {
    if($this->askConfirmationQuestion('This Entity has a url ? (y|o)'))
    {
      $this->propertiesUsed["url"] = true;
      $this->propertiesUsed["treePage"] = $this->askConfirmationQuestion('This url has a link with WebsitePage ? (y|o)');
    }
  }



}