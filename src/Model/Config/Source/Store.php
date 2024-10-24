<?php

declare(strict_types=1);

namespace Infrangible\CatalogSeo\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * @author      Andreas Knollmann
 * @copyright   2014-2024 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */
class Store implements OptionSourceInterface
{
    /** @var array */
    private $stores = [
        'AutoPartsStore'       => 'Auto Parts Store',
        'BikeStore'            => 'Bike Store',
        'BookStore'            => 'Book Store',
        'ClothingStore'        => 'Clothing Store',
        'ComputerStore'        => 'Computer Store',
        'ConvenienceStore'     => 'Convenience Store',
        'DepartmentStore'      => 'Department Store',
        'ElectronicsStore'     => 'Electronics Store',
        'Florist'              => 'Florist',
        'FurnitureStore'       => 'Furniture Store',
        'GardenStore'          => 'Garden Store',
        'GroceryStore'         => 'Grocery Store',
        'HardwareStore'        => 'Hardware Store',
        'HobbyShop'            => 'Hobby Shop',
        'HomeGoodsStore'       => 'Home Goods Store',
        'JewelryStore'         => 'Jewelry Store',
        'LiquorStore'          => 'Liquor Store',
        'MensClothingStore'    => 'Mens Clothing Store',
        'MobilePhoneStore'     => 'Mobile Phone Store',
        'MovieRentalStore'     => 'Movie Rental Store',
        'MusicStore'           => 'Music Store',
        'OfficeEquipmentStore' => 'Office Equipment Store',
        'OutletStore'          => 'Outlet Store',
        'PawnShop'             => 'Pawn Shop',
        'PetStore'             => 'Pet Store',
        'ShoeStore'            => 'Shoe Store',
        'SportingGoodsStore'   => 'Sporting Goods Store',
        'Store'                => 'Store',
        'TireShop'             => 'Tire Shop',
        'ToyStore'             => 'Toy Store',
        'WholesaleStore'       => 'Wholesale Store'
    ];

    public function toOptionArray(): array
    {
        $options = [];

        foreach ($this->stores as $value => $label) {
            $options[] = [
                'value' => $value,
                'label' => $label
            ];
        }

        return $options;
    }

    public function toArray(): array
    {
        $options = [];

        foreach ($this->stores as $value => $label) {
            $options[ $value ] = $label;
        }

        return $options;
    }
}
