<?php
/**
 * Modules Parser
 *
 * @author Eric COURTIAL <e.courtial30@gmail.com>
 */
namespace Enrico69\Magento2DevReport\Model;

use Enrico69\Magento2DevReport\Helper\Module;
use Magento\Framework\Filesystem\Directory\ReadFactory;

/**
 * Class ObserverParser
 * @package Enrico69\Magento2DevReport\Model
 */
class ObserverParser
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
     * Key for the observers in the global scope
     */
    const GLOBAL_KEY = 'global';

    /**
     * Key for the observers in the adminhtml scope
     */
    const ADMIN_HTML_KEY = 'adminhtml';

    /**
     * Key for the observers in the frontend scope
     */
    const FRONT_END_KEY = 'frontend';

    /**
     * ObserverParser constructor.
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
     * Return an array containing all the module's observers
     *
     * @param string $vendorName
     * @param string $moduleName
     * @return array
     */
    public function getObservers($vendorName, $moduleName)
    {
        $observers = [];
        $observers['total'] = 0;
        $etcModuleDir = $this->moduleHelper->getModuleAbsolutePath(
            $vendorName, $moduleName, DIRECTORY_SEPARATOR . 'etc');

        $this->getGlobalObservers($observers, $etcModuleDir);
        $this->getAdminObservers($observers, $etcModuleDir);
        $this->getFrontObservers($observers, $etcModuleDir);

        return $observers;
    }

    /**
     * Get the global observers
     *
     * @param mixed $observers
     * @param string $etcModuleDir
     */
    protected function getGlobalObservers(&$observers, $etcModuleDir)
    {
        $key = self::GLOBAL_KEY;
        $eventsFile = $etcModuleDir . DIRECTORY_SEPARATOR . 'events.xml';
        $dirReader = $this->directoryReaderFactory->create($etcModuleDir);
        $observers[$key] = [];
        $observers["total_{$key}"] = 0;

        if ($dirReader->isExist('events.xml')) {
            $this->parseEventsXML($observers, $eventsFile, $key);
        }
    }

    /**
     * Get the Adminhtml observers
     *
     * @param mixed $observers
     * @param string $etcModuleDir
     */
    protected function getAdminObservers(&$observers, $etcModuleDir)
    {
        $key = self::ADMIN_HTML_KEY;
        $extraPath = $key . DIRECTORY_SEPARATOR . 'events.xml';
        $eventsFile = $etcModuleDir . DIRECTORY_SEPARATOR . $extraPath;
        $dirReader = $this->directoryReaderFactory->create($etcModuleDir);
        $observers[$key] = [];
        $observers["total_{$key}"] = 0;

        if ($dirReader->isExist($extraPath)) {
            $this->parseEventsXML($observers, $eventsFile, $key);
        }
    }

    /**
     * Get the Frontend observers
     *
     * @param mixed $observers
     * @param string $etcModuleDir
     */
    protected function getFrontObservers(&$observers, $etcModuleDir)
    {
        $key = self::FRONT_END_KEY;
        $extraPath = 'frontend' . DIRECTORY_SEPARATOR . 'events.xml';
        $eventsFile = $etcModuleDir . DIRECTORY_SEPARATOR . $extraPath;
        $dirReader = $this->directoryReaderFactory->create($etcModuleDir);
        $observers[$key] = [];
        $observers["total_{$key}"] = 0;

        if ($dirReader->isExist($extraPath)) {
            $this->parseEventsXML($observers, $eventsFile, $key);
        }
    }

    /**
     * Parse an events xml file
     *
     * @param array $observers
     * @param string $eventsFile
     * @param string $key
     */
    protected function parseEventsXML(&$observers, $eventsFile, $key)
    {
        $xml = simplexml_load_file($eventsFile);
        foreach ($xml->children()->event as $event)
        {
            $observer = [];
            $observer['name'] = (string) $event['name'];
            $observer['instance'] = (string) $event->observer['instance'];

            $observers[$key][] = $observer;
        }

        // Update the totals
        $observers["total_{$key}"] = count($observers[$key]);
        $observers['total'] = $observers['total'] + $observers["total_{$key}"];
    }
}