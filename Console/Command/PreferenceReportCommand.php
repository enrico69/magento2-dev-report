<?php
/**
 * Command for preferences report
 *
 * @author Eric COURTIAL <e.courtial30@gmail.com>
 */
namespace Enrico69\Magento2DevReport\Console\Command;

use Symfony\Component\Console\Output\OutputInterface;
use Enrico69\Magento2DevReport\Console\ReportCommandAbstract;
use Enrico69\Magento2DevReport\Model\ModuleParser;
use Enrico69\Magento2DevReport\Model\ReportGenerator;
use Enrico69\Magento2DevReport\Model\PreferenceParser;

/**
 * Class PreferenceReportCommand
 * @package Enrico69\Magento2DevReport\Console\Command
 */
class PreferenceReportCommand extends ReportCommandAbstract
{
    /**
     * @var \Enrico69\Magento2DevReport\Model\PreferenceParser
     */
    protected $preferenceParser;

    /**
     * @var array
     */
    protected $overridedClasses = [];

    /**
     * Configure the command
     */
    protected function configure()
    {
        $this->setName('dev:preference:report');
        $this->setDescription('Generate a report of all user-created preferences');
        $this->addModuleArgument();
    }

    /**
     * PreferenceReportCommand constructor.
     * @param \Enrico69\Magento2DevReport\Model\ModuleParser $moduleParser
     * @param \Enrico69\Magento2DevReport\Model\ReportGenerator $reportGenerator
     * @param \Enrico69\Magento2DevReport\Model\PreferenceParser $preferenceParser
     * @param null $name
     */
    public function __construct(
        ModuleParser $moduleParser,
        ReportGenerator $reportGenerator,
        PreferenceParser $preferenceParser, $name = null)
    {
        parent::__construct($moduleParser, $reportGenerator, $name);
        $this->moduleParser = $moduleParser;
        $this->preferenceParser= $preferenceParser;
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
        $strHtmlReportPreferencesPart = "";

        // Run through the vendors list to generate the
        // first part of the report and create and index of preferences
        foreach ($modulesList as $vendorName => $modules) {
            // Run through the modules list
            foreach ($modules as $moduleName => $preferences) {
                // Store the $preferences for the second part of the report
                // There they will be indexed not by module, but by target class
                $this->storeTargets($vendorName . '_' . $moduleName, $targetClasses, $preferences);

                // Start the report

                // Preferences part
                $strHtmlReportPreferencesPart .= $this->reportGenerator->getTitleElement($vendorName . '_' . $moduleName);
                $strHtmlReportPreferencesPart .= $this->generateMainTable($preferences);
            }
        }

        // Overrided classes part
        $strHtmlReportTargetsPart = $this->generateSecondTable($targetClasses);

        // Control the quantity of override for each class
        $this->checkOverridesQuantity();

        return $this->reportGenerator->writeReportFile('Preferences report', 'Preferences', $strHtmlReportPreferencesPart,
            'Target class', $strHtmlReportTargetsPart, $this->reportMessages);
    }

    /**
     * Update the index of the preferences ordered by the target class.
     * It will be used to generate the second part of the report.
     *
     * @param string $moduleName
     * @param array $targetClasses
     * @param array $preferences
     */
    protected function storeTargets($moduleName, &$targetClasses, &$preferences)
    {
        foreach (self::SCOPE_KEYS as $key) {
            foreach ($preferences[$key] as $preference) {
                $this->storeTargetClassOverrideQty($preference['name']);
                $targetClasses[$preference['name']][$key][] = [
                    'instance' => $preference['instance'],
                    'module' => $moduleName
                ];
            }
        }
    }

    /**
     * Update the qty of overrides for each lass
     * @param string $class
     */
    protected function storeTargetClassOverrideQty($class)
    {
        if (array_key_exists($class, $this->overridedClasses)) {
            $this->overridedClasses[$class]++;
        } else {
            $this->overridedClasses[$class] = 1;
        }
    }

    /**
     * Check the qty of override for each target class
     */
    protected function checkOverridesQuantity()
    {
        foreach ($this->overridedClasses as $className => $qty) {
            if ($qty > 1) {
                $this->addReportMessage("The class $className has been preferred many times", "warning");
            }
        }
    }

    /**
     * Collect all the preferences for each module
     * @param array $modulesList
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return void
     */
    protected function collectData(&$modulesList, OutputInterface $output)
    {
        $output->writeln("Scanning preferences...");

        foreach ($modulesList as $vendorName => $modules) {
            // Makes the module name to be keys
            $modulesList[$vendorName] = array_flip($modulesList[$vendorName]);

            // Processes all the modules
            foreach ($modulesList[$vendorName] as $moduleName => &$value) {
                $value = $this->preferenceParser->getPreferences($vendorName, $moduleName);
                // Removing modules which don't have any preferences
                if ($value['total'] === 0) {
                    unset($modulesList[$vendorName][$moduleName]);
                }
            }
        }
    }
}
