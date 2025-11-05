<?php

namespace AdminCore\Controller\Admin\Trait;

use AdminCore\Controller\Admin\DefaultCrudController;
use AdminCore\Entity\SectionType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;

trait EntityTypeRedirectActionsTrait
{
    abstract protected function getAdminUrlGenerator(): AdminUrlGenerator;

    public function configureEntityTypeRedirectActions(Actions $actions): Actions
    {
        return $actions
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action->linkToUrl(fn($entityInstance) => $this->generateEntityTypeUrl($entityInstance, Action::EDIT));
            })
//          ->update(Crud::PAGE_INDEX, Action::DETAIL, function (Action $action) {
//              return $action->linkToUrl(fn($entityInstance) => $this->generateEntityTypeUrl($entityInstance, Action::DETAIL));
//          })
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action->linkToUrl(fn($entityInstance) => $this->generateEntityTypeUrl($entityInstance, Action::DELETE));
            });
    }

    private function generateEntityTypeUrl(object $entity, string $action): string
    {
        $adminUrlGenerator = $this->getAdminUrlGenerator();

        // Проверяем наличие метода getEntityType
        if (!method_exists($entity, 'getEntityType')) {
            // fallback — генерируем URL текущим контроллером и id сущности
            return $adminUrlGenerator
                ->setAction($action)
                ->setEntityId($entity->getId())
                ->generateUrl();
        }

        $type = $entity->getEntityType();

        if (!$type || !method_exists($type, 'getCrudControllerClass')) {
            throw new \RuntimeException('EntityType must return valid controller class');
        }

        $controllerFqcn = $type->getCrudControllerClass();

        $urlGenerator = $adminUrlGenerator
            ->setAction($action)
            ->setEntityId(method_exists($entity, 'getEntityId') ? $entity->getEntityId() : $entity->getId());

        if ($controllerFqcn !== null) {
            $urlGenerator->setController($controllerFqcn);
        }

        return $urlGenerator->generateUrl();
    }
}
