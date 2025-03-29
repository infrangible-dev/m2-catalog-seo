<?php

declare(strict_types=1);

namespace Infrangible\CatalogSeo\Observer;

use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2025 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class InfrangibleSitemapAttributesCategory implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        $event = $observer->getEvent();

        /** @var DataObject $attributes */
        $attributes = $event->getData('attributes');

        $attributeList = $attributes->getData('attributes');

        $attributeList[] = 'name';

        $attributes->setData(
            'attributes',
            $attributeList
        );
    }
}
