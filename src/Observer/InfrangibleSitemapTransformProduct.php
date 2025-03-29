<?php

declare(strict_types=1);

namespace Infrangible\CatalogSeo\Observer;

use FeWeDev\Base\Arrays;
use Infrangible\Core\Helper\Stores;
use Infrangible\Sitemap\Model\ISitemapUrl;
use Infrangible\Sitemap\Model\SitemapUrlDataObjectAttributeFactory;
use Infrangible\Sitemap\Model\SitemapUrlDataObjectFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2025 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class InfrangibleSitemapTransformProduct implements ObserverInterface
{
    /** @var Arrays */
    protected $arrays;

    /** @var Stores */
    protected $storeHelper;

    /** @var SitemapUrlDataObjectFactory */
    protected $sitemapUrlDataObjectFactory;

    /** @var SitemapUrlDataObjectAttributeFactory */
    protected $sitemapUrlDataObjectAttributeFactory;

    public function __construct(
        Arrays $arrays,
        Stores $storeHelper,
        SitemapUrlDataObjectAttributeFactory $sitemapUrlDataObjectAttributeFactory,
        SitemapUrlDataObjectFactory $sitemapUrlDataObjectFactory
    ) {
        $this->arrays = $arrays;
        $this->storeHelper = $storeHelper;
        $this->sitemapUrlDataObjectAttributeFactory = $sitemapUrlDataObjectAttributeFactory;
        $this->sitemapUrlDataObjectFactory = $sitemapUrlDataObjectFactory;
    }

    public function execute(Observer $observer): void
    {
        $enabled = $this->storeHelper->getStoreConfigFlag('infrangible_catalogseo/sitemap/enable');

        if (! $enabled) {
            return;
        }

        $event = $observer->getEvent();

        /** @var ISitemapUrl $sitemapUrl */
        $sitemapUrl = $event->getData('sitemap_url');

        /** @var array $productData */
        $productData = $event->getData('product_data');

        $sitemapUrlDataObjectDocument = $this->sitemapUrlDataObjectFactory->create();

        $sitemapUrlDataObjectDocument->setType('document');

        $addTitle = $this->storeHelper->getStoreConfigFlag('infrangible_catalogseo/sitemap/title');

        if ($addTitle) {
            $name = $this->arrays->getValue(
                $productData,
                'name'
            );

            $sitemapUrlDataObjectAttributeDocumentTitle = $this->sitemapUrlDataObjectAttributeFactory->create();

            $sitemapUrlDataObjectAttributeDocumentTitle->setName('title');
            $sitemapUrlDataObjectAttributeDocumentTitle->setValue($name);

            $sitemapUrlDataObjectDocument->addAttribute($sitemapUrlDataObjectAttributeDocumentTitle);
        }

        $addReview = $this->storeHelper->getStoreConfigFlag('infrangible_catalogseo/sitemap/review');

        if ($addReview) {
            $ratingSummary = $this->arrays->getValue(
                $productData,
                'review_summary:rating_summary'
            );

            if ($ratingSummary) {
                $rating = $ratingSummary * 0.05;

                $sitemapUrlDataObjectAttributeDocumentReview = $this->sitemapUrlDataObjectAttributeFactory->create();

                $sitemapUrlDataObjectAttributeDocumentReview->setName('review');
                $sitemapUrlDataObjectAttributeDocumentReview->setValue(strval(round($rating * 2) / 2));

                $sitemapUrlDataObjectDocument->addAttribute($sitemapUrlDataObjectAttributeDocumentReview);
            }
        }

        $sitemapUrl->addDataObject($sitemapUrlDataObjectDocument);
    }
}
