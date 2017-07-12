<?php
/**
 * Command for observers report
 *
 * @author Eric COURTIAL <e.courtial30@gmail.com>
 */
namespace Enrico69\Magento2DevReport\Console\Command;

use Symfony\Component\Console\Output\OutputInterface;
use Enrico69\Magento2DevReport\Console\ReportCommandAbstract;
use Enrico69\Magento2DevReport\Model\ModuleParser;
use Enrico69\Magento2DevReport\Model\ReportGenerator;
use Enrico69\Magento2DevReport\Model\ObserverParser;

/**
 * Class ObserverReportCommand
 * @package Enrico69\Magento2DevReport\Console\Command
 */
class ObserverReportCommand extends ReportCommandAbstract
{
    /**
     * @var \Magento\Developer\Model\Dependency\ObserverParser
     */
    protected $observerParser;

    /**
     * Configure the command
     */
    protected function configure()
    {
        $this->setName('dev:observer:report');
        $this->setDescription('Generate a report of all user-created observers');
        $this->addModuleArgument();
    }

    /**
     * ObserverReportCommand constructor.
     * @param \Magento\Developer\Model\Dependency\ModuleParser $moduleParser
     * @param \Magento\Developer\Model\Dependency\ReportGenerator $reportGenerator
     * @param \Magento\Developer\Model\Dependency\ObserverParser $observerParser
     * @param null $name
     */
    public function __construct(
        ModuleParser $moduleParser,
        ReportGenerator $reportGenerator,
        ObserverParser $observerParser, $name = null) {
        parent::__construct($moduleParser, $reportGenerator, $name);
        $this->moduleParser = $moduleParser;
        $this->observerParser= $observerParser;
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
        $events = [];
        $strHtmlReportObserversPart = "";

        // Run through the vendors list to generate the
        // first part of the report and create and index of events
        foreach ($modulesList as $vendorName => $modules) {
            // Run through the modules list
            foreach($modules as $moduleName => $observers) {
                // Store the observers for the second part of the report
                // There they will be indexed not by module, but by events
                $this->storeEvents($vendorName . '_' . $moduleName, $events, $observers);

                // Start the report
                // Observers part
                $strHtmlReportObserversPart .= $this->reportGenerator->getTitleElement($vendorName . '_' . $moduleName);
                $strHtmlReportObserversPart .= $this->generateMainTable($observers);
            }
        }
        // Events part
        $strHtmlReportEventsPart = $this->generateSecondTable($events);

        return $this->reportGenerator->writeReportFile('Observers report','Observers', $strHtmlReportObserversPart,
            'Events', $strHtmlReportEventsPart, $this->reportMessages);
    }

    /**
     * Update the index of the observers ordered by events.
     * It will be used to generate the second part of the report.
     *
     * @param string $moduleName
     * @param array $events
     * @param array $observers
     */
    protected function storeEvents($moduleName, &$events, &$observers)
    {
        foreach (self::SCOPE_KEYS as $key) {
            foreach ($observers[$key] as $observer) {
                $events[$observer['name']][$key][] = [
                    'instance' => $observer['instance'],
                    'module' => $moduleName
                ];
            }
        }
    }

    /**
     * Collect all the observers for each module
     * @param array $modulesList
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return void
     */
    protected function collectData(&$modulesList, OutputInterface $output)
    {
        $output->writeln("Scanning observers...");

        foreach ($modulesList as $vendorName => $modules) {
            // Makes the module name to be keys
            $modulesList[$vendorName] = array_flip($modulesList[$vendorName]);

            // Processes all the modules
            foreach($modulesList[$vendorName] as $moduleName => &$value) {
                $value = $this->observerParser->getObservers($vendorName, $moduleName);
                // Removing modules which don't have any observers
                if ($value['total'] === 0) {
                    unset($modulesList[$vendorName][$moduleName]);
                }
            }
        }
    }
}