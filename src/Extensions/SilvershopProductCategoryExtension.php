<?php

namespace NSWDPC\Payments\NSWGOVCPP\Agency;

use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataList;
use SilverStripe\Security\Security;

/**
 * Provides extension handling for Product Categories
 * Restrict "showable" products based on the user signed in, whether they can view the product or not
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
