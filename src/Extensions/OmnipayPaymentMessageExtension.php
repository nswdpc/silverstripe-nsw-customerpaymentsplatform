<?php

namespace NSWDPC\Payments\NSWGOVCPP\Agency;

use SilverStripe\ORM\DataExtension;

class OmnipayPaymentMessageExtension extends DataExtension {

    public function updateSummaryFields(&$fields) {
        $fields[ 'LastEdited.Nice' ] = _t(__CLASS__ . '.UPDATED', 'Updated');
    }
}
