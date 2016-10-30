<?php

namespace Model;

class BasePackager extends BaseLinuxApp
{

    protected $packageName ;
    public $actionsToMethods =
        array(
            "pkg-install" => "performInstall",
            "pkg-remove" => "performRemove",
            "pkg-exists" => "performExistenceCheck",
            "update" => "performUpdate",
        ) ;

    public function __construct($params)
    {
        parent::__construct($params);
    }

    protected function getPackageName($packageName = null)
    {
        if (isset($packageName)) {
        } elseif (isset($this->params["packagename"])) {
            $packageName = $this->params["packagename"];
        } elseif (isset($this->params["package-name"])) {
            $packageName = $this->params["package-name"];
        } elseif (isset($autopilot["package-name"])) {
            $packageName = $autopilot["package-name"];
        } elseif (isset($autopilot["packagename"])) {
            $packageName = $autopilot["packagename"];
        } else {
            $packageName = self::askForInput("Enter Package Name:", true);
        }
        return $packageName ;
    }

    public function setPackage($packageName = null)
    {
        if (isset($packageName)) {
            $this->packageName = $packageName;
        } elseif (isset($this->params["packagename"])) {
            $this->packageName = $this->params["packagename"];
        } elseif (isset($this->params["package-name"])) {
            $this->packageName = $this->params["package-name"];
        } elseif (isset($autopilot["packagename"])) {
            $this->packageName = $autopilot["packagename"];
        } elseif (isset($autopilot["package-name"])) {
            $this->packageName = $autopilot["package-name"];
        } else {
            $this->packageName = self::askForInput("Enter Package Name:", true);
        }
    }

    protected function performInstall()
    {
        $this->setPackage() ;
        $result = $this->installPackage($this->packageName);
        return $result;
    }

    protected function performRemove()
    {
        $this->setPackage() ;
        $result = $this->removePackage($this->packageName);
        return $result ;
    }

    protected function performUpdate()
    {
        $result = $this->update();
        return $result ;
    }

    protected function performExistenceCheck()
    {
        $this->setPackage() ;
        $result = $this->isInstalled($this->packageName);
        return $result ;
    }
}
