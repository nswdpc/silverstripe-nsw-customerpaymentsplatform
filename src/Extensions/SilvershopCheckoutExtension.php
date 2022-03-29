<?php

namespace NSWDPC\Payments\NSWGOVCPP\Agency;

use SilverShop\Page\CheckoutPageController;
use SilverShop\Extension\SteppedCheckoutExtension;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Extension;
use SilverStripe\View\ArrayData;
use SilverStripe\ORM\ArrayList;

/**
 * Provides extension for adding/updating features available in checkout
 *
 * @author James
 */
class SilvershopCheckoutExtension extends Extension
{

    /**
     * Return the number of steps in this checkout
     * @return int
     */
    public function getCheckoutStepCount() : int {
        $controller = Controller::has_curr() ? Controller::curr() : null;
        if(!$controller instanceof CheckoutPageController) {
            return 0;
        }
        /**
         * {@link \SilverShop\Extension\SteppedCheckoutExtension::getSteps()}
         */
        $steps = $controller->getSteps();
        if(is_array($steps)) {
            return count($steps);
        } else {
            return 0;
        }
    }

    /**
     * Get current step in the checkout steps, the first step is returned as one 1
     * The last step is the count of all steps
     * @return int
     */
    public function getCheckoutStepPosition() : int {
        $controller = Controller::has_curr() ? Controller::curr() : null;
        if(!$controller instanceof CheckoutPageController) {
            return 0;
        }
        $action = $controller->getAction();
        /**
         * Steps are an index of step action => class name handler
         * @var array
         */
        $steps = $controller->getSteps();
        if(is_array($steps)) {
            $key = array_search($action, array_keys($steps));
            if($key === false) {
                return 0;
            } else {
                //
                return ($key + 1);
            }
        } else {
            return 0;
        }
    }

    /**
     * Return rendered progress indicator, if one exists
     */
    public function CheckoutProgressIndicator() {
        $stepsCount = $this->owner->getCheckoutStepCount();
        $template = null;
        if($stepsCount > 0) {
            $currentPosition = $this->owner->getCheckoutStepPosition();
            $items = ArrayList::create();
            for($i=0;$i<$stepsCount;$i++) {
                $items->push(ArrayData::create([
                    'IsActive' => $currentPosition > $i
                ]));
            }
            $data = ArrayData::create([
                'ProgressIndicator_Items' => $items,
                'Total' => $items->count(),
                'Step' => $currentPosition
            ]);

            $template = $data->renderWith('nswds/Includes/ProgressIndicator');
        }
        return $template;
    }
}
