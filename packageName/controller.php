<?php
namespace Concrete\Package\PackageName;

use Package;
use AttributeSet;
use \Concrete\Core\Attribute\Key\Category as AttributeKeyCategory;
use \Concrete\Core\Attribute\Key\CollectionKey as CollectionKey;
use \Concrete\Core\Attribute\Type as AttributeType;
use Page;
use PageType;
use PageTemplate;

class Controller extends Package
{
    protected $pkgHandle = 'packageName';
    protected $appVersionRequired = '5.7.5.2';
    protected $pkgVersion = '0.0.1';

    public function getPackageDescription()
    {
        return t('');
    }

    public function getPackageName()
    {
        return t('Package Name');
    }

    public function install()
    {
        $pkg = parent::install();
        $this->installOrUpgrade($pkg);
    }

    public function upgrade()
    {
        $pkg = Package::getByHandle($this->pkgHandle);
        parent::upgrade();
        $this->installOrUpgrade($pkg);
    }

    public function installOrUpgrade($pkg)
    {

    }

    /**
     * Add Attribute Set
     * @param string $categoryHandle Attribute Key Category Handle
     * @param string $setHandle New Attribute Set Handle
     * @param string $setName New Attribute Set Name
     * @param object $pkg Package Object
     */
    public function addAttributeSet($categoryHandle, $setHandle, $setName, $pkg)
    {
        $pakc = AttributeKeyCategory::getByHandle($categoryHandle);
        $pakc->setAllowAttributeSets(AttributeKeyCategory::ASET_ALLOW_MULTIPLE);

        //get or set Attribute Set
        $att_set = AttributeSet::getByHandle($setHandle);
        if (!is_object($att_set)) {
            $att_set = $pakc->addSet($setHandle, t($setName), $pkg);
        }
    }
    
        
    /**
     * Add Custom Collection Attribute Key
     * @param string $handle Handle
     * @param string $name Name
     * @param string $type Attribute Type
     * @param object $pkg Package Object
     * @param object $att_set Attribute Set Object
     */
    public function addCollectionAttribute($handle, $name, $type, $pkg, $att_set)
    {
        $attr = CollectionKey::getByHandle($handle);
        if (!is_object($attr)) {
            $info = array(
                'akHandle' => $handle,
                'akName' => $name,
                'akIsSearchable' => true
            );
            $att_type = AttributeType::getByHandle($type);
            $attr = CollectionAttributeKey::add($att_type, $info, $pkg)->setAttributeSet($att_set);
            if ($type == 'select') {
                $attr->setAllowOtherValues();
            }
        }
    }
    
    /**
     * Add a Specific Page
     * @param string $handle Page Handle
     * @param string $name Page Name
     * @param string $description Page Description
     * @param string $type Page Type Handle
     * @param string $template Page Template Handle
     * @param string|int|object $parent Parent Page (can be handle, ID, or object)
     * @param object $pkg Package Object
     * @return object Page Object
     */
    public function addPage($handle, $name, $description, $type, $template, $parent, $pkg)
    {
        $page = Page::getByHandle($handle);
        if (!is_object($page)) {
            $pageType = PageType::getByHandle($type);
            $pageTemplate = PageTemplate::getByHandle($template);
            if (is_object($parent)) {
                $parent = $parent;
            } elseif (is_int($parent)) {
                $parent = Page::getById($parent);
            } else {
                $parent = Page::getByPath($parent);
            }
            $pkgID = $pkg->getPackageID();
            $page = $parent->add($pageType, array(
                'cName' => $name,
                'cHandle' => $handle,
                'cDescription' => $description,
                'pkgID' => $pkgID
            ), $pageTemplate);
        }
        
        return $page;
    }
}
