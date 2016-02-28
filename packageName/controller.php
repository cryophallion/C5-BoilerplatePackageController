<?php
namespace Concrete\Package\PackageName;

use Package;
use AttributeSet;
use \Concrete\Core\Attribute\Key\Category as AttributeKeyCategory;

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
}
