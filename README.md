# PHPUnit Report Generator Bundle
This Symfony bundle provides a command to generate an Excel report from PHPUnit XML files, summarizing test results in a user-friendly format. The generated report includes detailed information about each test case, including suite name, test case, class name, file, line, assertions, time, and status (Passed/Failed).

## Installation
To install this bundle, use Composer:

```composer require jason-leibel/php-unit-report-generator-bundle```

You may need to add this to your `composer.json`:

```
"repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/jason-leibel/php-unit-report-generator-bundle"
        }
    ],
```

You will need to add a yaml configuration in config/packages, below is an example:

```
report:
  output_path: '/var/www/html/public'
```

## Usage
Once installed, this bundle adds the phpunit:generate-test-report command to your Symfony project.
To generate a test report, run the following command:

```php bin/console phpunit:generate-test-report```

This command will:

1. Execute PHPUnit tests with the --testdox and --log-junit options to generate a report.xml file.
2. Parse the report.xml file.
3. Create an Excel report (report.xlsx) in the specified output directory with a summary of the test results.
Output
4. The generated Excel report will contain two sheets:

- Sheet 1: Detailed Test Results
  - Columns: Suite Name, Test Case, Class Name, File, Line, Assertions, Time, Passed/Failed
- Sheet 2: Summary
  - Total number of test cases 
  - Number of tests passed 
  - Number of tests failed

## Configuration
By default, the generated report will be saved in the project's root directory. You can customize the output path by passing a different value when initializing the GenerateTestReportCommand in your services configuration.

## Example
To generate the test report, run the command in your terminal:

```php bin/console phpunit:generate-test-report```

## Requirements
- Symfony 6.4 or later
- PHP 8 or later
- PHPUnit 9.5 or later
