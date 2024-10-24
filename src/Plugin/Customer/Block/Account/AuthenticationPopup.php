<?php

declare(strict_types=1);

namespace Infrangible\CatalogSeo\Plugin\Customer\Block\Account;

use Infrangible\Core\Helper\Stores;
use Magento\Customer\Model\Form;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class AuthenticationPopup
{
    /** @var Stores */
    protected $storeHelper;

    public function __construct(Stores $storeHelper)
    {
        $this->storeHelper = $storeHelper;
    }

    /**
     * @return mixed
     * @noinspection PhpUnusedParameterInspection
     */
    public function aroundToHtml(\Magento\Customer\Block\Account\AuthenticationPopup $subject, callable $proceed)
    {
        if ($this->storeHelper->getStoreConfigFlag(Form::XML_PATH_ENABLE_AUTOCOMPLETE)) {
            return $proceed();
        }

        return '';
    }
}
