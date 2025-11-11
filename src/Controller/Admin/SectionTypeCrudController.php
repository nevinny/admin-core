<?php

namespace AdminCore\Controller\Admin;

use AdminCore\Entity\SectionType;
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
