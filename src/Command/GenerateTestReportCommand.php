<?php

namespace Zu\PHPUnitReportGeneratorBundle\Command;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;

class GenerateTestReportCommand extends Command
{
    private const REPORT_FILE_XML = __DIR__.'/report.xml';
    private const SUCCESS_COLOR = '00FF00';
    private const FAILURE_COLOR = 'FF0000';

    private ProgressBar $progressBar;
    private string $outputPath;

    public function __construct(KernelInterface $kernel, string $outputPath)
    {
        parent::__construct();

        $this->outputPath = $outputPath;
    }

    /**
     * @returns void
     */
    protected function configure(): void
    {
        $this
            ->setName('phpunit:generate-test-report')
            ->setDescription('Generate an Excel report from a PHP Unit XML file');
    }

    /**
     * @throws \Exception
     *
     * @returns int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->info('Generating test report...');
        exec('php ./vendor/bin/simple-phpunit --configuration phpunit.xml.dist --testdox --log-junit '.self::REPORT_FILE_XML);
        $io->info('Parsing test report...');
        $xml = simplexml_load_file(self::REPORT_FILE_XML);
        $totalTestCases = count($xml->xpath('//testcase'));

        $this->progressBar = new ProgressBar($output, $totalTestCases);
        $this->progressBar->start();

        $io->info('Generating Excel report...');
        $results = $this->generateExcelReport(self::REPORT_FILE_XML);

        $this->progressBar->finish();
        $io->newLine();

        $outputFilePath = $this->outputPath . '/report.xlsx';

        $io->success("Test report generated successfully.\nReport file: ".$outputFilePath."\nTotal test cases: {$results['totalTestCases']}\nPassed: {$results['passed']}\nFailed: {$results['failed']}");

        unlink(self::REPORT_FILE_XML);

        return 0;
    }

    /**
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function generateExcelReport($xmlFilePath): array
    {
        if (!file_exists($xmlFilePath)) {
            throw new \Exception("File {$xmlFilePath} does not exist.");
        }

        $xml = simplexml_load_file($xmlFilePath);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $headers = ['Suite Name', 'Test Case', 'Class Name', 'File', 'Line', 'Assertions', 'Time', 'Passed/Failed'];
        $headerCells = range('A', 'H');

        foreach ($headerCells as $index => $cell) {
            $sheet->setCellValue("{$cell}1", $headers[$index]);
        }

        // Apply header styling
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);
        $sheet->getStyle('A1:H1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFF00');
        $sheet->getStyle('A1:H1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:H1')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // Iterate over the test suites and test cases to populate the Excel sheet
        $row = 2; // Start writing data from row 2
        $testCaseCount = 0;
        $passedCount = 0;
        $failedCount = 0;

        foreach ($xml->testsuite as $testsuite) {
            $this->processTestSuites($testsuite, $sheet, $row, $testCaseCount, $passedCount, $failedCount);
        }

        // Auto-size columns
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Create summary sheet
        $summarySheet = $spreadsheet->createSheet();
        $summarySheet->setTitle('Summary');

        // Summary headers
        $summaryHeaders = ['Total Test Cases', 'Total Passed', 'Total Failed'];
        $summarySheet->setCellValue('A1', 'Summary');
        $summarySheet->setCellValue('A2', $summaryHeaders[0]);
        $summarySheet->setCellValue('A3', $summaryHeaders[1]);
        $summarySheet->setCellValue('A4', $summaryHeaders[2]);

        // Summary values
        $summarySheet->setCellValue('B2', $testCaseCount);
        $summarySheet->setCellValue('B3', $passedCount);
        $summarySheet->setCellValue('B4', $failedCount);

        // Apply styling to summary sheet
        $summarySheet->getStyle('A1:B1')->getFont()->setBold(true);
        $summarySheet->getStyle('A2:A4')->getFont()->setBold(true);
        $summarySheet->getStyle('A1:B4')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $summarySheet->getStyle('A1:B4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $summarySheet->getStyle('A1:B4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('E0E0E0');
        $summarySheet->getColumnDimension('A')->setWidth(30);
        $summarySheet->getColumnDimension('B')->setWidth(20);

        $writer = new Xlsx($spreadsheet);
        $spreadsheet->setActiveSheetIndex(0);

        $outputFilePath = $this->outputPath . '/report.xlsx';

        $writer->save($outputFilePath);

        return [
            'totalTestCases' => $testCaseCount,
            'passed' => $passedCount,
            'failed' => $failedCount,
        ];
    }

    private function processTestSuites($testsuite, $sheet, &$row, &$testCaseCount, &$passedCount, &$failedCount)
    {
        foreach ($testsuite->testcase as $testcase) {
            $this->processTestCase($testcase, $testsuite['name'], $sheet, $row, $testCaseCount, $passedCount, $failedCount);
        }

        foreach ($testsuite->testsuite as $subsuite) {
            $this->processTestSuites($subsuite, $sheet, $row, $testCaseCount, $passedCount, $failedCount);
        }
    }

    private function processTestCase($testcase, $suiteName, $sheet, &$row, &$testCaseCount, &$passedCount, &$failedCount)
    {
        $sheet->setCellValue('A'.$row, $suiteName);
        $sheet->setCellValue('B'.$row, (string) $testcase['name']);
        $sheet->setCellValue('C'.$row, (string) $testcase['classname']);
        $sheet->setCellValue('D'.$row, (string) $testcase['file']);
        $sheet->setCellValue('E'.$row, (string) $testcase['line']);
        $sheet->setCellValue('F'.$row, (string) $testcase['assertions']);
        $sheet->setCellValue('G'.$row, (string) $testcase['time']);

        // Check for errors, warnings, and failures
        $hasFailure = isset($testcase->failure) || isset($testcase->error) || isset($testcase->warning);

        // Populate Passed/Failed column
        $status = $hasFailure ? 'Failed' : 'Passed';
        $sheet->setCellValue('H'.$row, $status);

        // Apply conditional formatting
        $color = $hasFailure ? self::FAILURE_COLOR : self::SUCCESS_COLOR;
        $sheet->getStyle('H'.$row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($color);
        $sheet->getStyle('H'.$row)->getFont()->setColor(new Color(Color::COLOR_WHITE));

        // Apply border to each row
        $sheet->getStyle('A'.$row.':H'.$row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        ++$row;
        $this->progressBar->advance();

        // Update counts
        ++$testCaseCount;
        if ($hasFailure) {
            ++$failedCount;
        } else {
            ++$passedCount;
        }
    }
}
