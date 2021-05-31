<?php

namespace NSWDPC\Payments\NSWGOVCPP\Agency;

use Silverstripe\Admin\ModelAdmin;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldImportButton;
use SilverStripe\Forms\GridField\GridFieldPrintButton;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldExportButton;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutoCompleter;
use SilverStripe\Omnipay\Model\Payment as OmnipayPayment;


/**
 * Provide an administration are to view payments
 * @author James
 */
class CustomerPaymentsPlatformModelAdmin extends ModelAdmin
{

    /**
     * @inheritdoc
     */
    public $showImportForm = false;

    /**
     * @var string
     */
    private static $url_segment = 'cpp';

    /**
     * @var string
     */
    private static $menu_icon_class = 'font-icon-tags';

    /**
     * @var array
     */
    private static $managed_models = [
        Payment::class,
        OmnipayPayment::class,
        Configuration::class
    ];

    private static $menu_title = 'CPP';

    /**
     * @inheritdoc
     */
    public function getModelImporters()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getEditForm($id = null, $fields = null)
    {
        $form = parent::getEditForm($id, $fields);
        if($grid = $form->Fields()->dataFieldByName($this->sanitiseClassName($this->modelClass))) {
            $config = $grid->getConfig();
            if($config) {
                $config->removeComponentsByType(GridFieldAddNewButton::class);
                $config->removeComponentsByType(GridFieldPrintButton::class);
                $config->removeComponentsByType(GridFieldImportButton::class);
                $config->removeComponentsByType(GridFieldAddExistingAutoCompleter::class);
                if($this->modelClass == Configuration::class) {
                    $config->removeComponentsByType(GridFieldExportButton::class);
                }
            }
        }
        return $form;
    }
}
