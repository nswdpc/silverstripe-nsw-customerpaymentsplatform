<?php

namespace NSWDPC\Payments\NSWGOVCPP\Agency;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Config\Configurable;
use Symbiote\QueuedJobs\Services\AbstractQueuedJob;
use Symbiote\QueuedJobs\Services\QueuedJob;
use Symbiote\QueuedJobs\Services\QueuedJobService;

/**
 * Run daily to reconcile payments
 * @author James
 */
class DailyReportJob extends AbstractQueuedJob
{
    use Configurable;

    private static $run_again_in_seconds = 86400;

    public function __construct($date = null)
    {
        if ($date) {
            $this->reconciliationDate =  $date;
        }
    }

    /**
     * Total steps for this job
     * @var int
     */
    protected $totalSteps = 1;

    /**
     * Job type
     */
    public function getJobType()
    {
        return QueuedJob::QUEUED;
    }

    public function getTitle()
    {
        return _t(
            __CLASS__ . ".JOB_TITLE",
            "Customer Payments Platform - Daily Agency Report"
        );
    }

    /**
     * Retrieve the report and process each record
     */
    public function process()
    {
        try {
            if ($this->reconciliationDate) {
                $datetime = new \DateTime($this->reconciliationDate);
            } else {
                $datetime = new \DateTime($date);
            }
            $service = new DailyReportService();
            $result = $service->getForDate($datetime)->process();
            if ($result) {
                $errors = $service->getErrors();
                $reconciliationErrors = $service->getReconciliationErrors();
                $reportLength = $this->getReportLength();
                $this->addMessage(
                    "Date: " . $datetime->format('Y-m-d H:i:s')
                    . " "
                    . " Report length:" .  count($reportLength)
                    . " Errors:" .  count($errors)
                    . " Reconciliation Errors:" .  count($reconciliationErrors)
                );
            }
        } catch (\Exception $e) {
            $this->addMessage('DailyReportJob failed with error: ' . $e->getMessage());
        }
        $this->isComplete = true;
    }

    /**
     * Requeue a job in the future
     */
    public function afterComplete()
    {
        $seconds = $this->config()->get('run_again_in_seconds');
        $start = new \DateTime("now +{$seconds} seconds");
        $job  = new DailyReportJob();
        return QueuedJobService::singleton()->queueJob($job, $start->format('Y-m-d H:i:s'));
    }
}
