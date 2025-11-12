<?php

namespace Nevinny\AdminCoreBundle\Controller\Admin;

use Nevinny\AdminCoreBundle\Entity\SectionType;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class SectionTypeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SectionType::class;
    }
}
