<?php

declare(strict_types=1);

namespace Infrangible\CatalogSeo\Observer;

use Infrangible\Core\Helper\Stores;
use Infrangible\Sitemap\Model\ISitemapUrl;
use Infrangible\Sitemap\Model\SitemapUrlDataObjectAttributeFactory;
use Infrangible\Sitemap\Model\SitemapUrlDataObjectFactory;
use Magento\Cms\Model\Page;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2025 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class InfrangibleSitemapTransformCms implements ObserverInterface
{
    /** @var Stores */
    protected $storeHelper;

    /** @var SitemapUrlDataObjectFactory */
    protected $sitemapUrlDataObjectFactory;

    /** @var SitemapUrlDataObjectAttributeFactory */
    protected $sitemapUrlDataObjectAttributeFactory;

    public function __construct(
        Stores $storeHelper,
        SitemapUrlDataObjectAttributeFactory $sitemapUrlDataObjectAttributeFactory,
        SitemapUrlDataObjectFactory $sitemapUrlDataObjectFactory
    ) {
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

        /** @var Page $cmsPage */
        $cmsPage = $event->getData('cms_page');

        $noRouteIdentifier = $this->storeHelper->getStoreConfig('web/default/cms_no_route');

        if ($cmsPage->getIdentifier() == $noRouteIdentifier) {
            return;
        }

        $name = $cmsPage->getTitle();

        $sitemapUrlDataObjectDocument = $this->sitemapUrlDataObjectFactory->create();

        $sitemapUrlDataObjectDocument->setType('document');

        $addTitle = $this->storeHelper->getStoreConfigFlag('infrangible_catalogseo/sitemap/title');

        if ($addTitle) {
            $sitemapUrlDataObjectAttributeDocumentTitle = $this->sitemapUrlDataObjectAttributeFactory->create();

            $sitemapUrlDataObjectAttributeDocumentTitle->setName('title');
            $sitemapUrlDataObjectAttributeDocumentTitle->setValue($name);

            $sitemapUrlDataObjectDocument->addAttribute($sitemapUrlDataObjectAttributeDocumentTitle);
        }

        $sitemapUrl->addDataObject($sitemapUrlDataObjectDocument);
    }
}
