<?php

declare(strict_types=1);

namespace Infrangible\CatalogSeo\Block;

use Exception;
use FeWeDev\Base\Json;
use FeWeDev\Base\Variables;
use Infrangible\Core\Helper\Attribute;
use Infrangible\Core\Helper\Database;
use Infrangible\Core\Helper\Registry;
use Infrangible\Core\Helper\Stores;
use Magento\Bundle\Model\Product\Price;
use Magento\Bundle\Model\Product\Type;
use Magento\Catalog\Helper\Product;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product\Media\ConfigFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Review\Model\Rating\Option\Vote;
use Magento\Review\Model\ResourceModel\Rating\Option\Vote\Collection;
use Magento\Review\Model\Review;
use Magento\Review\Model\Review\Summary;
use Psr\Log\LoggerInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Head extends Template
{
    /** @var Variables */
    protected $variables;

    /** @var Stores */
    protected $storeHelper;

    /** @var Json */
    protected $json;

    /** @var Registry */
    protected $registryHelper;

    /** @var Database */
    protected $databaseHelper;

    /** @var Attribute */
    protected $eavAttributeHelper;

    /** @var \Infrangible\Core\Helper\Review */
    protected $reviewHelper;

    /** @var Product */
    protected $catalogProductHelper;

    /** @var LoggerInterface */
    protected $logging;

    /** @var ConfigFactory */
    protected $configFactory;

    /** @var Category */
    private $category;

    /** @var \Magento\Catalog\Model\Product */
    private $product;

    public function __construct(
        Template\Context $context,
        Variables $variables,
        Stores $storeHelper,
        Json $json,
        Registry $registryHelper,
        Database $databaseHelper,
        Attribute $eavAttributeHelper,
        \Infrangible\Core\Helper\Review $reviewHelper,
        Product $catalogProductHelper,
        LoggerInterface $logging,
        ConfigFactory $configFactory,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $data
        );

        $this->variables = $variables;
        $this->storeHelper = $storeHelper;
        $this->json = $json;
        $this->registryHelper = $registryHelper;
        $this->databaseHelper = $databaseHelper;
        $this->eavAttributeHelper = $eavAttributeHelper;
        $this->reviewHelper = $reviewHelper;
        $this->catalogProductHelper = $catalogProductHelper;
        $this->logging = $logging;
        $this->configFactory = $configFactory;
    }

    protected function getCategory(): ?Category
    {
        if ($this->category === null) {
            $this->category = $this->registryHelper->registry('current_category');
        }

        return $this->category;
    }

    protected function getProduct(): ?\Magento\Catalog\Model\Product
    {
        if ($this->product === null) {
            $this->product = $this->registryHelper->registry('current_product');
        }

        return $this->product;
    }

    protected function isCategoryPage(): bool
    {
        return $this->getCategory() !== null && $this->getProduct() === null;
    }

    protected function isProductPage(): bool
    {
        return $this->getProduct() !== null;
    }

    public function addSchemaWebsiteData(): bool
    {
        return $this->storeHelper->getStoreConfig(
            'infrangible_catalogseo/rich_snippets/website',
            true,
            true
        );
    }

    public function getSchemaWebsiteData(): string
    {
        $data = [
            '@context' => 'http://schema.org',
            '@type'    => 'WebSite',
            'url'      => $this->storeHelper->getWebUrl()
        ];

        if ($this->storeHelper->getStoreConfig(
            'infrangible_catalogseo/rich_snippets/search_action',
            false,
            true
        )) {
            $data[ 'potentialAction' ] = [
                '@type'       => 'SearchAction',
                'target'      => sprintf(
                    '%s/catalogsearch/result/?q={search_term_string}',
                    $this->storeHelper->getWebUrl()
                ),
                'query-input' => 'required name=search_term_string'
            ];
        }

        return $this->json->encode(
            $data,
            true
        );
    }

    public function addSchemaOrganizationData(): bool
    {
        return $this->storeHelper->getStoreConfig(
            'infrangible_catalogseo/rich_snippets/organization',
            false,
            true
        );
    }

    public function getSchemaOrganizationData(): string
    {
        $address = ['@type' => 'PostalAddress'];

        $this->addProperty(
            $address,
            'streetAddress'
        );
        $this->addProperty(
            $address,
            'postalCode'
        );
        $this->addProperty(
            $address,
            'addressLocality'
        );
        $this->addProperty(
            $address,
            'addressCountry'
        );

        $organizationWebsiteData = [
            '@context'    => 'http://schema.org',
            '@type'       => $this->storeHelper->getStoreConfig(
                'infrangible_catalogseo/rich_snippets/organization_type'
            ),
            'name'        => $this->getTitle(false),
            'description' => $this->getDescription(false),
            'logo'        => $this->getSiteLogo(),
            'image'       => $this->getSiteLogo(),
            'email'       => $this->formatValue($this->storeHelper->getStoreConfig('trans_email/ident_general/email')),
            'telephone'   => $this->formatValue($this->storeHelper->getStoreConfig('general/store_information/phone')),
            'url'         => $this->getBaseUrl()
        ];

        if (count($address) > 1) {
            $organizationWebsiteData[ 'address' ] = $address;
        }

        $this->addProperty(
            $organizationWebsiteData,
            'currenciesAccepted'
        );
        $this->addProperty(
            $organizationWebsiteData,
            'openingHours'
        );
        $this->addProperty(
            $organizationWebsiteData,
            'paymentAccepted'
        );
        $this->addProperty(
            $organizationWebsiteData,
            'priceRange'
        );
        $this->addProperty(
            $organizationWebsiteData,
            'sameAs'
        );

        return $this->json->encode(
            $organizationWebsiteData,
            true
        );
    }

    protected function addProperty(array &$data, string $propertyName)
    {
        $propertyConfigName = strtolower(
            preg_replace(
                '/(.)([A-Z])/',
                '$1_$2',
                $propertyName
            )
        );

        $propertyValue = $this->storeHelper->getStoreConfig(
            sprintf(
                'infrangible_catalogseo/rich_snippets/%s',
                $propertyConfigName
            )
        );

        if (! $this->variables->isEmpty($propertyValue)) {
            $propertyValue = trim($propertyValue);

            if (preg_match(
                '/\n/',
                $propertyValue
            )) {
                foreach (preg_split(
                    '/\n/',
                    $propertyValue
                ) as $propertyValueValue) {
                    $data[ $propertyName ][] = $this->formatValue($propertyValueValue);
                }
            } else {
                $data[ $propertyName ] = $this->formatValue($propertyValue);
            }
        }
    }

    public function addSchemaProductData(): bool
    {
        return $this->isProductPage() && $this->storeHelper->getStoreConfig(
                'infrangible_catalogseo/rich_snippets/product',
                true,
                true
            );
    }

    public function getSchemaProductData(): string
    {
        $product = $this->getProduct();
        $mediaConfig = $this->configFactory->create();

        $data = [
            '@context'   => 'http://schema.org',
            '@type'      => 'Product',
            'identifier' => $product->getSku(),
            'productID'  => $product->getSku(),
            'sku'        => $product->getSku(),
            'url'        => $product->getUrlModel()->getUrl(
                $product,
                ['_ignore_category' => true]
            ),
            'image'      => $mediaConfig->getMediaUrl($product->getData('image'))
        ];

        $this->addEavAttributeLabel(
            $data,
            'brand'
        );
        $this->addEavAttributeLabel(
            $data,
            'color'
        );
        $this->addEavAttributeLabel(
            $data,
            'description'
        );
        $this->addEavAttributeLabel(
            $data,
            'depth'
        );
        $this->addEavAttributeLabel(
            $data,
            'gtin'
        );
        $this->addEavAttributeLabel(
            $data,
            'gtin8'
        );
        $this->addEavAttributeLabel(
            $data,
            'gtin12'
        );
        $this->addEavAttributeLabel(
            $data,
            'gtin13'
        );
        $this->addEavAttributeLabel(
            $data,
            'gtin14'
        );
        $this->addEavAttributeLabel(
            $data,
            'height'
        );
        $this->addEavAttributeLabel(
            $data,
            'manufacturer'
        );
        $this->addEavAttributeLabel(
            $data,
            'material'
        );
        $this->addEavAttributeLabel(
            $data,
            'model'
        );
        $this->addEavAttributeLabel(
            $data,
            'mpn'
        );
        $this->addEavAttributeLabel(
            $data,
            'name'
        );
        $this->addEavAttributeLabel(
            $data,
            'weight'
        );
        $this->addEavAttributeLabel(
            $data,
            'width'
        );

        $categoryCollection = $product->getCategoryCollection();

        $categoryCollection->addNameToResult();
        $categoryCollection->addIsActiveFilter();

        /** @var Category $category */
        foreach ($categoryCollection as $category) {
            if ($category->getLevel() > 1) {
                $data[ 'category' ][] = $category->getName();
            }
        }

        $availability = 'http://schema.org/OutOfStock';

        if ($this->catalogProductHelper->getSkipSaleableCheck()) {
            $availability = 'http://schema.org/InStock';
        }

        if ($product->isSaleable()) {
            $availability = 'http://schema.org/InStock';
        }

        if ($product->getTypeInstance()->isComposite($product) && $this->storeHelper->getStoreConfig(
                'infrangible_catalogseo/rich_snippets/aggregate_offer',
                false
            )) {
            $aggregateOffer = ['@type' => 'AggregateOffer'];

            $offers = [];

            if ($product->getTypeId() === 'bundle') {
                /** @var Price $priceModel */
                $priceModel = $product->getPriceModel();

                [$lowPrice, $highPrice] = $priceModel->getTotalPrices($product);

                /** @var Type $typeInstance */
                $typeInstance = $product->getTypeInstance();

                /** @var \Magento\Catalog\Model\Product $selectionProduct */
                foreach ($typeInstance->getSelectionsCollection(
                    $typeInstance->getOptionsIds($product),
                    $product
                ) as $selectionProduct) {

                    $selectionProductAvailability = 'http://schema.org/OutOfStock';

                    if ($this->catalogProductHelper->getSkipSaleableCheck()) {
                        $selectionProductAvailability = 'http://schema.org/InStock';
                    }

                    if ($product->isSaleable()) {
                        $selectionProductAvailability = 'http://schema.org/InStock';
                    }

                    $offer = [
                        '@type'        => 'Offer',
                        'availability' => $selectionProductAvailability,
                        'url'          => $product->getUrlModel()->getUrl(
                            $product,
                            ['_ignore_category' => true]
                        ),
                        'sku'          => $selectionProduct->getSku(),
                        'price'        => $selectionProduct->getFinalPrice()
                    ];

                    try {
                        $offer[ 'priceCurrency' ] = $this->storeHelper->getStore()->getCurrentCurrencyCode();
                    } catch (NoSuchEntityException $exception) {
                        $this->logging->error($exception);
                    }

                    $this->addEavAttributeLabel(
                        $offer,
                        'name',
                        $selectionProduct
                    );

                    $offers[] = $offer;
                }
            } elseif ($product->getTypeId() === 'grouped') {
                /** @var Grouped $typeInstance */
                $typeInstance = $product->getTypeInstance();

                $associatedProductMinimalPrices = [];
                $associatedProductMaximalPrices = [];

                /** @var \Magento\Catalog\Model\Product $associatedProduct */
                foreach ($typeInstance->getAssociatedProducts($product) as $associatedProduct) {
                    $associatedProductAvailability = 'http://schema.org/OutOfStock';

                    if ($this->catalogProductHelper->getSkipSaleableCheck()) {
                        $associatedProductAvailability = 'http://schema.org/InStock';
                    }

                    if ($product->isSaleable()) {
                        $associatedProductAvailability = 'http://schema.org/InStock';
                    }

                    $offer = [
                        '@type'        => 'Offer',
                        'availability' => $associatedProductAvailability,
                        'url'          => $product->getUrlModel()->getUrl(
                            $product,
                            ['_ignore_category' => true]
                        ),
                        'sku'          => $associatedProduct->getSku()
                    ];

                    if ($associatedProduct->getTypeId() === 'bundle') {
                        /** @var Price $associatedProductPriceModel */
                        $associatedProductPriceModel = $associatedProduct->getPriceModel();

                        [$minimalPrice, $maximalPrice] = $associatedProductPriceModel->getTotalPrices($product);

                        $associatedProductMinimalPrices[] = $minimalPrice;
                        $associatedProductMaximalPrices[] = $maximalPrice;

                        $offer[ 'price' ] = $minimalPrice;
                    } else {
                        $associatedProductMinimalPrices[] = $associatedProduct->getFinalPrice();
                        $associatedProductMaximalPrices[] = $associatedProduct->getFinalPrice();

                        $offer[ 'price' ] = $associatedProduct->getFinalPrice();
                    }

                    try {
                        $offer[ 'priceCurrency' ] = $this->storeHelper->getStore()->getCurrentCurrencyCode();
                    } catch (NoSuchEntityException $exception) {
                        $this->logging->error($exception);
                    }

                    $this->addEavAttributeLabel(
                        $offer,
                        'name',
                        $associatedProduct
                    );

                    $offers[] = $offer;
                }

                $lowPrice = round(
                    min($associatedProductMinimalPrices),
                    2
                );
                $highPrice = round(
                    array_sum($associatedProductMaximalPrices),
                    2
                );
            } elseif ($product->getTypeId() === 'configurable') {
                /** @var Configurable $typeInstance */
                $typeInstance = $product->getTypeInstance();

                $usedProductPrices = [];

                /** @var \Magento\Catalog\Model\Product $usedProduct */
                foreach ($typeInstance->getUsedProducts($product) as $usedProduct) {
                    $usedProductPrices[] = $usedProduct->getFinalPrice();

                    $usedProductAvailability = 'http://schema.org/OutOfStock';

                    if ($this->catalogProductHelper->getSkipSaleableCheck()) {
                        $usedProductAvailability = 'http://schema.org/InStock';
                    }

                    if ($product->isSaleable()) {
                        $usedProductAvailability = 'http://schema.org/InStock';
                    }

                    $offer = [
                        '@type'        => 'Offer',
                        'availability' => $usedProductAvailability,
                        'url'          => $product->getUrlModel()->getUrl(
                            $product,
                            ['_ignore_category' => true]
                        ),
                        'sku'          => $usedProduct->getSku(),
                        'price'        => $usedProduct->getFinalPrice()
                    ];

                    try {
                        $offer[ 'priceCurrency' ] = $this->storeHelper->getStore()->getCurrentCurrencyCode();
                    } catch (NoSuchEntityException $exception) {
                        $this->logging->error($exception);
                    }

                    $this->addEavAttributeLabel(
                        $offer,
                        'name',
                        $usedProduct
                    );

                    $offers[] = $offer;
                }

                $lowPrice = empty($usedProductPrices) ? 0 : round(
                    min($usedProductPrices),
                    2
                );
                $highPrice = empty($usedProductPrices) ? 0 : round(
                    max($usedProductPrices),
                    2
                );
            } else {
                $this->logging->error(
                    sprintf(
                        'Product with id: %d has unsupported product type',
                        $product->getId()
                    )
                );

                $lowPrice = $product->getFinalPrice();
                $highPrice = $product->getFinalPrice();
            }

            $aggregateOffer[ 'lowPrice' ] = round(
                $lowPrice,
                2
            );
            $aggregateOffer[ 'highPrice' ] = round(
                $highPrice,
                2
            );

            try {
                $aggregateOffer[ 'priceCurrency' ] = $this->storeHelper->getStore()->getCurrentCurrencyCode();
            } catch (NoSuchEntityException $exception) {
                $this->logging->error($exception);
            }

            $aggregateOffer[ 'offerCount' ] = count($offers);
            $aggregateOffer[ 'offers' ] = $offers;

            $data[ 'offers' ] = $aggregateOffer;
        } else {
            $offer = [
                '@type'        => 'Offer',
                'availability' => $availability,
                'url'          => $product->getUrlModel()->getUrl(
                    $product,
                    ['_ignore_category' => true]
                )
            ];

            $this->addEavAttributeLabel(
                $offer,
                'name'
            );
            $this->addEavAttributeLabel(
                $offer,
                'sku'
            );

            try {
                $offer[ 'priceCurrency' ] = $this->storeHelper->getStore()->getCurrentCurrencyCode();
            } catch (NoSuchEntityException $exception) {
                $this->logging->error($exception);
            }

            if ($product->getTypeId() === 'bundle') {
                /** @var Price $priceModel */
                $priceModel = $product->getPriceModel();

                [$minimalPrice, $maximalPrice] = $priceModel->getTotalPrices($product);

                $offer[ 'price' ] = round(
                    $minimalPrice,
                    2
                );
                $offer[ 'minPrice' ] = round(
                    $minimalPrice,
                    2
                );
                $offer[ 'maxPrice' ] = round(
                    $maximalPrice,
                    2
                );
            } elseif ($product->getTypeId() === 'grouped') {
                /** @var Grouped $typeInstance */
                $typeInstance = $product->getTypeInstance();

                $associatedProductPrices = [];

                /** @var \Magento\Catalog\Model\Product $associatedProduct */
                foreach ($typeInstance->getAssociatedProducts($product) as $associatedProduct) {
                    if ($associatedProduct->getTypeId() === 'bundle') {
                        /** @var Price $associatedProductPriceModel */
                        $associatedProductPriceModel = $associatedProduct->getPriceModel();

                        [$minimalPrice, $maximalPrice] = $associatedProductPriceModel->getTotalPrices($product);

                        $associatedProductPrices[] = $minimalPrice;
                        $associatedProductPrices[] = $maximalPrice;
                    } else {
                        $associatedProductPrices[] = $associatedProduct->getFinalPrice();
                    }
                }

                $offer[ 'price' ] = round(
                    min($associatedProductPrices),
                    2
                );
                $offer[ 'minPrice' ] = round(
                    min($associatedProductPrices),
                    2
                );
                $offer[ 'maxPrice' ] = round(
                    max($associatedProductPrices),
                    2
                );
            } elseif ($product->getTypeId() === 'configurable') {
                /** @var Configurable $typeInstance */
                $typeInstance = $product->getTypeInstance();

                $usedProductPrices = [];

                /** @var \Magento\Catalog\Model\Product $usedProduct */
                foreach ($typeInstance->getUsedProducts($product) as $usedProduct) {
                    $usedProductPrices[] = $usedProduct->getFinalPrice();
                }

                $offer[ 'price' ] = round(
                    min($usedProductPrices),
                    2
                );
                $offer[ 'minPrice' ] = round(
                    min($usedProductPrices),
                    2
                );
                $offer[ 'maxPrice' ] = round(
                    max($usedProductPrices),
                    2
                );
            } else {
                $offer[ 'price' ] = round(
                    $product->getFinalPrice(),
                    2
                );
            }

            $data[ 'offers' ] = [$offer];
        }

        if ($this->storeHelper->getStoreConfig(
            'infrangible_catalogseo/rich_snippets/aggregate_rating',
            false,
            true
        )) {
            try {
                $reviewSummaryCollection = $this->reviewHelper->getReviewSummaryCollection();

                $reviewSummaryCollection->addEntityFilter($product->getId());
                $reviewSummaryCollection->addStoreFilter($this->storeHelper->getStore()->getId());

                $reviewSummaryCollection->load();

                if ($reviewSummaryCollection->getSize() > 0) {
                    /** @var Summary $reviewSummary */
                    $reviewSummary = $reviewSummaryCollection->getFirstItem();

                    $reviewData = $reviewSummary->getData();

                    if (array_key_exists(
                            'reviews_count',
                            $reviewData
                        ) && array_key_exists(
                            'rating_summary',
                            $reviewData
                        )) {

                        if ($reviewData[ 'reviews_count' ] > 0) {
                            if ($this->storeHelper->getStoreConfig(
                                'infrangible_catalogseo/rich_snippets/aggregate_rating_scale',
                                false
                            )) {

                                $data[ 'aggregateRating' ] = [
                                    '@type'       => 'AggregateRating',
                                    'bestRating'  => 100,
                                    'worstRating' => 0,
                                    'reviewCount' => $reviewData[ 'reviews_count' ],
                                    'ratingValue' => round(
                                        $reviewData[ 'rating_summary' ],
                                        1
                                    )
                                ];
                            } else {
                                $data[ 'aggregateRating' ] = [
                                    '@type'       => 'AggregateRating',
                                    'bestRating'  => 5,
                                    'worstRating' => 0,
                                    'reviewCount' => $reviewData[ 'reviews_count' ],
                                    'ratingValue' => round(
                                        $reviewData[ 'rating_summary' ] / 20,
                                        1
                                    )
                                ];
                            }
                        }
                    }
                }
            } catch (NoSuchEntityException $exception) {
                $this->logging->error($exception);
            }
        }

        if ($this->storeHelper->getStoreConfig(
            'infrangible_catalogseo/rich_snippets/review',
            false,
            true
        )) {
            try {
                $data[ 'review' ] = [];

                $reviewCollection = $this->reviewHelper->getReviewCollection();

                $reviewCollection->addEntityFilter(
                    'product',
                    $product->getId()
                );
                $reviewCollection->addStoreFilter($this->storeHelper->getStore()->getId());
                $reviewCollection->addStatusFilter(Review::STATUS_APPROVED);
                $reviewCollection->setDateOrder();
                $reviewCollection->addRateVotes();

                /** @var Review $review */
                foreach ($reviewCollection as $review) {
                    /** @var Collection $voteCollection */
                    $voteCollection = $review->getDataUsingMethod('rating_votes');

                    $reviewRating = [];

                    /** @var Vote $vote */
                    foreach ($voteCollection as $vote) {
                        $reviewRating[] = [
                            '@type'        => 'Rating',
                            'reviewAspect' => $vote->getDataUsingMethod('rating_code'),
                            'ratingValue'  => $vote->getDataUsingMethod('value')
                        ];
                    }

                    $data[ 'review' ][] = [
                        '@type'         => 'Review',
                        'author'        => $review->getDataUsingMethod('nickname'),
                        'datePublished' => $this->formatDate($review->getCreatedAt()),
                        'name'          => $review->getDataUsingMethod('title'),
                        'description'   => $review->getDataUsingMethod('detail'),
                        'reviewRating'  => $reviewRating
                    ];
                }
            } catch (NoSuchEntityException $exception) {
                $this->logging->error($exception);
            }
        }

        return $this->json->encode(
            $data,
            true
        );
    }

    protected function addEavAttributeLabel(
        array &$data,
        string $schemaAttributeCode,
        ?\Magento\Catalog\Model\Product $product = null
    ) {
        if (! $product) {
            $product = $this->getProduct();
        }

        if ($this->storeHelper->getStoreConfig(
            sprintf(
                'infrangible_catalogseo/rich_snippets/%s',
                $schemaAttributeCode
            ),
            false,
            true
        )) {

            $attributeId = (int)$this->storeHelper->getStoreConfig(
                sprintf(
                    'infrangible_catalogseo/rich_snippets/%s_attribute_id',
                    $schemaAttributeCode
                )
            );

            try {
                $eavAttribute = $attributeId > 0 ? $this->eavAttributeHelper->getAttribute(
                    'catalog_product',
                    (string)$attributeId
                ) : null;

                $attributeCode = $eavAttribute && $eavAttribute->getId() ? $eavAttribute->getAttributeCode() : null;

                $value = $attributeCode ? $this->eavAttributeHelper->getAttributeValue(
                    $this->databaseHelper->getDefaultConnection(),
                    'catalog_product',
                    $attributeCode,
                    $this->variables->intValue($product->getId()),
                    $this->variables->intValue($this->storeHelper->getStore()->getId())
                ) : null;

                if (! $this->variables->isEmpty($value)) {
                    $data[ $schemaAttributeCode ] = $this->formatValue(
                        $value,
                        true
                    );
                } else {
                    $backupAttributeId = (int)$this->storeHelper->getStoreConfig(
                        sprintf(
                            'infrangible_catalogseo/rich_snippets/%s_attribute_id_backup',
                            $schemaAttributeCode
                        )
                    );

                    $backupEavAttribute = $backupAttributeId > 0 ? $this->eavAttributeHelper->getAttribute(
                        'catalog_product',
                        (string)$backupAttributeId
                    ) : null;

                    $backupAttributeCode =
                        $backupEavAttribute && $backupEavAttribute->getId() ? $backupEavAttribute->getAttributeCode() :
                            $schemaAttributeCode;

                    $value = $backupAttributeCode ? $this->eavAttributeHelper->getAttributeValue(
                        $this->databaseHelper->getDefaultConnection(),
                        'catalog_product',
                        $backupAttributeCode,
                        $this->variables->intValue($product->getId()),
                        $this->variables->intValue($this->storeHelper->getStore()->getId())
                    ) : null;

                    if (! $this->variables->isEmpty($value)) {
                        $data[ $schemaAttributeCode ] = $this->formatValue(
                            $value,
                            true
                        );
                    }
                }
            } catch (Exception $exception) {
            }
        }
    }

    public function addOpenGraphData(): bool
    {
        return $this->storeHelper->getStoreConfig(
            'infrangible_catalogseo/open_graph/enable',
            true,
            true
        );
    }

    public function getOpenGraphType(): string
    {
        return $this->isProductPage() ? 'product' : ($this->isCategoryPage() ? 'product.group' : 'website');
    }

    public function getTitle(bool $siteSpecific = true): string
    {
        if ($siteSpecific && $this->isCategoryPage()) {
            $title = $this->getCategory()->getName();
        } elseif ($siteSpecific && $this->isProductPage()) {
            $title = $this->getProduct()->getName();
        } else {
            $title = $this->storeHelper->getStoreConfig('design/head/default_title');
        }

        return $this->formatValue(
            $title,
            true
        );
    }

    public function getDescription(bool $siteSpecific = true): string
    {
        if ($siteSpecific && $this->isCategoryPage()) {
            $description = $this->getCategory()->getDataUsingMethod('description');
        } elseif ($siteSpecific && $this->isProductPage()) {
            $description = $this->getProduct()->getDataUsingMethod('short_description');
        } else {
            $description = $this->storeHelper->getStoreConfig('design/head/default_description');
        }

        return $this->formatValue(
            $description,
            true
        );
    }

    public function getSiteLogo(): string
    {
        return $this->storeHelper->getSiteLogo();
    }

    public function getSiteUrl(): string
    {
        if ($this->isCategoryPage()) {
            $siteUrl = $this->getCategory()->getUrl();
        } elseif ($this->isProductPage()) {
            $product = $this->getProduct();
            $siteUrl = $product->getUrlModel()->getUrl(
                $product,
                ['_ignore_category' => true]
            );
        } else {
            $siteUrl = $this->storeHelper->getWebUrl();
        }

        return $siteUrl;
    }

    public function getSiteName(): string
    {
        return $this->formatValue($this->storeHelper->getStoreConfig('general/store_information/name'));
    }

    public function addFacebookData(): bool
    {
        return $this->storeHelper->getStoreConfig(
            'infrangible_catalogseo/facebook/enable',
            true,
            true
        );
    }

    public function getFacebookAppId(): string
    {
        return $this->formatValue($this->storeHelper->getStoreConfig('infrangible_catalogseo/facebook/app_id'));
    }

    public function addTwitterCardData(): bool
    {
        return $this->storeHelper->getStoreConfig(
            'infrangible_catalogseo/twitter_card/enable',
            true,
            true
        );
    }

    public function getTwitterCardType(): string
    {
        return 'summary';
    }

    public function getTwitterCardUserName(): string
    {
        return $this->formatValue($this->storeHelper->getStoreConfig('infrangible_catalogseo/twitter_card/user_name'));
    }

    /**
     * @param mixed $value
     */
    protected function formatValue($value, bool $stripTags = false): string
    {
        $value = trim($value === null ? '' : $value);

        if ($stripTags) {
            $value = $this->stripTags($value);
        }

        return htmlspecialchars($value);
    }
}
