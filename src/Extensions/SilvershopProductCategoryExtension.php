<?php

namespace NSWDPC\Payments\NSWGOVCPP\Agency;

use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataList;
use SilverStripe\Security\Security;

/**
 * Provides extension handling for Product Categories
 *
 * @author James
 */
class SilvershopProductCategoryExtension extends DataExtension
{

    /**
     * Ensure canView is respected
     */
    public function updateProductsShowable(DataList &$products) {
        $member = Security::getCurrentUser();
        $viewableProductIds = [];
        foreach($products as $product) {
            if($product->canView($member)) {
                $viewableProductIds[] = $product->ID;
            }
        }
        if(!empty($viewableProductIds)) {
            $products = $products->filter(['ID' => $viewableProductIds]);
        }
    }

}
