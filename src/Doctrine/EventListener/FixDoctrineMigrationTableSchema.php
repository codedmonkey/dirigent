<?php

namespace CodedMonkey\Conductor\Doctrine\EventListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\ORM\Tools\ToolEvents;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/** @see https://github.com/doctrine/migrations/issues/1406 */
#[AsDoctrineListener(ToolEvents::postGenerateSchema)]
final readonly class FixDoctrineMigrationTableSchema
{
    public function __construct(
        #[Autowire('@=service("doctrine.migrations.dependency_factory").getConfiguration().getMetadataStorageConfiguration()')]
        private TableMetadataStorageConfiguration $configuration,
    ) {
    }

    /** @see \Doctrine\Migrations\Metadata\Storage\TableMetadataStorage::getExpectedTable */
    public function postGenerateSchema(GenerateSchemaEventArgs $args): void
    {
        $schema = $args->getSchema();
        $schemaChangelog = $schema->createTable($this->configuration->getTableName());
        $schemaChangelog->addColumn(
            $this->configuration->getVersionColumnName(),
            'string',
            ['notnull' => true, 'length' => $this->configuration->getVersionColumnLength()],
        );
        $schemaChangelog->addColumn($this->configuration->getExecutedAtColumnName(), 'datetime', ['notnull' => false]);
        $schemaChangelog->addColumn($this->configuration->getExecutionTimeColumnName(), 'integer', ['notnull' => false]);

        $schemaChangelog->setPrimaryKey([$this->configuration->getVersionColumnName()]);
    }
}
