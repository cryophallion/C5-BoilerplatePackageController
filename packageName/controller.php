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
use \Concrete\Core\Page\Type\PublishTarget\Type\Type as PublishTargetType;
use \Concrete\Core\Page\Type\Composer\LayoutSet as LayoutSet;
use \Concrete\Core\Page\Type\Composer\Control\CollectionAttributeControl as AttributeControl;
use \Concrete\Core\Page\Type\Composer\Control\BlockControl as BlockControl;
use SinglePage;
use PageTheme;
use FileSet;
use Express;
use Concrete\Core\Express\EntryList as EntryList;

class Controller extends Package
{

    protected $pkgHandle = 'packageName';
    protected $appVersionRequired = '8.1.0';
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
     * @param string $type Attribute Type
     * @param object $categoryKeyObject Attribute Key Category Class (ie, CollectionKey, etc class object)
     * @param object $attibuteSetObject Attribute Set Object
     * @param object $pkg Package Object
     * @param boolean $selectAllowOtherValues Sets whether additional values are allowed for select attributes
     * @return object Attribute Object
     */
    protected function addAttribute($handle, $name, $type, $categoryKeyObject, $attibuteSetObject, $pkg, $selectAllowOtherValues = true)
    {
        $attr = $categoryKeyObject::getByHandle($handle);
        if (!is_object($attr)) {
            $info = array(
                'akHandle' => $handle,
                'akName' => $name,
                'akIsSearchable' => true
            );
            $att_type = AttributeType::getByHandle($type);
            $attr = $categoryKeyObject::add($att_type, $info, $pkg);
            $attr->setAttributeSet($attibuteSetObject);
            if ($type == 'select' && $selectAllowOtherValues == true) {
                $attr->getController()->setAllowOtherValues();
            }
        }

        return $attr;
    }

    /**
     * Add a Specific Page
     * @param string|int $pathOrCID Page Path OR CID
     * @param string $name Page Name
     * @param string $description Page Description
     * @param string $type Page Type Handle
     * @param string $template Page Template Handle
     * @param string|int|object $parent Parent Page (can be handle, ID, or object)
     * @param object $pkg Package Object
     * @param string $handle Optional slugified handle
     * @return object Page Object
     */
    protected function addPage($pathOrCID, $name, $description, $type, $template, $parent, $pkg, $handle = null)
    {
        //Get Page if it's already created
        if (is_int($pathOrCID)) {
            $page = Page::getByID($pathOrCID);
        } else {
            $page = Page::getByPath($pathOrCID);
        }
        if ($page->isError() && $page->getError() == COLLECTION_NOT_FOUND) {
            //Get Page Type and Templates from their handles
            $pageType = PageType::getByHandle($type);
            $pageTemplate = PageTemplate::getByHandle($template);

            //Get parent, depending on what format parent is passed in
            if (is_object($parent)) {
                $parent = $parent;
            } elseif (is_int($parent)) {
                $parent = Page::getById($parent);
            } else {
                $parent = Page::getByPath($parent);
            }
            //Get package
            $pkgID = $pkg->getPackageID();

            //Create Page
            $page = $parent->add($pageType, array(
                'cName' => $name,
                'cHandle' => $handle,
                'cDescription' => $description,
                'pkgID' => $pkgID,
                'cHandle' => $handle
                ), $pageTemplate);
        }

        return $page;
    }

    /**
     * Adds a Page Type with an All Publish Target (can publish anywhere)
     * @param string $typeHandle Page Type Handle
     * @param string $typeName Page Type Name
     * @param string $defaultTemplateHandle Default Page Template Handle
     * @param string $allowedTemplates (A|C|X) A for all, C for selected only, X for non-selected only
     * @param array $templateArray Array or Iterator of selected templates, see `$allowedTemplates`
     * @param object $pkg Package Object
     * @param int $startingPointCID CID of optional starting point below which page can be added
     * @param bool $selectorFormFactor Form factor of page selector
     * @return object Page Type Object
     */
    protected function addPageTypeWithAllPublishTarget($typeHandle, $typeName, $defaultTemplateHandle, $allowedTemplates, $templateArray, $pkg, $startingPointCID = 0, $selectorFormFactor = 0)
    {
        $pt = PageType::getByHandle($typeHandle);
        if (!is_object($pt)) {
            $pto = $this->addPageType($typeHandle, $typeName, $defaultTemplateHandle, $allowedTemplates, $templateArray, $pkg);
            $pt = $this->setAllPublishTarget($pto, $startingPointCID, $selectorFormFactor);
        }

        return $pt;
    }

    /**
     * Add a Page Type with a Page Type Publish Target
     * @param string $typeHandle Page Type Handle
     * @param string $typeName Page Type Name
     * @param string $defaultTemplateHandle Default Page Template Handle
     * @param string $allowedTemplates (A|C|X) A for all, C for selected only, X for non-selected only
     * @param array $templateArray Array or Iterator of selected templates, see `$allowedTemplates`
     * @param int $parentPageTypeID ID of parent Page Type
     * @param object $pkg Package Object
     * @param int $startingPointCID CID of optional starting point below which page can be added
     * @param bool $selectorFormFactor Form factor of page selector
     * @return object Page Type Object
     */
    protected function addPageTypeWithPageTypePublishTarget($typeHandle, $typeName, $defaultTemplateHandle, $allowedTemplates, $templateArray, $parentPageTypeID, $pkg, $startingPointCID = 0, $selectorFormFactor = 0)
    {
        $pt = PageType::getByHandle($typeHandle);
        if (!is_object($pt)) {
            $pto = $this->addPageType($typeHandle, $typeName, $defaultTemplateHandle, $allowedTemplates, $templateArray, $pkg);
            $pt = $this->setPageTypePublishTarget($pto, $parentPageTypeID, $startingPointCID, $selectorFormFactor);
        }

        return $pt;
    }

    /**
     * Add a Page Type with a Parent Page Publish Target
     * @param string $typeHandle Page Type Handle
     * @param string $typeName Page Type Name
     * @param string $defaultTemplateHandle Default Page Template Handle
     * @param string $allowedTemplates (A|C|X) A for all, C for selected only, X for non-selected only
     * @param array $templateArray Array or Iterator of selected templates, see `$allowedTemplates`
     * @param int $parentPageCID Parent Page CID
     * @param object $pkg Package Object
     * @return object Page Type Object
     */
    protected function addPageTypeWithParentPagePublishTarget($typeHandle, $typeName, $defaultTemplateHandle, $allowedTemplates, $templateArray, $parentPageCID, $pkg)
    {
        $pt = PageType::getByHandle($typeHandle);
        if (!is_object($pt)) {
            $pto = $this->addPageType($typeHandle, $typeName, $defaultTemplateHandle, $allowedTemplates, $templateArray, $pkg);
            $pt = $this->setParentPagePublishTarget($pto, $parentPageCID);
        }

        return $pt;
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
        foreach ($templateArray as $handle) {
            $allowedTemplateArray[] = PageTemplate::getByHandle($handle);
        }

        $data = array(
            'handle' => $typeHandle,
            'name' => $typeName,
            'defaultTemplate' => $defaultTemplate,
            'allowedTemplates' => $allowedTemplates,
            'templates' => $allowedTemplateArray
        );
        $pt = PageType::add($data, $pkg);

        return $pt;
    }

    /**
     * Set All Pages Publish Target for Page Type
     * @param object $pageTypeObject Page Type Object 
     * @param int $startingPointCID CID of page to be underneath, or 0 for any page
     * @param bool $selectorFormFactor 1 for in page sitemap, 0 for popup sitemap
     * @return object Page Type Object
     */
    protected function setAllPublishTarget($pageTypeObject, $startingPointCID = 0, $selectorFormFactor = 0)
    {
        $allTarget = PublishTargetType::getByHandle('all');
        $configuredTarget = $allTarget->configurePageTypePublishTarget(
            $pageTypeObject, array(
            'selectorFormFactorAll' => $selectorFormFactor, // this is the form factor of the page selector. null or false is the standard sitemap popup. 1 or true would be the in page sitemap
            'startingPointPageIDall' => ($startingPointCID) // If you only want this available below a certain explicit page, but anywhere nested under that page, set this page id. null or false sets this to anywhere
            )
        );
        $pageTypeObject->setConfiguredPageTypePublishTargetObject($configuredTarget);

        return $pageTypeObject;
    }

    /**
     * Set Page Type Publish Target for Page Type
     * @param object $pageTypeObject Page Type Object
     * @param int $parentPageTypeID Parent Page Type ID
     * @param int $startingPointCID CID of page to be underneath, or 0 for any page
     * @param bool $selectorFormFactor 1 for in page sitemap, 0 for popup sitemap
     * @return object Page Type Object
     */
    protected function setPageTypePublishTarget($pageTypeObject, $parentPageTypeID, $startingPointCID = 0, $selectorFormFactor = 0)
    {
        $typeTarget = PublishTargetType::getByHandle('page_type');
        $configuredTypeTarget = $typeTarget->configurePageTypePublishTarget(
            $pageTypeObject, //the one being set up, NOT the target one
            array(
            'ptID' => $parentPageTypeID,
            'startingPointPageIDPageType' => $startingPointCID, // this is the form factor of the page selector. null or false is the standard sitemap popup. 1 or true would be the in page sitemap
            'selectorFormFactorPageType' => $selectorFormFactor // If you only want this available below a certain explicit page, but anywhere nested under that page, set this page id. null or false sets this to anywhere
            )
        );
        $pageTypeObject->setConfiguredPageTypePublishTargetObject($configuredTypeTarget);

        return $pageTypeObject;
    }

    /**
     * Set Parent Page Publish Target for Page Type
     * @param object $pageTypeObject Page Type Object
     * @param int $parentPageCID Parent Page CID
     * @return object Page Type Object
     */
    protected function setParentPagePublishTarget($pageTypeObject, $parentPageCID)
    {
        $parentTarget = PublishTargetType::getByHandle('parent_page');
        $configuredParentTarget = $parentTarget->configurePageTypePublishTarget(
            $pageTypeObject, array(
            'CParentID' => $parentPageCID
            )
        );
        $pageTypeObject->setConfiguredPageTypePublishTargetObject($configuredParentTarget);

        return $pageTypeObject;
    }

    /**
     * Adds an Attribute Form Control
     * @param string $attributeHandle Attribute Handle
     * @param object $layoutSet Composer Layout Set
     * @param string $customName Custom Name for Control
     * @param string $customDescription Custom Description for Control
     * @return object AttributeControl
     */
    protected function addAttributeFormControl($attributeHandle, $layoutSet, $customName = null, $customDescription = null)
    {
        $fc = new AttributeControl();
        $aID = CollectionKey::getByHandle($attributeHandle)->getAttributeKeyID();
        $fc->setAttributeKeyId($aID);
        $fc->addToPageTypeComposerFormLayoutSet($layoutSet);
        if (!empty($customName)) {
            $fc->updateFormLayoutSetControlCustomLabel($customName);
        }
        if (!empty($customDescription)) {
            $fc->updateFormLayoutSetControlDescription($customDescription);
        }

        return $fc;
    }

    /**
     * Adds a Block Form Control
     * @param string $blockHandle Block Type Handle
     * @param object $layoutSet Composer Layout Set
     * @param string $customName Custom Name for Control
     * @param string $customDescription Custom Description for Control
     * @return object BlockControl
     */
    protected function addBlockFormControl($blockHandle, $layoutSet, $customName = null, $customDescription = null)
    {
        $fc = new BlockControl();
        $bID = BlockType::getByHandle($blockHandle)->getBlockTypeID();
        $fc->setBlockTypeID($bID);
        $fc->addToPageTypeComposerFormLayoutSet($layoutSet);
        if (!empty($customName)) {
            $fc->updateFormLayoutSetControlCustomLabel($customName);
        }
        if (!empty($customDescription)) {
            $fc->updateFormLayoutSetControlDescription($customDescription);
        }

        return $fc;
    }

    /**
     * Add Single Page
     * @param string $path Page Path
     * @param object $pkg Package Object
     * @param string $name Single Page Name
     * @param string $description Single Page Description
     * @return object Single Page Object
     */
    protected function addSinglePage($path, $pkg, $name = "", $description = "")
    {
        //Install single page
        $sp = Page::getByPath($path);
        if ($sp->isError() && $sp->getError() == COLLECTION_NOT_FOUND) {
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

    /**
     * Adds an express object if not set - note: this will be a builder if being initially created
     * @param string $handle
     * @param string $plural
     * @param string $name
     * @param object $pkg
     * @return object Builder OR Express Object
     */
    protected function addExpressObject($handle, $plural, $name, $pkg)
    {
        $eo = Express::getObjectByHandle($handle);
        if (!is_object($eo)) {
            $eo = Express::buildObject($handle, $plural, $name, $pkg);
        }

        return $eo;
    }

    /**
     * Some forms are just standalone and made with attributes, so this is a quick method for more quickly generating these forms 
     * @param object $expressObject Express Object
     * @param array $fieldArray Multidimensional array, such as array('FieldsetName'=>array('attribute_handle1', 'attributehandle2, ...))
     * @param string $formName Name for the form
     * @return object Express Object
     */
    protected function createExpressAttributeForm($expressObject, $fieldArray, $formName = "Form")
    {
        if (is_object($expressObject) && is_array($fieldArray)) {
            $form = $expressObject->buildForm($formName);

            foreach ($fieldArray as $fieldsetName => $fields) {
                $set = $form->addFieldSet($fieldsetName);
                foreach ($fields as $attribute) {
                    $set->addAttributeKeyControl($attribute);
                }
            }
            $form->save();
        }

        return $expressObject;
    }

    /**
     * More complicated forms have a few extra parameters to account for, but can also be automated a bit
     * @param object $expressObject Express Object.
     * @param array $fieldArray Multidimensional array, such as array('FieldsetName'=>array('attribute_handle1'=>'attribute", 'text_control'=>'text', 'association_name'=>'association'))
     * @param string $formName Name for the form
     * @return object Express Object
     */
    protected function createExpressForm($expressObject, $fieldArray, $formName = "Form", $set_default = True)
    {
        if (is_object($expressObject) && is_array($fieldArray)) {
            $form = $expressObject->buildForm($formName);

            foreach ($fieldArray as $fieldsetName => $fields) {
                $set = $form->addFieldset($fieldsetName);
                foreach ($fields as $name => $type) {
                    switch ($type) {
                        case 'attribute':
                            $set->addAttributeKeyControl($name);
                            break;
                        case 'association':
                            $set->addAssociationControl($name);
                            break;
                        case 'text':
                            $set->addTextControl('', $name); //skips headline for ease
                            break;
                        default:
                            break;
                    }
                }
            }
            $form = $form->save();
            if ($set_default == True) {
                $expressObject->setDefaultViewForm($form);
                $expressObject->setDefaultEditForm($form);
            }
        }

        return $expressObject;
    }

    /**
     * Does Express Entity Search
     * @param string $entity_handle
     * @param string $search_attribute_handle
     * @param string $search_value
     * @param string $comparison
     * @return array EntryListObjects
     */
    protected function getExpressEntries($entity_handle, $search_attribute_handle, $search_value, $comparison = "=")
    {
        $entity = Express::getObjectByHandle($entity_handle);
        $list = new EntryList($entity);
        $list->filterByAttribute($search_attribute_handle, $search_value, $comparison);
        return $list->getResults();
    }

    /**
     * Sets default Edit and View form for Express - Assumes entity is already set, not a builder
     * @param oject $entity
     * @param oject $form
     */
    protected function setDefaultForms($entity, $form)
    {
        $entity->setDefaultViewForm($form);
        $entity->setDefaultEditForm($form);
        $entityManager = \ORM::entityManager();
        $entityManager->persist($entity);
        $entityManager->flush();
    }
    
     /**
     * Gets packages current working directory
     * @param object $pkg
     * @return string
     */
    protected function getPackageCwd($pkg)
    {
        $cwd = getcwd();
        $pkg_path = $pkg->getRelativePath();
        return $cwd . $pkg_path;
    }

    /**
     * Imports an existing file into C5
     * @param string $file_path
     * @param string $cwd
     * @return boolean|Object
     */
    protected function importFile($file_path, $cwd)
    {
        if (!empty($file_path) && ($file_path != 'NULL')) {
            $importer = new FileImporter();
            $imported_file = $importer->import($cwd . '/files/' . $file_path);
            if (is_object($imported_file)) {
                return $imported_file->getFile();
            } else {
                error_log($file_path); //Error logging for files not imported
                error_log(print_r($imported_file));
                return false;
            }
        } else {
            return false;
        }
    }
}
