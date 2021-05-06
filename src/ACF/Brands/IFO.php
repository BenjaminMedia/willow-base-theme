<?php

namespace Bonnier\Willow\Base\ACF\Brands;

use Bonnier\Willow\Base\Models\ACF\ACFField;
use Bonnier\Willow\Base\Models\ACF\ACFLayout;
use Bonnier\Willow\Base\Models\ACF\Composite\CompositeFieldGroup;
use Bonnier\Willow\Base\Models\ACF\Fields\ImageField;
use Bonnier\Willow\Base\Models\ACF\Fields\RadioField;
use Bonnier\Willow\Base\Models\ACF\Fields\RepeaterField;
use Bonnier\Willow\Base\Models\ACF\Fields\TrueFalseField;
use Bonnier\Willow\Base\Models\ACF\Page\PageFieldGroup;
use Bonnier\Willow\Base\Models\ACF\Page\SortByFields;
use Bonnier\Willow\Base\Models\ACF\User\UserFieldGroup;

class IFO extends Brand
{
    public static function register(): void
    {
        self::init();
        self::removeVideoUrlFromImageWidget();
        self::removeVideoUrlFromGalleryItems();
        self::removeVideoUrlFromParagraphListWidget();
        self::removeVideoUrlFromTeaserImages();
        self::removeImageFromInfoboxWidget();
        self::removeSortByEditorialTypeFromTeaserListPageWidget();

        self::removeInventoryWidget();
        self::removeAudioWidget();
        self::removeMultimediaWidget();
        self::removeProductWidget();

        self::removeQuotePageWidget();
        self::removeFeaturedContentPageWidget();

        $teaserListWidget =  PageFieldGroup::getTeaserListLayout();
        add_filter(sprintf('willow/acf/layout=%s', $teaserListWidget->getKey()), [__CLASS__, 'setTeaserListDisplayHints']);
        add_filter(sprintf('willow/acf/layout=%s', $teaserListWidget->getKey()), [__CLASS__, 'setTeaserListMultiTagField']);

        $galleryField = CompositeFieldGroup::getGalleryWidget();
        add_filter(sprintf('willow/acf/layout=%s', $galleryField->getKey()), [__CLASS__, 'setGalleryDisplayHints']);

        $imageWidget = CompositeFieldGroup::getImageWidget();
        add_filter(sprintf('willow/acf/layout=%s', $imageWidget->getKey()), [__CLASS__, 'setImageDisplayHints']);

        $paragraphListWidget = parent::$paragraphListWidget;
        add_filter(sprintf('willow/acf/layout=%s', $paragraphListWidget->getKey()), [__CLASS__, 'setParagraphListDisplayHints']);
        add_filter(sprintf('willow/acf/layout=%s', $paragraphListWidget->getKey()), [__CLASS__, 'removeParagraphListCollapsible']);

        $infoBoxWidget = parent::$infoboxWidget;
        add_filter(sprintf('willow/acf/layout=%s', $infoBoxWidget->getKey()), [__CLASS__, 'setInfoBoxDisplayHints']);

        $videoWidget = CompositeFieldGroup::getVideoWidget();
        add_filter(sprintf('willow/acf/layout=%s', $videoWidget->getKey()), [__CLASS__, 'setIncludeIntroVideoDefaultTrue']);

        $linkWidget = CompositeFieldGroup::getLinkWidget();
        add_filter(sprintf('willow/acf/layout=%s', $linkWidget->getKey()), [__CLASS__, 'addLinkWidgetDisplayHints']);

        $fileWidget = CompositeFieldGroup::getFileWidget();
        add_filter(sprintf('willow/acf/layout=%s', $fileWidget->getKey()), [__CLASS__, 'removeRequiredFromFileWidgetImages']);
    }

    public static function setTeaserListDisplayHints(ACFLayout $teaserList)
    {
        $displayHint = new RadioField('field_5bb319a1ffcf1');
        $displayHint->setLabel('Display Format')
            ->setName('display_hint')
            ->setChoice('1col', '1 Col')
            ->setChoice('2col', '2 Col')
            ->setChoice('3col', '3 Col')
            ->setChoice('4col', '4 Col')
            ->setChoice('2plus1', '2 + 1')
            ->setChoice('1plus2', '1 + 2')
            ->setChoice('1plus4', '1 + 4')
            ->setChoice('4plus1', '4 + 1')
            ->setChoice('slider4col', 'Slider - 4 col')
            ->setChoice('slider-netflix', 'Slider - netflix')
            ->setChoice('text+4', 'text + 4')
            ->setChoice('toplist', 'Top list')
            ->setChoice('featured', 'Featured')
            ->setDefaultValue('1plus2')
            ->setLayout('vertical')
            ->setReturnFormat(ACFField::RETURN_VALUE);

        return $teaserList->addSubField($displayHint);
    }

    public static function setTeaserListMultiCategoryField(ACFLayout $teaserList)
    {
        $categoryField = SortByFields::getCategoryField(true);

        return $teaserList->addSubField($categoryField);
    }

    public static function setTeaserListMultiTagField(ACFLayout $teaserList)
    {
        $tagField = SortByFields::getTagField(true);

        return $teaserList->addSubField($tagField);
    }

    public static function setGalleryDisplayHints(ACFLayout $gallery): ACFLayout
    {
        $displayHint = new RadioField('field_5af2a198b1028');
        $displayHint->setLabel('Display Format')
            ->setName('display_hint')
            ->setChoice('default', 'Default')
            ->setChoice('slider', 'Slider')
            ->setDefaultValue('default')
            ->setLayout('vertical')
            ->setReturnFormat(ACFField::RETURN_VALUE);

        return $gallery->addSubField($displayHint);
    }

    public static function setImageDisplayHints(ACFLayout $image)
    {
        return $image->mapSubFields(function (ACFField $field) {
            if ($field instanceof RadioField && $field->getName() === 'display_hint') {
                $field->setChoices([
                    'default' => 'Default',
                    'xl' => 'XL',
                    'sm' => 'SM',
                ]);
                $field->setDefaultValue('default');
            }
            return $field;
        });
    }

    public static function setInfoBoxDisplayHints(ACFLayout $infoBox)
    {
        $displayHint = new RadioField('field_5f60afb647c6e');
        $displayHint->setLabel('Display Format')
            ->setName('display_hint')
            ->setChoice('default', 'Default')
            ->setChoice('border', 'Border')
            ->setChoice('solid', 'Solid')
            ->setDefaultValue('default')
            ->setLayout('vertical')
            ->setReturnFormat(ACFField::RETURN_VALUE);

        return $infoBox->addSubField($displayHint);
    }

    public static function setParagraphListDisplayHints(ACFLayout $paragraphList)
    {
        return $paragraphList->mapSubFields(function (ACFField $field) {
            if ($field instanceof RadioField && $field->getName() === 'display_hint') {
                $field->setChoices([
                    'accordion' => 'Accordion',
                    'box' => 'Box',
                    'border' => 'Border',
                    'clean' => 'Clean',
                    'fold-out-no-items' => 'Fold out - no items',
                    'img-left-no-items' => 'Img left - no items',
                ]);
                $field->setDefaultValue('box');
            }
            return $field;
        });
    }

    public static function addLinkWidgetDisplayHints(ACFLayout $link)
    {
        $displayHint = new RadioField('field_5f916f115010d');
        $displayHint->setLabel('Display Format')
            ->setName('display_hint')
            ->setChoice('default', 'Default')
            ->setChoice('small_button', 'Small button')
            ->setChoice('large_button', 'Large button')
            ->setChoice('large_button_centered', 'Large button centered')
            ->setDefaultValue('default')
            ->setLayout('vertical')
            ->setReturnFormat(ACFField::RETURN_VALUE);

        return $link->addSubField($displayHint);
    }

    public static function removeRequiredFromFileWidgetImages(ACFLayout $fileWidget)
    {
        $images = new RepeaterField('field_5921e5a83f4ea');
        $images->setLabel('Images')
            ->setName('images')
            ->setRequired(false)
            ->setLayout('table')
            ->setButtonLabel('Add Image');

        $image = new ImageField('field_5921e94c3f4eb');
        $image->setLabel('File')
            ->setName('file')
            ->setRequired(false)
            ->setReturnFormat(ACFField::RETURN_ARRAY)
            ->setPreviewSize(ImageField::PREVIEW_MEDIUM);

        $images->addSubField($image);

        return $fileWidget->addSubField($images);
    }

    public static function removeParagraphListCollapsible(ACFLayout $layout)
    {
        $subFields = array_filter($layout->getSubFields(), function (ACFField $field) {
            return $field->getName() !== CompositeFieldGroup::COLLAPSIBLE_FIELD_NAME;
        });
        return $layout->setSubFields($subFields);
    }

    public static function setIncludeIntroVideoDefaultTrue(ACFLayout $videoWidget)
    {
        $includeIntroVideo = new TrueFalseField('field_6061945f12bd9');
        $includeIntroVideo->setLabel('Include intro video')
            ->setName(CompositeFieldGroup::VIDEO_INCLUDE_INTRO_VIDEO_FIELD)
            ->setDefaultValue(true);

        return $videoWidget->addSubField($includeIntroVideo);
    }
}
