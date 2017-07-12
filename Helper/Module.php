<?php
/**
 * Module Helper
 *
 * @author Eric COURTIAL <e.courtial30@gmail.com>
 */
namespace Enrico69\Magento2DevReport\Helper;

use Magento\Framework\Filesystem;

/**
 * Class Module
 * @package Enrico69\Magento2DevReport\Helper
 */
class Module
{
    /**
     * @var Filesystem
     */
    protected $fileSystem;

    /**
     * ObserverParser constructor.
     * @param Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->fileSystem = $filesystem;
    }

    /**
     * @param string $vendorName
     * @param string $moduleName
     * @param string $additionalString
     * @return string
     */
    public function getModuleAbsolutePath($vendorName, $moduleName, $additionalString = "")
    {
        $appDirectory = $this->fileSystem->getDirectoryRead('app');

        return $appDirectory->getAbsolutePath() . 'code' .
            DIRECTORY_SEPARATOR . $vendorName . DIRECTORY_SEPARATOR .
            $moduleName . $additionalString;
    }
}
