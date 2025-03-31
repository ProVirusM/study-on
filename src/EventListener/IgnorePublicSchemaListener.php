<?php

namespace App\EventListener;

use Doctrine\DBAL\Event\SchemaDropTableEventArgs;
use Doctrine\DBAL\Event\SchemaCreateTableEventArgs;
use Doctrine\DBAL\Events;
use Doctrine\Common\EventSubscriber;

class IgnorePublicSchemaListener implements EventSubscriber
{
    public function getSubscribedEvents(): array
    {
        return [
            Events::onSchemaCreateTable,
            Events::onSchemaDropTable,
        ];
    }

    public function onSchemaCreateTable(SchemaCreateTableEventArgs $eventArgs): void
    {
        if ($eventArgs->getTable()->getSchemaName() === 'public') {
            $eventArgs->preventDefault();
        }
    }

    public function onSchemaDropTable(SchemaDropTableEventArgs $eventArgs): void
    {
        if ($eventArgs->getTable()->getSchemaName() === 'public') {
            $eventArgs->preventDefault();
        }
    }
}

