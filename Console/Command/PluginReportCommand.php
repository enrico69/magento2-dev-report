<?php
/**
 * Command for plugins report
 *
 * @author Eric COURTIAL <e.courtial30@gmail.com>
 */
namespace Enrico69\Magento2DevReport\Console\Command;

use Symfony\Component\Console\Output\OutputInterface;
use Enrico69\Magento2DevReport\Console\ReportCommandAbstract;
use Enrico69\Magento2DevReport\Model\ModuleParser;
use Enrico69\Magento2DevReport\Model\ReportGenerator;
use Enrico69\Magento2DevReport\Model\PluginParser;

/**
 * Class PluginReportCommand
 * @package Enrico69\Magento2DevReport\Console\Command
 */
class PluginReportCommand extends ReportCommandAbstract
{
    /**
     * @var \Enrico69\Magento2DevReport\Model\PluginParser
     */
    protected $pluginParser;

    /**
     * Configure the command
     */
    protected function configure()
    {
        $this->setName('dev:plugin:report');
        $this->setDescription('Generate a report of all user-created plugins');
        $this->addModuleArgument();
    }

    /**
     * PluginReportCommand constructor.
     * @param \Enrico69\Magento2DevReport\Model\ModuleParser $moduleParser
     * @param \Enrico69\Magento2DevReport\Model\ReportGenerator $reportGenerator
     * @param \Enrico69\Magento2DevReport\Model\PluginParser $pluginParser
     * @param null $name
     */
    public function __construct(
        ModuleParser $moduleParser,
        ReportGenerator $reportGenerator,
        PluginParser $pluginParser, $name = null)
    {
        parent::__construct($moduleParser, $reportGenerator, $name);
        $this->moduleParser = $moduleParser;
        $this->pluginParser= $pluginParser;
        $this->reportGenerator = $reportGenerator;
    }

    /**
     * Generate the report
     *
     * @param array $modulesList
     * @return string the name of the report file
     */
    protected function generateReport($modulesList)
    {
        $targetClasses = [];
        $strHtmlReportPluginsPart = "";

        // Run through the vendors list to generate the
        // first part of the report and create and index of plugins
        foreach ($modulesList as $vendorName => $modules) {
            // Run through the modules list
            foreach ($modules as $moduleName => $plugins) {
                // Store the $plugins for the second part of the report
                // There they will be indexed not by module, but by target class
                $this->storeTargets($vendorName . '_' . $moduleName, $targetClasses, $plugins);

                // Start the report

                // plugins part
                $strHtmlReportPluginsPart .= $this->reportGenerator->getTitleElement($vendorName . '_' . $moduleName);
                $strHtmlReportPluginsPart .= $this->generateMainTable($plugins);
            }
        }

        // Plugins classes part
        $strHtmlReportTargetsPart = $this->generateSecondTable($targetClasses);

        return $this->reportGenerator->writeReportFile('Plugins report', 'Plugins', $strHtmlReportPluginsPart,
            'Target class', $strHtmlReportTargetsPart, $this->reportMessages);
    }

    /**
     * Update the index of the plugins ordered by the target class.
     * It will be used to generate the second part of the report.
     *
     * @param string $moduleName
     * @param array $targetClasses
     * @param array $plugins
     */
    protected function storeTargets($moduleName, &$targetClasses, &$plugins)
    {
        foreach (self::SCOPE_KEYS as $key) {
            foreach ($plugins[$key] as $plugin) {
                $targetClasses[$plugin['name']][$key][] = [
                    'instance' => $plugin['instance'],
                    'module' => $moduleName
                ];
            }
        }
    }

    /**
     * Collect all the plugins for each module
     * @param array $modulesList
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return void
     */
    protected function collectData(&$modulesList, OutputInterface $output)
    {
        $output->writeln("Scanning plugins...");

        foreach ($modulesList as $vendorName => $modules) {
            // Makes the module name to be keys
            $modulesList[$vendorName] = array_flip($modulesList[$vendorName]);

            // Processes all the modules
            foreach ($modulesList[$vendorName] as $moduleName => &$value) {
                $value = $this->pluginParser->getPlugins($vendorName, $moduleName);
                // Removing modules which don't have any observers
                if ($value['total'] === 0) {
                    unset($modulesList[$vendorName][$moduleName]);
                }
            }
        }
    }
}
