<?php

namespace Bonnier\Willow\Base\ACF\Brands;

use Bonnier\Willow\Base\Models\ACF\ACFField;
use Bonnier\Willow\Base\Models\ACF\ACFLayout;
use Bonnier\Willow\Base\Models\ACF\Composite\CompositeFieldGroup;
use Bonnier\Willow\Base\Models\ACF\Fields\RadioField;

class GDS extends Brand
{
    public static function register(): void
    {
        self::removeVideoUrlFromImageWidget();
        self::removeVideoUrlFromGalleryItems();
        self::removeVideoUrlFromParagraphListWidget();
        self::removeInventoryWidget();

        $galleryField = CompositeFieldGroup::getGalleryWidget();
        add_filter(sprintf('willow/acf/layout=%s', $galleryField->getKey()), [__CLASS__, 'setGalleryDisplayHints']);

        $paragraphListWidget = CompositeFieldGroup::getParagraphListWidget();
        add_filter(sprintf('willow/acf/layout=%s', $paragraphListWidget->getKey()), [__CLASS__, 'setParagraphListDisplayHints']);
        add_filter(sprintf('willow/acf/layout=%s', $paragraphListWidget->getKey()), [__CLASS__, 'removeParagraphListCollapsible']);

        $imageWidget = CompositeFieldGroup::getImageWidget();
        add_filter(sprintf('willow/acf/layout=%s', $imageWidget->getKey()), [__CLASS__, 'setImageDisplayHints']);

        $infoBoxWidget = CompositeFieldGroup::getInfoboxWidget();
        add_filter(sprintf('willow/acf/layout=%s', $infoBoxWidget->getKey()), [__CLASS__, 'setInfoBoxDisplayHints']);

        $linkWidget = CompositeFieldGroup::getLinkWidget();
        add_filter(sprintf('willow/acf/layout=%s', $linkWidget->getKey()), [__CLASS__, 'addLinkWidgetDisplayHints']);
    }

    public static function setGalleryDisplayHints(ACFLayout $gallery): ACFLayout
    {
        return $gallery->mapSubFields(function (ACFField $field) {
            if ($field instanceof RadioField && $field->getName() === 'display_hint') {
                $field->removeChoice('parallax');
            }
            return $field;
        });
    }

    public static function setParagraphListDisplayHints(ACFLayout $paragraphList)
    {
        return $paragraphList->mapSubFields(function (ACFField $field) {
            if ($field instanceof RadioField && $field->getName() === 'display_hint') {
                $field->setChoices([
                    'box' => 'Box',
                    'text-full' => 'Text Full',
                    'text-half' => 'Text Half',
                    'border' => 'Border',
                    'material-list' => 'Material List',
                    'slider-full-width' => 'Slider Full Width',
                    'slider-cards' => 'Slider Cards',
                ]);
                $field->setDefaultValue('box');
            }
            return $field;
        });
    }


    public static function removeParagraphListCollapsible(ACFLayout $layout)
    {
        $subFields = array_filter($layout->getSubFields(), function (ACFField $field) {
            return $field->getName() !== CompositeFieldGroup::COLLAPSIBLE_FIELD_NAME;
        });
        return $layout->setSubFields($subFields);
    }

    public static function setImageDisplayHints(ACFLayout $paragraphList)
    {
        return $paragraphList->mapSubFields(function (ACFField $field) {
            if ($field instanceof RadioField && $field->getName() === 'display_hint') {
                $field->setChoices([
                    'full-width' => 'Full Width',
                    'half-width' => 'Half Width',
                ]);
                $field->setDefaultValue('full-width');
            }
            return $field;
        });
    }

    public static function setInfoBoxDisplayHints(ACFLayout $infoBox)
    {
        $displayHint = new RadioField('field_5f60afb647c6e');
        $displayHint->setLabel('Display Format')
            ->setName('display_hint')
            ->setChoice('yellow', 'Yellow')
            ->setChoice('blue', 'Blue')
            ->setChoice('green', 'Green')
            ->setChoice('red', 'Red')
            ->setDefaultValue('yellow')
            ->setLayout('vertical')
            ->setReturnFormat(ACFField::RETURN_VALUE);

        return $infoBox->addSubField($displayHint);
    }

    public static function addLinkWidgetDisplayHints(ACFLayout $link)
    {
        $displayHint = new RadioField('field_5f916f115010d');
        $displayHint->setLabel('Display Format')
            ->setName('display_hint')
            ->setChoice('default', 'Default')
            ->setChoice('button', 'Button')
            ->setDefaultValue('default')
            ->setLayout('vertical')
            ->setReturnFormat(ACFField::RETURN_VALUE);

        return $link->addSubField($displayHint);
    }
}
