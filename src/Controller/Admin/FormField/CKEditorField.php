<?php

namespace AdminCore\Controller\Admin\FormField;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;
use EasyCorp\Bundle\EasyAdminBundle\Config\Asset;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\TextEditorType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

final class CKEditorField implements FieldInterface
{
    use FieldTrait;

    public static function new(string $propertyName, ?string $label = null): self
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setTemplateName('crud/field/text_editor')
            ->setFormType(TextareaType::class)
            ->addCssClass('field-text_editor')
            ->setDefaultColumns('col-md-9 col-xxl-7')
            ->setFormTypeOption('attr', [
//                'onfocus' => 'if(!this._ckeditor) { this._ckeditor = true; ClassicEditor.create(this); }',
                'rows' => 11,
                'class' => 'ckeditor-field',
                'data-ckeditor' => 'true',
            ])

//            ->addWebpackEncoreEntries(Asset::new('admin/ckeditor.js'))
//            ->addCssFiles(Asset::new('admin/ckeditor.css'))
            ;
    }

    /**
     * @param array $toolbarConfig
     * @return CKEditorField
     */
    public function setToolbar(array $toolbarConfig): self
    {
        $this->setCustomOption('toolbar', $toolbarConfig);
        return $this;
    }

    /**
     * @param int $height
     * @return CKEditorField
     */
    public function setHeight(int $height): self
    {
        $this->setCustomOption('height', $height);
        return $this;
    }

    /**
     * @param string $language
     * @return CKEditorField
     */
    public function setLanguage(string $language): self
    {
        $this->setCustomOption('language', $language);
        return $this;
    }
}
