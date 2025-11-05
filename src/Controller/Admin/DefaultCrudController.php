<?php

namespace AdminCore\Controller\Admin;

use AdminCore\Controller\Admin\FormField\CKEditorField;
use AdminCore\Controller\Admin\Trait\EntityTypeRedirectActionsTrait;
use AdminCore\Entity\Main;
use AdminCore\Entity\Product;
use AdminCore\Entity\SectionLink;
use AdminCore\Entity\SectionType;
use AdminCore\Enum\Statuses;
use AdminCore\Service\SectionPathGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RequestStack;
use Vich\UploaderBundle\Form\Type\VichImageType;
use Vich\UploaderBundle\Mapping\Annotation\UploadableField;

class DefaultCrudController extends AbstractCrudController
{
    use EntityTypeRedirectActionsTrait;
    public function __construct(
        private RequestStack             $requestStack,
        protected EntityManagerInterface $entityManager,
        protected AdminUrlGenerator $adminUrlGenerator,
        private SectionPathGenerator $pathGenerator,
    )
    {}
    protected function getAdminUrlGenerator(): AdminUrlGenerator
    {
        return $this->adminUrlGenerator;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->showEntityActionsInlined(true)
            ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('title')
            ->add('status')
            ;
    }


    public static function getEntityFqcn(): string
    {
        return '';
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions = parent::configureActions($actions);

        // добавляем редиректы на основании типа сущности
        $actions = $this->configureEntityTypeRedirectActions($actions);

        $request = $this->requestStack->getCurrentRequest();
        $parentId = $request?->query->getInt('parent_id');

        $actions = $actions->remove(Crud::PAGE_INDEX, Action::NEW);

        $availableTypes = [];

        if ($parentId) {
            $parent = $this->entityManager->getRepository(Main::class)->find($parentId);
            if (!$parent) {
                throw new \RuntimeException("Main with ID $parentId not found");
            }

            $parentType = $parent->getEntityType();

            $sectionLinks = $this->entityManager->getRepository(SectionLink::class)
                ->findBy(['parentType' => $parentType]);

            foreach ($sectionLinks as $link) {
                $availableTypes[] = $link->getChildType();
            }
        } else {
            $availableTypes = $this->entityManager
                ->getRepository(SectionType::class)
                ->findAll();
        }

        foreach ($availableTypes as $type) {
            $controller = $type->getCrudControllerClass();
            $entityClass = $type->getEntityClass();

            if (!$controller || !$entityClass) {
                continue;
            }

            $url = $this->adminUrlGenerator
                ->setController($controller)
                ->setAction(Action::NEW)
                ->set('parent_id', $parentId)
                ->generateUrl()//                ->setDashboard()
            ;

            $label = '+ ' . $type->getName();

            $customNewAction = Action::new('add_' . $type->getCode(), $label)
                ->linkToUrl($url)
                ->setCssClass('btn btn-outline-primary')
                ->createAsGlobalAction();

            $actions = $actions->add(Crud::PAGE_INDEX, $customNewAction);
        }
        // добавляем кастомные NEW, если нужно
        return $actions;
    }


    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $request = $this->requestStack->getCurrentRequest();
        $parentId = $request?->query->get('parent_id');

        $entityClass = $entityDto->getFqcn();
        $metadata = $this->entityManager->getClassMetadata($entityClass);

//        dd($entityDto->getInstance());
        $isParentAvailable = $metadata->hasAssociation('parent')
            || property_exists($entityClass, 'parent')
            || (method_exists($entityDto, 'getInstance') && ($entityDto->getInstance() !== null && property_exists($entityDto->getInstance(), 'parent')));

        if ($isParentAvailable) {
            if ($parentId !== null) {
                $qb->andWhere('entity.parent = :parentId')
                    ->setParameter('parentId', $parentId);
            } else {
                $qb->andWhere('entity.parent IS NULL');
            }
        }
        $qb->andWhere('entity.status != :status')
            ->setParameter(':status', Statuses::Deleted);
        return $qb;
    }

    public function configureFields(string $pageName): iterable
    {
        // System tab
        yield FormField::addTab('System');

        foreach ($this->getSystemFields() as $field) {
            yield $field;
        }

        // Main tab
        yield FormField::addTab('Main');

        foreach ($this->getMainFields($pageName) as $field) {
            yield $field;
        }

//        dd($this->getImageFields());
        // Images tab (если есть поля изображений)
        if ($this->hasImageFields()) {
            yield FormField::addTab('Images');

            foreach ($this->getImageFields() as $field) {
                yield $field;
            }
        }

        // Meta tab
        yield FormField::addTab('Meta');

        foreach ($this->getMetaFields() as $field) {
            yield $field;
        }
    }

    protected function getSystemFields(): iterable
    {
        $request = $this->requestStack->getCurrentRequest();
        $parentId = $request?->query->get('parent_id', 0);

        $parentField = IdField::new('parent', 'Родитель')->hideOnIndex();
        $parentField->setFormTypeOption('data', $parentId);


        yield $parentField;
        yield ChoiceField::new('status', 'Статус')
            ->setChoices(Statuses::cases())
            ->setFormTypeOption('choice_label', 'name')
            ->setFormTypeOption('choice_value', 'value');
        yield IdField::new('ord', 'Порядок')->hideOnIndex();
    }

    protected function getMainFields(string $pageName): iterable
    {
        $entityFqcn = $this->getEntityFqcn();
        $entityFields = $this->getEntityFields($entityFqcn);
//        dd($entityFields);
        foreach ($entityFields as $fieldName => $fieldConfig)
        {
            // Пропускаем системные, meta и image поля
            if (in_array($fieldName, [
                'id', 'parent', 'created_at', 'updated_at', 'ord', 'status',
                'metaTitle', 'metaKeywords', 'metaDescription',
                'image', 'logo', 'preview',
                'variants'
            ])) {
                continue;
            }

            yield $this->createFieldByType($fieldName, $fieldConfig);
        }
    }


    /**
     * @throws \ReflectionException
     */
    protected function getImageFields(): iterable
    {
        $fields = $this->getVichUploadableFields($this->getEntityFqcn());
//        dd($fields,$this->getVichImageFieldNames($fields));
        foreach ($fields as $baseField) {
            yield Field::new($baseField , ucfirst($baseField))
                ->setFormType(VichImageType::class)
                ->onlyOnForms()
                ->setLabel('Загрузить ' . ucfirst($baseField));

            yield ImageField::new($baseField)
                ->setBasePath('/uploads/' . $this->getEntityUploadFolder($baseField))
                ->onlyOnIndex()
                ->setLabel('Превью');
        }
    }
    protected function getEntityUploadFolder(string $baseField): string
    {
        return match ($baseField) {
            'logo' => 'brands',
            'image' => 'news',
            default => 'uploads'
        };
    }

    protected function getMetaFields(): iterable
    {
        $entityFqcn = $this->getEntityFqcn();
        $entityFields = $this->getEntityFields($entityFqcn);
        if (array_key_exists('metaTitle', $entityFields)) {
            yield TextField::new('metaTitle', 'Meta Title')->setMaxLength(255)->hideOnIndex();
        }
        if (array_key_exists('metaKeywords', $entityFields)) {
            yield TextField::new('metaKeywords', 'Meta Keywords')->setMaxLength(255)->hideOnIndex();
        }
        if (array_key_exists('metaDescription', $entityFields)) {
            yield TextField::new('metaDescription', 'Meta Description')
                ->setMaxLength(255)->hideOnIndex();
        }
    }

    /**
     * @throws \ReflectionException
     */
    protected function hasImageFields(): bool
    {
        $fields = $this->getVichUploadableFields($this->getEntityFqcn());
//        dd($fields,$this->getVichImageFieldNames($fields));
        return count($fields) > 0;
    }

    protected function getVichImageFieldNames(array $fields): array
    {
        $result = [];

        foreach ($fields as $field => $config) {
            if (str_ends_with($field, 'File')) {
                $base = substr($field, 0, -4);
                if (array_key_exists($base, $fields)) {
                    $result[] = $base;
                }
            }
        }

        return $result;
    }

    protected function createFieldByType(string $fieldName, array $fieldConfig): FieldInterface
    {
        // Обрабатываем ассоциации
        if ($fieldConfig['type'] === 'association') {
            return AssociationField::new($fieldName);
        }

        switch ($fieldConfig['type']) {
            case 'string':
                if (str_contains($fieldName, 'slug')) {
                    return SlugField::new($fieldName)->setTargetFieldName('title');
                }
                if (str_contains($fieldName, 'url')) {
                    return UrlField::new($fieldName);
                }
                return TextField::new($fieldName)->setMaxLength(255);
            case 'text':
                if (str_contains($fieldName, 'specs')) {
                    return TextareaField::new($fieldName);
                }
                if (str_contains($fieldName, 'sizes')) {
                    return TextareaField::new($fieldName);
                }
//                return TextEditorField::new($fieldName)
//                    ->setFormType(CKEditorType::class)
//                    ->setNumOfRows(9)
//                    ->setTrixEditorConfig([
//                        'blockAttributes' => [
//                            'heading1' => ['tagName' => 'h2']
//                        ]
//                    ]);
                return CKEditorField::new($fieldName);
            case 'datetime':
                return DateTimeField::new($fieldName);
            case 'boolean':
                return BooleanField::new($fieldName);
            case 'integer':
                return IntegerField::new($fieldName);
            default:
                return Field::new($fieldName);
        }
    }

    protected function getEntityFields(string $entityFqcn): array
    {
        $metadata = $this->entityManager->getClassMetadata($entityFqcn);

//        dd($metadata);
        $fields = [];
        // Явно задаем порядок важных полей
        $priorityOrder = [
            'id', 'externalId', 'title', 'slug', // основные поля
            'anons', 'description', 'preview', 'image', // контент
//            'isfp', 'rating', 'reviewCount', 'ord', // дополнительные
//            'parent', // отношения
//            'metaTitle', 'metaDescription', 'metaKeywords', // meta из трейтов
//            'status', 'created_at', 'updated_at' // системные из трейтов
        ];
        foreach ($metadata->fieldMappings as $field) {
            $fields[$field['fieldName']] = [
                'type' => $field['type'],
                'nullable' => $field['nullable'] ?? false,
            ];
        }

        // Обрабатываем ассоциации (связанные сущности)
        foreach ($metadata->associationMappings as $association) {
            $fields[$association['fieldName']] = [
                'type' => 'association',
                'associationType' => $association['type'],
                'targetEntity' => $association['targetEntity'],
                'nullable' => $association['joinColumns'][0]['nullable'] ?? true,
            ];
        }

        // Сортируем согласно нашему порядку
        uksort($fields, function ($a, $b) use ($priorityOrder) {
            $aIndex = array_search($a, $priorityOrder);
            $bIndex = array_search($b, $priorityOrder);

            // Если оба поля есть в нашем порядке
            if ($aIndex !== false && $bIndex !== false) {
                return $aIndex <=> $bIndex;
            }

            // Если только A есть в порядке - ставим его первым
            if ($aIndex !== false) {
                return -1;
            }

            // Если только B есть в порядке - ставим его первым
            if ($bIndex !== false) {
                return 1;
            }

            // Если оба отсутствуют - сортируем по алфавиту
            return strcmp($a, $b);
        });

        return $fields;
    }

    /**
     * @throws \ReflectionException
     */
    protected function getVichUploadableFields(string $entityFqcn): array
    {
        $reflection = new \ReflectionClass($entityFqcn);
        $props = $reflection->getProperties();

        $vichFields = [];

        foreach ($props as $prop) {
            $attrs = $prop->getAttributes(UploadableField::class);
            if (!empty($attrs)) {
                // берём имя свойства
                $vichFields[] = $prop->getName();
            }
        }

        return $vichFields;
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        parent::updateEntity($entityManager, $entityInstance);

        $reflectionClass = new \ReflectionClass($entityInstance);
        $sectionType = $entityManager->getRepository(SectionType::class)
            ->findOneBy([
                'entityClass' => $reflectionClass->getName(),
            ]);
//        dd($entityInstance,$reflectionClass,$sectionType);

        $repo = $entityManager->getRepository(Main::class);
        $main = $repo->findOneBy([
            'entityType' => $sectionType->getId(),
            'entityId' => $entityInstance->getId()
        ]);
        // Обновляем статус в связанной сущности
        if ($main) {
            $main->setStatus($entityInstance->getStatus());
            $main->setTitle($entityInstance->getTitle());
            $main->setSlug($entityInstance->getSlug());
            $main->setFullPath($this->pathGenerator->generateFullPath($main));

            $entityManager->persist($main);
            $entityManager->flush();
        } else {
            $main = new Main();
            $main->setEntityType($sectionType);
            $main->setEntityId($entityInstance->getId());
            $main->setTitle($entityInstance->getTitle());
            $main->setSlug($entityInstance->getSlug());
            $main->setStatus($entityInstance->getStatus());
            $main->setIsNode($entityInstance->getIsNode());
            $main->setCreatedAt($entityInstance->getCreatedAt());
            $main->setUpdatedAt($entityInstance->getUpdatedAt());
            if ($entityInstance instanceof Product) {
                if ($entityInstance->getParent()) {
                    $categoryType = $this->entityManager->getRepository(SectionType::class)
                        ->findOneBy(['code' => 'category']);

                    $parentMain = $this->entityManager->getRepository(Main::class)->findOneBy([
                        'entityId' => $categoryType->getId(),
                        'entityType' => $categoryType,
                    ]);
                    $main->setParent($parentMain);
                }
            }
        }
    }
}
