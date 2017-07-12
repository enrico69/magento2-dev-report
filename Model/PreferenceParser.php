<?php
/**
 * Preferences Parser
 *
 * @author Eric COURTIAL <e.courtial30@gmail.com>
 */
namespace Enrico69\Magento2DevReport\Model;

use Enrico69\Magento2DevReport\Helper\Module;
use Magento\Framework\Filesystem\Directory\ReadFactory;

/**
 * Class PreferenceParser
 * @package Enrico69\Magento2DevReport\Model
 */
class PreferenceParser
{
    /**
     * @var \Enrico69\Magento2DevReport\Helper\Module
     */
    protected $moduleHelper;

    /**
     * @var \Magento\Framework\Filesystem\Directory\ReadFactory
     */
    protected $directoryReaderFactory;

    /**
     * Key for the preferences in the global scope
     */
    const GLOBAL_KEY = 'global';

    /**
     * Key for the preferences in the adminhtml scope
     */
    const ADMIN_HTML_KEY = 'adminhtml';

    /**
     * Key for the preferences in the frontend scope
     */
    const FRONT_END_KEY = 'frontend';

    /**
     * PreferenceParser constructor.
     * @param \Enrico69\Magento2DevReport\Helper\Module $moduleHelper
     * @param \Magento\Framework\Filesystem\Directory\ReadFactory $directoryReaderFactory
     */
    public function __construct(
        Module $moduleHelper, ReadFactory $directoryReaderFactory
    ) {
        $this->moduleHelper = $moduleHelper;
        $this->directoryReaderFactory = $directoryReaderFactory;
    }

    /**
     * Return an array containing all the module's preferences
     *
     * @param string $vendorName
     * @param string $moduleName
     * @return array
     */
    public function getPreferences($vendorName, $moduleName)
    {
        $preferences = [];
        $preferences['total'] = 0;
        $etcModuleDir = $this->moduleHelper->getModuleAbsolutePath(
            $vendorName, $moduleName, DIRECTORY_SEPARATOR . 'etc');

        $this->getGlobalPreferences($preferences, $etcModuleDir);
        $this->getAdminPreferences($preferences, $etcModuleDir);
        $this->getFrontPreferences($preferences, $etcModuleDir);

        return $preferences;
    }

    /**
     * Get the global preferences
     *
     * @param mixed $preferences
     * @param string $etcModuleDir
     */
    protected function getGlobalPreferences(&$preferences, $etcModuleDir)
    {
        $key = self::GLOBAL_KEY;
        $diFile = $etcModuleDir . DIRECTORY_SEPARATOR . 'di.xml';
        $dirReader = $this->directoryReaderFactory->create($etcModuleDir);
        $preferences[$key] = [];
        $preferences["total_{$key}"] = 0;

        if ($dirReader->isExist('di.xml')) {
            $this->parseDiXML($preferences, $diFile, $key);
        }
    }

    /**
     * Get the Adminhtml preferences
     *
     * @param mixed $preferences
     * @param string $etcModuleDir
     */
    protected function getAdminPreferences(&$preferences, $etcModuleDir)
    {
        $key = self::ADMIN_HTML_KEY;
        $extraPath = $key . DIRECTORY_SEPARATOR . 'di.xml';
        $diFile = $etcModuleDir . DIRECTORY_SEPARATOR . $extraPath;
        $dirReader = $this->directoryReaderFactory->create($etcModuleDir);
        $preferences[$key] = [];
        $preferences["total_{$key}"] = 0;

        if ($dirReader->isExist($extraPath)) {
            $this->parseDiXML($preferences, $diFile, $key);
        }
    }

    /**
     * Get the Frontend preferences
     *
     * @param mixed $preferences
     * @param string $etcModuleDir
     */
    protected function getFrontPreferences(&$preferences, $etcModuleDir)
    {
        $key = self::FRONT_END_KEY;
        $extraPath = 'frontend' . DIRECTORY_SEPARATOR . 'di.xml';
        $diFile = $etcModuleDir . DIRECTORY_SEPARATOR . $extraPath;
        $dirReader = $this->directoryReaderFactory->create($etcModuleDir);
        $preferences[$key] = [];
        $preferences["total_{$key}"] = 0;

        if ($dirReader->isExist($extraPath)) {
            $this->parseDiXML($preferences, $diFile, $key);
        }
    }

    /**
     * Parse an di xml file to look for preferences
     *
     * @param array $preferences
     * @param string $diFile
     * @param string $key
     */
    protected function parseDiXML(&$preferences, $diFile, $key)
    {
        $xml = simplexml_load_file($diFile);
        foreach ($xml->children()->preference as $element) {
            $preference = [];
            $preference['name'] = (string) $element['for'];
            $preference['instance'] = (string) $element['type'];

            $preferences[$key][] = $preference;
        }

        // Update the totals
        $preferences["total_{$key}"] = count($preferences[$key]);
        $preferences['total'] = $preferences['total'] + $preferences["total_{$key}"];
    }
}
