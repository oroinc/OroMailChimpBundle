<?php

namespace Oro\Bundle\MailChimpBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;

abstract class AbstractMailChimpFixture extends AbstractFixture
{
    protected function setEntityPropertyValues(object $entity, array $data, array $excludeProperties = []): void
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        foreach ($data as $property => $value) {
            if (\in_array($property, $excludeProperties, true)) {
                continue;
            }
            $propertyAccessor->setValue($entity, $property, $value);
        }
    }
}
