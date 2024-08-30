<?php

namespace Zu\PHPUnitReportGeneratorBundle;

use Zu\PHPUnitReportGeneratorBundle\DependencyInjection\ReportExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class PHPUnitReportGeneratorBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new ReportExtension();
    }
}