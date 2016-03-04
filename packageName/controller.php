<?php
namespace Concrete\Package\PackageName;

use Package;
use BlockType;
use AttributeSet;
use \Concrete\Core\Attribute\Key\Category as AttributeKeyCategory;
use \Concrete\Core\Attribute\Key\CollectionKey as CollectionKey;
use \Concrete\Core\Attribute\Key\FileKey as FileKey;
use \Concrete\Core\Attribute\Key\UserKey as UserKey;
use \Concrete\Core\Attribute\Type as AttributeType;
use Page;
use PageType;
use PageTemplate;

class Controller extends Package
{
    protected $pkgHandle = 'packageName';
    protected $appVersionRequired = '5.7.5.2';
    protected $pkgVersion = '0.0.1';
    protected $previousVersion = '0.0.0';

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
        $this->previousVersion = $pkg->getPackageVersion();
        parent::upgrade();
        $this->installOrUpgrade($pkg);
    }

    public function installOrUpgrade($pkg)
    {
        
    }
    
    /**
     * Add Block Type
     * @param string $handle Block Handle
     * @param object $pkg Package Object
     * @return object Block Type Object
     */
    public function addBlockType($handle, $pkg)
    {
        $bt = BlockType::getByHandle($handle);
        if (!is_object($bt)) {
            $bt = BlockType::installBlockType($handle, $pkg);
        }
        
        return $bt;
    }

    /**
     * Add Attribute Set
     * @param string $categoryHandle Attribute Key Category Handle
     * @param string $setHandle New Attribute Set Handle
     * @param string $setName New Attribute Set Name
     * @param object $pkg Package Object
     * @return object Attribute Set Object
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
        
        return $att_set;
    }
    
        
    /**
     * Add Custom Attribute Key
     * @param string $handle Handle
     * @param string $name Name
     * @param string $category Attribute Key Category Class Name (assumes php5.3)
     * @param string $type Attribute Type
     * @param object $pkg Package Object
     * @param object $att_set Attribute Set Object
     * @return object Attribute Object
     */
    public function addAttribute($handle, $name, $category, $type, $pkg, $att_set)
    {
        $attr = $category::getByHandle($handle);
        if (!is_object($attr)) {
            $info = array(
                'akHandle' => $handle,
                'akName' => $name,
                'akIsSearchable' => true
            );
            $att_type = AttributeType::getByHandle($type);
            $attr = $category::add($att_type, $info, $pkg)->setAttributeSet($att_set);
            if ($type == 'select') {
                $attr->setAllowOtherValues();
            }
        }
        
        return $attr;
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
    
    /**
     * Add New Page Type
     * @param string $typeHandle New Type Handle
     * @param string $typeName New Type Name
     * @param string $defaultTemplateHandle Default Page Template Handle
     * @param string $allowedTemplates (A|C|X) A for all, C for selected only, X for non-selected only
     * @param array $templateArray Array or Iterator of selected templates, see `$allowedTemplates`
     * @param object $pkg
     * @return object Page Type Object
     */
    public function addPageType($typeHandle, $typeName, $defaultTemplateHandle, $allowedTemplates, $templateArray, $pkg)
    {
        //Get required objects (these can be handles after 8)
        $defaultTemplate = PageTemplate::getByHandle($defaultTemplateHandle);
        $allowedTemplateArray = array();
        foreach($templateArray as $handle) {
            $allowedTemplateArray[] = PageTemplate::getByHandle($handle);
        }
        
        $pt = PageType::getByHandle($data['handle']);
        if (!is_object($pt)) {
            $data = array (
                'handle' => $typeHandle,
                'name' => $typeName,
                'defaultTemplate' => $defaultTemplate,
                'allowedTemplates' => $allowedTemplates,
                'templates' => $allowedTemplateArray
            );
            $pt = PageType::add($data, $pkg);
        }
        return $pt;
    }
}
