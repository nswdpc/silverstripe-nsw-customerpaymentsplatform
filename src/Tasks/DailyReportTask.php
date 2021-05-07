<?php

namespace NSWDPC\Payments\NSWGOVCPP\Agency;

use SilverStripe\Control\Director;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\DB;

/**
 * Given a date, process the DailyReport via the DailyReportService
 * @author James
 */
class DailyReportTask extends BuildTask
{
    private static $segment = "cppreconciliationtask";

    public function getTitle()
    {
        return _t(__CLASS__ . '.TITLE', 'CPP Daily report task');
    }

    public function getDescription()
    {
        return _t(__CLASS__ . '.DESCRIPTION', 'Provide a date in an acceptable format to get a CPP reconiliation report of that date. If not date is supplied, yesterday will be used');
    }

    public function run($request)
    {
        if (!Director::is_cli()) {
            print "This report  only accessible via shell\n";
            return 1;
        }

        try {
            $date = $request->getVar('date');

            if ($date) {
                // specific date
                $datetime = new \DateTime($date);
            } else {
                // yesterday
                $datetime = new \DateTime();
                $datetime->modify('-1 day');
            }

            $service = new DailyReportService();
            $result = $service->getForDate($datetime)->process();
            if ($result) {
                $errors = $service->getErrors();
                $reconciliationErrors = $service->getReconciliationErrors();
                $reportLength = $service->getReportLength();
                DB::alteration_message(
                    "Date: " . $datetime->format('Y-m-d H:i:s')
                    . " "
                    . " Report length: " .  $reportLength
                    . " Errors: " .  count($errors)
                    . " Reconciliation Errors: " .  count($reconciliationErrors),
                    'changed'
                );

                print "Errors\n";
                if (!empty($errors)) {
                    print_r($errors);
                }

                print "Reconciliation Errors\n";
                if (!empty($reconciliationErrors)) {
                    print_r($reconciliationErrors);
                }
            }
        } catch (\Exception $e) {
            DB::alteration_message($e->getMessage(), "error");
        }
    }
}
