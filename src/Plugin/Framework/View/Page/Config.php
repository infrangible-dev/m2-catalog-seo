<?php

declare(strict_types=1);

namespace Infrangible\CatalogSeo\Plugin\Framework\View\Page;

use Infrangible\Core\Helper\Stores;
use Magento\Framework\View\Page\Title;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Config
{
    /** @var Stores */
    protected $storeHelper;

    public function __construct(Stores $storeHelper)
    {
        $this->storeHelper = $storeHelper;
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     */
    public function afterGetTitle(\Magento\Framework\View\Page\Config $subject, Title $result): Title
    {
        $titleMaxLength = (int)$this->storeHelper->getStoreConfig(
            'infrangible_catalogseo/page/title_max_length',
            70
        );

        $title = $result->get();

        $title = trim($title);

        if (strlen($title) > $titleMaxLength) {
            $result->set(
                trim(
                    mb_substr(
                        $title,
                        0,
                        $titleMaxLength
                    )
                )
            );
        }

        return $result;
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     */
    public function afterGetMetaTitle(\Magento\Framework\View\Page\Config $subject, ?string $result = null): string
    {
        $titleMaxLength = (int)$this->storeHelper->getStoreConfig(
            'infrangible_catalogseo/page/title_max_length',
            150
        );

        $title = trim($result);

        if (strlen($title) > $titleMaxLength) {
            $title = trim(
                mb_substr(
                    $title,
                    0,
                    $titleMaxLength
                )
            );
        }

        return $title;
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     */
    public function afterGetDescription(\Magento\Framework\View\Page\Config $subject, ?string $result = null): ?string
    {
        $descriptionMaxLength = (int)$this->storeHelper->getStoreConfig(
            'infrangible_catalogseo/page/title_max_description',
            150
        );

        if ($result === null) {
            return $result;
        }

        $description = trim($result);

        if (strlen($description) > $descriptionMaxLength) {
            $description = trim(
                mb_substr(
                    $description,
                    0,
                    $descriptionMaxLength
                )
            );
        }

        return $description;
    }
}
