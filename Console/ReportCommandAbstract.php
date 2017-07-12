<?php
/**
 * Report abstract command
 *
 * @author Eric COURTIAL <e.courtial30@gmail.com>
 */
namespace Enrico69\Magento2DevReport\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Enrico69\Magento2DevReport\Model\ModuleParser;
use Enrico69\Magento2DevReport\Model\ReportGenerator;

/**
 * Class ReportCommandAbstract
 * @package Enrico69\Magento2DevReport\Console
 */
abstract class ReportCommandAbstract extends Command
{
    /**
     * @var \Enrico69\Magento2DevReport\Model\ModuleParser
     */
    protected $moduleParser;

    /**
     * The keys of the scopes to be scanned
     */
    const SCOPE_KEYS = ['global', 'adminhtml', 'frontend'];

    /**
     * @var array
     */
    protected $reportMessages = [];

    /**
     * @var \Enrico69\Magento2DevReport\Model\ReportGenerator
     */
    protected $reportGenerator;

    /**
     * ReportCommandAbstract constructor.
     * @param \Enrico69\Magento2DevReport\Model\ModuleParser $moduleParser
     * @param \Enrico69\Magento2DevReport\Model\ReportGenerator $reportGenerator
     * @param null $name
     */
    public function __construct(
        ModuleParser $moduleParser,
        ReportGenerator $reportGenerator, $name = null) {
        parent::__construct($name);
        $this->moduleParser = $moduleParser;
        $this->reportGenerator = $reportGenerator;
    }

    /**
     * Execute the command
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $moduleName = $input->getArgument('module');
        if($moduleName) { // Scan one specified module
            $modulesList = $this->getSingleModule($moduleName);
        } else { // Scan all modules
            $output->writeln("Scanning modules...");
            $modulesList = $this->moduleParser->getModuleList();
        }

        $this->collectData($modulesList, $output);
        $output->writeln("Generating report...");
        $fileName = $this->generateReport($modulesList);
        $output->writeln("Report successfully generated: " .
            "var" .  DIRECTORY_SEPARATOR . $fileName);
    }

    /**
     * Add a message a the sum-up of the report
     * @param string $message
     * @param string $type
     */
    protected function addReportMessage($message, $type)
    {
        $this->reportMessages[] = ['level' => $type, 'msg' => $message];
    }

    /**
     * Check if a specified module exists and return it
     * @param string $moduleName
     * @return array
     * @throws \Exception
     */
    protected function getSingleModule($moduleName)
    {
        $array = explode("_", $moduleName);
        if(count($array) !== 2 || mb_strlen(trim($array[0])) === 0
            || mb_strlen(trim($array[1])) === 0) {
            throw new \Exception("The module name pattern is invalid");
        }

        if(!$this->moduleParser->checkIfModuleExists($array[0], $array[1])) {
            throw new \Exception("The module directory doesn't exist");
        }

        return [$array[0] => [$array[1]]];
    }

    /**
     * Handle basic verification of the class which handle the element
     * Check if the class exist. Note that PHP will rise a fatal error
     * if any compulsory method is missing or not properly declared
     *
     * @param string $classFullName
     * @return void
     */
    protected function checkClass($classFullName)
    {
        try {
            new \ReflectionClass($classFullName);
        } catch (\ReflectionException $refEx) {
            $this->addReportMessage("Class not found: $classFullName", 'error');
        } catch(\Exception $ex) {
            $this->addReportMessage("Impossible to reflect class: $classFullName", 'error');
        }
    }

    /**
     * Generate the main table (the first one)
     * @param array $elements
     * @return string
     */
    protected function generateMainTable(&$elements)
    {
        $lines = "";
        foreach (self::SCOPE_KEYS as $key) {
            foreach ($elements[$key] as $element) {
                $lines .= $this->reportGenerator->getLine($element['name'], $key, $element['instance']);
                $this->checkClass($element['instance']);
            }
        }

        return $this->reportGenerator->getBlock('Event name', 'Scope', 'Instance', $lines);
    }

    /**
     * Generate the second part of the report:
     * with an reversed approach
     *
     * @param $elements
     * @return string
     */
    protected function generateSecondTable($elements)
    {
        $htmlContent = "";

        foreach($elements as $name => $element) {
            $title = $this->reportGenerator->getTitleElement($name);
            $lines = "";
            foreach($element as $scope => $entries) {
                foreach($entries as $entry) {
                    $lines .= $this->reportGenerator->getLine($entry['module'], $scope, $entry['instance']);
                    $htmlContent .= $title . $this->reportGenerator->getBlock(
                            'Module', 'Scope', 'Instance', $lines);
                }
            }

        }

        return $htmlContent;
    }

    protected function addModuleArgument()
    {
        $this->addArgument('module', InputArgument::OPTIONAL, 'Module name, on this pattern: Vendor_ModuleName');
    }

    /**
     * Collect all the scanned type occurences
     * @param array $modulesList
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return void
     */
    protected abstract function collectData(&$modulesList, OutputInterface $output);

    /**
     * Generate the report
     *
     * @param array $modulesList
     * @return string the name of the report file
     */
    protected abstract function generateReport($modulesList);
}