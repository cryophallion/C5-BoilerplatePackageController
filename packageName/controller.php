<?php
namespace Concrete\Package\PackageName;

use Package;

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

}
