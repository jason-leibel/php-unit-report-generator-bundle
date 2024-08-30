<?php

namespace Zu\PHPUnitReportGeneratorBundle\DependencyInjection;

use Zu\PHPUnitReportGeneratorBundle\Command\GenerateTestReportCommand;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;

class ReportExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('phpunit_report_generator.output_path', $config['output_path']);

        $definition = new Definition(GenerateTestReportCommand::class, [new Reference('kernel'), '%phpunit_report_generator.output_path%']);        $definition->setPublic(true);
        $definition->addTag('console.command');
        $container->setDefinition('app.command.generate_test_report', $definition);
    }
}