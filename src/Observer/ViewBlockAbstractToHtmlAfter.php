<?php

declare(strict_types=1);

namespace Infrangible\CatalogSeo\Observer;

use Magento\Catalog\Block\Product\View\Gallery;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\AbstractBlock;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class ViewBlockAbstractToHtmlAfter implements ObserverInterface
{
    /**
     * @throws LocalizedException
     */
    public function execute(Observer $observer): void
    {
        /** @var AbstractBlock $block */
        $block = $observer->getEvent()->getData('block');

        if ($block->getNameInLayout() === 'product.info.media.image' && $block instanceof Gallery) {
            /** @var DataObject $transport */
            $transport = $observer->getEvent()->getData('transport');

            $html = $transport->getData('html');

            /** @var Gallery $additionalGalleryBlock */
            $additionalGalleryBlock = $block->getLayout()->createBlock(Gallery::class);
            $additionalGalleryBlock->setTemplate('Infrangible_CatalogSeo::product/view/gallery.phtml');

            $html .= $additionalGalleryBlock->toHtml();

            $transport->setData(
                'html',
                $html
            );
        }
    }
}
