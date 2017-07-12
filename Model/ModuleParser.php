<?php
/**
 * Module Parser
 *
 * @author Eric COURTIAL <e.courtial30@gmail.com>
 */
namespace Enrico69\Magento2DevReport\Model;

use Magento\Framework\Filesystem;

/**
 * Class ModuleParser
 * @package Enrico69\Magento2DevReport\Model
 */
class ModuleParser
{
    /**
     * The list of modules
     * read the app/code directory
     *
     * @var array
     */
    protected $moduleList;

    /**
     * Filesystem tool
     *
     * @var \Magento\Framework\Filesystem
     */
    protected $fileSystem;

    /**
     * Store the module to not
     * be scanned
     *
     * @var array
     */
    protected $untrackedVendors = ['Magento'];

    /**
     * ModuleParser constructor.
     *
     * @param \Magento\Framework\Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->fileSystem = $filesystem;
    }

    /**
     * Return an array where the key is the
     * vendor name and the value an array of modules name
     *
     * @param boolean $forceScan force to rescan
     *
     * @return array
     */
    public function getModuleList($forceScan = false)
    {
        if ($this->moduleList === null || $forceScan) {
            $this->scanDir();
        }

        return $this->moduleList;
    }

    /**
     * Scan the app/code directory
     *
     * @return void
     * @throws \Exception
     */
    protected function scanDir()
    {
        $this->moduleList = [];
        foreach ($this->getVendorList() as $vendorName) {
            $this->moduleList[$vendorName] = [];
        }

        $this->readModules();
    }

    /**
     * Return the 'app/code' absolute path
     *
     * @return string the app/code directory absolute path
     */
    protected function getAppCodeAbsolutePath()
    {
        $appDirectory = $this->fileSystem->getDirectoryRead('app');

        return $appDirectory->getAbsolutePath() . 'code';
    }

    /**
     * Extract the vendor list
     *
     * @return array containing the vendor list
     * @throws \Exception
     */
    protected function getVendorList()
    {
        $directoryIterator = new \DirectoryIterator($this->getAppCodeAbsolutePath());
        if (!$directoryIterator->isReadable()) {
            throw new \Exception('Impossible to read the app/ directory');
        }
        $vendorList = [];

        foreach ($directoryIterator as $element) {
            if ($element->isDir()
                && !in_array($element->getBasename(), $this->untrackedVendors)
                && $this->isBaseNameValid($element->getBasename())
            ) {
                $vendorList[] = $element->getBasename();
            }
        }

        return $vendorList;
    }

    /**
     * Check if the basename is valid
     *
     * @param string $baseName is the dir name
     *
     * @return bool
     */
    protected function isBaseNameValid($baseName)
    {
        $blnStatus = false;
        if ($baseName != "." && $baseName != "..") {
            $blnStatus = true;
        }

        return $blnStatus;
    }


    /**
     * Extract the list of the modules for each vendor
     *
     * @return void
     */
    protected function readModules()
    {
        foreach ($this->moduleList as $vendorName => &$modulesArray) {
            $directoryIterator = new \DirectoryIterator(
                $this->getAppCodeAbsolutePath() . DIRECTORY_SEPARATOR . $vendorName
            );
            foreach ($directoryIterator as $element) {
                if ($element->isDir()
                    && $this->isBaseNameValid($element->getBasename())
                ) {
                    $modulesArray[] = $element->getBasename();
                }
            }
        }
    }

    /**
     * Check if a module directory exists
     *
     * @param string $vendor
     * @param string $name
     * @throws \UnexpectedValueException
     * @throws \Exception
     * @return boolean
     */
    public function checkIfModuleExists($vendor, $name)
    {
        $fullPath = $this->getAppCodeAbsolutePath() . DIRECTORY_SEPARATOR . $vendor . DIRECTORY_SEPARATOR . $name;
        $status = true;
        try {
            new \DirectoryIterator($fullPath);
        } catch (\UnexpectedValueException $ex) {
            unset($ex);
            $status = false;
        }

        return $status;
    }
}
