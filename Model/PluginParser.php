<?php
/**
 * Plugins Parser
 *
 * @author Eric COURTIAL <e.courtial30@gmail.com>
 */
namespace Enrico69\Magento2DevReport\Model;

use Enrico69\Magento2DevReport\Helper\Module;
use Magento\Framework\Filesystem\Directory\ReadFactory;

/**
 * Class PluginParser
 * @package Enrico69\Magento2DevReport\Model
 */
class PluginParser
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
     * Key for the plugins in the global scope
     */
    const GLOBAL_KEY = 'global';

    /**
     * Key for the plugins in the adminhtml scope
     */
    const ADMIN_HTML_KEY = 'adminhtml';

    /**
     * Key for the plugins in the frontend scope
     */
    const FRONT_END_KEY = 'frontend';

    /**
     * PluginParser constructor.
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
     * @param $vendorName
     * @param $moduleName
     * @return array
     */
    public function getPlugins($vendorName, $moduleName)
    {
        $plugins = [];
        $plugins['total'] = 0;
        $etcModuleDir = $this->moduleHelper->getModuleAbsolutePath(
            $vendorName, $moduleName, DIRECTORY_SEPARATOR . 'etc');

        $this->getGlobalPlugins($plugins, $etcModuleDir);
        $this->getAdminPlugins($plugins, $etcModuleDir);
        $this->getFrontPlugins($plugins, $etcModuleDir);

        return $plugins;
    }

    /**
     * Get the global plugins
     *
     * @param mixed $plugins
     * @param string $etcModuleDir
     */
    protected function getGlobalPlugins(&$plugins, $etcModuleDir)
    {
        $key = self::GLOBAL_KEY;
        $diFile = $etcModuleDir . DIRECTORY_SEPARATOR . 'di.xml';
        $dirReader = $this->directoryReaderFactory->create($etcModuleDir);
        $plugins[$key] = [];
        $plugins["total_{$key}"] = 0;

        if ($dirReader->isExist('di.xml')) {
            $this->parseDiXML($plugins, $diFile, $key);
        }
    }

    /**
     * Get the Adminhtml plugins
     *
     * @param mixed $plugins
     * @param string $etcModuleDir
     */
    protected function getAdminPlugins(&$plugins, $etcModuleDir)
    {
        $key = self::ADMIN_HTML_KEY;
        $extraPath = $key . DIRECTORY_SEPARATOR . 'di.xml';
        $diFile = $etcModuleDir . DIRECTORY_SEPARATOR . $extraPath;
        $dirReader = $this->directoryReaderFactory->create($etcModuleDir);
        $plugins[$key] = [];
        $plugins["total_{$key}"] = 0;

        if ($dirReader->isExist($extraPath)) {
            $this->parseDiXML($plugins, $diFile, $key);
        }
    }

    /**
     * Get the Frontend plugins
     *
     * @param mixed $plugins
     * @param string $etcModuleDir
     */
    protected function getFrontPlugins(&$plugins, $etcModuleDir)
    {
        $key = self::FRONT_END_KEY;
        $extraPath = 'frontend' . DIRECTORY_SEPARATOR . 'di.xml';
        $diFile = $etcModuleDir . DIRECTORY_SEPARATOR . $extraPath;
        $dirReader = $this->directoryReaderFactory->create($etcModuleDir);
        $plugins[$key] = [];
        $plugins["total_{$key}"] = 0;

        if ($dirReader->isExist($extraPath)) {
            $this->parseDiXML($plugins, $diFile, $key);
        }
    }

    /**
     * Parse an di xml file to look for plugins
     *
     * @param array $plugins
     * @param string $diFile
     * @param string $key
     */
    protected function parseDiXML(&$plugins, $diFile, $key)
    {
        $xml = simplexml_load_file($diFile);
        foreach ($xml->children()->type as $element) {
            if ($element->plugin) {
                $plugin = [];
                $plugin['name'] = (string)$element['name'];
                $plugin['instance'] = (string)$element->plugin['type'];

                $plugins[$key][] = $plugin;
            }
        }

        // Update the totals
        $plugins["total_{$key}"] = count($plugins[$key]);
        $plugins['total'] = $plugins['total'] + $plugins["total_{$key}"];
    }
}
