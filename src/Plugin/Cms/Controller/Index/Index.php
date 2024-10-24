<?php

declare(strict_types=1);

namespace Infrangible\CatalogSeo\Plugin\Cms\Controller\Index;

use Infrangible\Core\Helper\Stores;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Result\Page;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Index
{
    /** @var Stores */
    protected $storeHelper;

    /** @var Config */
    protected $pageConfig;

    public function __construct(Stores $storeHelper, Config $pageConfig)
    {
        $this->storeHelper = $storeHelper;
        $this->pageConfig = $pageConfig;
    }

    /**
     * @param mixed $result
     *
     * @return mixed
     * @noinspection PhpUnusedParameterInspection
     */
    public function afterExecute(\Magento\Cms\Controller\Index\Index $subject, $result)
    {
        if ($result instanceof Page) {
            if ($this->storeHelper->getStoreConfigFlag('infrangible_catalogseo/page/home_canonical_tag')) {
                $this->pageConfig->addRemotePageAsset(
                    $this->storeHelper->getWebUrl(),
                    'canonical',
                    ['attributes' => ['rel' => 'canonical']]
                );
            }
        }

        return $result;
    }
}
