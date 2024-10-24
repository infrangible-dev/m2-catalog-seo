<?php

declare(strict_types=1);

namespace Infrangible\CatalogSeo\Plugin\Cms\Controller\Page;

use Infrangible\Core\Helper\Stores;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Result\Page;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class View
{
    /** @var Stores */
    protected $storeHelper;

    /** @var \Magento\Cms\Helper\Page */
    protected $pageHelper;

    /** @var Config */
    protected $pageConfig;

    public function __construct(Stores $storeHelper, \Magento\Cms\Helper\Page $pageHelper, Config $pageConfig)
    {
        $this->storeHelper = $storeHelper;
        $this->pageHelper = $pageHelper;

        $this->pageConfig = $pageConfig;
    }

    /**
     * @param mixed $result
     *
     * @return mixed
     */
    public function afterExecute(\Magento\Cms\Controller\Page\View $subject, $result)
    {
        if ($result instanceof Page) {
            if ($this->storeHelper->getStoreConfigFlag('infrangible_catalogseo/page/cms_canonical_tag')) {
                $pageId = $subject->getRequest()->getParam(
                    'page_id',
                    $subject->getRequest()->getParam(
                        'id',
                        false
                    )
                );

                $this->pageConfig->addRemotePageAsset(
                    $this->pageHelper->getPageUrl($pageId),
                    'canonical',
                    ['attributes' => ['rel' => 'canonical']]
                );
            }
        }

        return $result;
    }
}
