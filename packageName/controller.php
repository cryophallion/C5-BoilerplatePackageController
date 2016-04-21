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
use SinglePage;
use PageTheme;
use FileSet;

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

    protected function installOrUpgrade($pkg)
    {
        
    }
    
    /**
     * Add Block Type
     * @param string $handle Block Handle
     * @param object $pkg Package Object
     * @return object Block Type Object
     */
    protected function addBlockType($handle, $pkg)
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
    protected function addAttributeSet($categoryHandle, $setHandle, $setName, $pkg)
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
     * @param object $category Attribute Key Category Class (ie, CollectionKey, etc class object)
     * @param string $type Attribute Type
     * @param object $att_set Attribute Set Object
     * @param object $pkg Package Object
     * @return object Attribute Object
     */
    protected function addAttribute($handle, $name, $category, $type, $att_set, $pkg)
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
    protected function addPage($handle, $name, $description, $type, $template, $parent, $pkg)
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
    protected function addPageType($typeHandle, $typeName, $defaultTemplateHandle, $allowedTemplates, $templateArray, $pkg)
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
    
    /**
     * Add Single Page
     * @param string $path Page Path
     * @param object $pkg Package Object
     * @param string $name Single Page Name
     * @param string $description Single Page Description
     * @return object Single Page Object
     */
    protected function addSinglePage($path, $pkg, $name="", $description="")
    {
        //Install single page
        $sp = Page::getByPath($path);
        if (!is_object($sp)) {
           $sp = SinglePage::add($path, $pkg); 
        }
        
        //Set name and description
        if (!empty($name) || !empty($description)) {
            $data = array();
            if (!empty($name)) {
                $data['cName'] = $name;
            }
            if (!empty($description)) {
                $data['cDescription'] = $description;
            }
            $sp->update($data);
        }
        
        return $sp;
    }
    
    /**
     * Add Theme
     * @param string $handle Theme Handle
     * @param object $pkg Package Object
     * @return object Theme Object
     */
    protected function addTheme($handle, $pkg)
    {
        $theme = PageTheme::getByHandle($handle);
        if (!is_object($theme)) {
            $theme = PageTheme::add($handle, $pkg);
        }
        
        return $theme;
    }
    
    /**
     * Add File Set
     * @param string $fsName FileSet Name
     * @param string $fsType FileSet Type (public, private, starred)
     * @return object FileSet Object
     */
    protected function addFileSet($fsName, $fsType)
    {
        $fs = FileSet::getByName($fsName);
        if (!is_object($fs)) {
            switch (strtolower($fsType)) {
                case 'private':
                    $type = 'TYPE_PRIVATE';
                    break;
                
                case 'public':
                    $type = 'TYPE_PUBLIC';
                    break;
                
                case 'starred':
                    $type = 'TYPE_STARRED';
                    break;

                default:
                    $type = 'TYPE_PRIVATE';
                    break;
            }
            $fs = FileSet::createAndGetSet($fsName, $fsType);
        }
        
        return $fs;
    }
}
