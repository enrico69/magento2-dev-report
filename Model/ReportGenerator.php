<?php
/**
 * Report Generator
 *
 * @author Eric COURTIAL <e.courtial30@gmail.com>
 */
namespace Enrico69\Magento2DevReport\Model;

use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadFactory;

class ReportGenerator
{
    /**
     * @var string
     */
    protected $reportContent = "";

    public function __construct(Filesystem $filesystem, ReadFactory $readFactory)
    {
        $this->fileSystem = $filesystem;
        $this->readFactory = $readFactory;

        $this->readTemplateContent();
    }

    /**
     * Generate the report block
     * @param array $reportMessages
     * @return string
     */
    public function generateReportBlock($reportMessages)
    {
        if (count($reportMessages) === 0) {
            $strResult = $this->getMsgLine('No error detected', 'infoMsg');
        } else {
            $strResult = "";
            foreach ($reportMessages as $message) {
                switch ($message['level']) {
                    case 'info':
                        $cssClass = 'infoMsg';
                        break;
                    case 'warning':
                        $cssClass = 'warningMsg';
                        break;
                    case 'error':
                        $cssClass = 'errorMsg';
                        break;
                    default:
                        $cssClass = 'infoMsg';
                        break;
                }
                $strResult .= $this->getMsgLine($message['msg'], $cssClass);
            }
        }

        return $strResult;
    }

    /**
     * Read the content on the report template
     */
    protected function readTemplateContent()
    {
        $templatePath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR .
            DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR . 'report';
        $fileReader = $this->readFactory->create($templatePath);
        $this->reportContent = $fileReader->readFile('report.html');
    }

    /**
     * Write the report file
     *
     * @param string $reportTitle
     * @param string $filePrefix
     * @param string $modulesPart
     * @param string $elementsPart
     * @param string $elementTitle
     * @param array $reportMessages
     * @return string $fileName
     */
    public function writeReportFile($reportTitle, $filePrefix, $modulesPart, $elementTitle, $elementsPart, $reportMessages)
    {
        $date = date('Y-m-d H:i:s');
        $fileDate = str_replace([' ', ':'], ['_', '-'], $date);
        $varDirectory = $this->fileSystem->getDirectoryWrite('var');
        $fileName = "{$filePrefix}-{$fileDate}.html";

        $this->reportContent = str_replace('@generationDate@', $date, $this->reportContent);
        $this->reportContent = str_replace('@modulesContent@', $modulesPart, $this->reportContent);
        $this->reportContent = str_replace('@Target@', $elementTitle, $this->reportContent);
        $this->reportContent = str_replace('@Title@', $reportTitle, $this->reportContent);
        $this->reportContent = str_replace('@targetContent@', $elementsPart, $this->reportContent);
        $this->reportContent = str_replace('@reportCheckContent@',
            $this->generateReportBlock($reportMessages), $this->reportContent);

        $varDirectory->writeFile($fileName, $this->reportContent);

        return $fileName;
    }

    /**
     * Generate a three-columns line
     *
     * @param string $leftColumn
     * @param string $middleColumn
     * @param string $rightColumn
     * @return string
     */
    public function getLine($leftColumn, $middleColumn, $rightColumn)
    {
        return <<<EOD
        <tr>
            <td>$leftColumn</td>
            <td>$middleColumn</td>
            <td>$rightColumn</td>
        </tr>
EOD;
    }

    /**
     * Generate a HTML line for the report sum-up
     *
     * @param $msg
     * @param $class
     * @return string
     */
    protected function getMsgLine($msg, $class)
    {
        return <<<EOD
            <tr>
                <td class="$class">$msg</td>
            </tr>
EOD;
    }

    /**
     * Generate a title line for the report
     *
     * @param string $title
     * @return string
     */
    public function getTitleElement($title)
    {
        $str = <<<EOD
        <table>
            <tr>
                <th class="moduleTitle">$title</th>
            </tr>
        </table>
EOD;

        return $str;
    }

    /**
     * Generate a block of content: the three
     * columns names and the lines related to them
     *
     * @param string $leftColumn
     * @param string $middleColumn
     * @param string $rightColumn
     * @param $content
     * @return string
     */
    public function getBlock($leftColumn, $middleColumn, $rightColumn, $content)
    {
        return <<<EOD
           <table>
        <tr>
            <th class="columnHeadTitle" id="leftColum">$leftColumn</th>
            <th class="columnHeadTitle" id="middleColum">$middleColumn</th>
            <th class="columnHeadTitle" id="rightColum">$rightColumn</th>
        </tr>
        $content
    </table>
EOD;
    }
}
