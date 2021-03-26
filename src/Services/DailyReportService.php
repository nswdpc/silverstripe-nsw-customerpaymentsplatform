<?php

namespace NSWDPC\Payments\NSWGOVCPP\Agency;

use Omnipay\Omnipay;
use Omnipay\NSWGOVCPP\FetchTransactionRequest;
use Omnipay\NSWGOVCPP\FetchTransactionResponse;
use Omnipay\NSWGOVCPP\FetchTransactionRequestException;
use Omnipay\NSWGOVCPP\Gateway as CppGateway;
use Omnipay\NSWGOVCPP\ParameterStorage;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Omnipay\GatewayInfo;

/**
 * Service class to access the CPP daily reconciliation results
 * @author James
 */
class DailyReportService
{
    use Configurable;

    /**
     * The raw report data, if it could not be retrieved, boolean false
     * @var mixed
     */
    protected $reportRaw = false;

    /**
     * Stores errors when parsing the report
     * @var array
     */
    protected $errors = [];

    /**
     * Stores reconciliation errors when parsing the report (e.g amounts differing between report and payment)
     * @var array
     */
    protected $reconciliationErrors = [];

    /**
     * The number of items in the report
     * @var array
     */
    protected $reportLength = 0;

    /*/
     */
    public function getForDate(\Datetime $datetime)
    {

        // get all parameters for the NSWGOVCPP gateway
        $parameters =  GatewayInfo::getConfigSetting(Payment::CPP_GATEWAY_CODE, 'parameters');
        ParameterStorage::setAll($parameters);
        $dailyReconciliationUrl = $parameters['dailyReconciliationUrl'] ?? '';
        if (!$dailyReconciliationUrl) {
            throw new \Exception(
                _t(
                    __CLASS__ . '.PAYMENT_STATUS_NO_RECONCILIATION_URL',
                    "The system is not configured to reconcile payments - the CPP dailyReconciliation URL is required"
                )
            );
        }
        $gateway = Omnipay::create(Payment::CPP_GATEWAY_CODE);
        // get the transaction via the payment reference
        $request = $gateway->dailyReconciliation([
            'reconciliationDate' => $datetime
        ]);
        // send the request, get response
        $response = $request->send();
        $this->reportRaw = $response->getReconciliationReport();
        return $this;
    }

    public function getErrors() : array
    {
        return $this->errors;
    }

    public function getReconciliationErrors() : array
    {
        return $this->reconciliationErrors;
    }

    public function getReportLength() : int
    {
        return $this->reportLength;
    }

    /**
     * Build the report from the raw report data, resetting error values and the link
     * @return array
     */
    private function buildReportFromRaw() : array
    {
        $this->reportLength = 0;
        $this->errors = [];
        $this->reconciliationErrors = [];
        if ($this->reportRaw === false || !is_string($this->reportRaw)) {
            throw new \Exception("Failed to retrieve the raw report data");
        }
        // read the report into memory to use fgetcsv
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $this->reportRaw);
        rewind($stream);
        // empty report
        $report = [];
        $c = 0;
        $headers = [];
        while (($data = fgetcsv($stream, 0, ",", '"')) !== false) {
            if ($c == 0) {
                // the first line contains the report header
                $headers = $data;
            } elseif (!empty($headers)) {
                // pad out the data to ensure array_combine doesn't fail
                // this can occur if empty values are in the CSV at the end
                $report[] = array_combine($headers, array_pad($data, count($headers), ""));
            }
            $c++;
        }
        fclose($stream);
        return $report;
    }

    /**
     * Process the current report
     */
    public function process()
    {
        $report = $this->buildReportFromRaw();
        foreach ($report as $line) {
            try {
                $this->reportLength++;
                $datetime = new \Datetime();
                $agencyTransactionId = $line[ "Agency Transaction ID" ];
                // try to get a payment
                $payment = Payment::getByAgencyTransactionId($agencyTransactionId);
                $payment->RecReportDateTime = $datetime->format('Y-m-d H:i:s');
                $amount = $line[ 'Amount' ];
                $payment->RecAmountAmount = $amount;
                if ($amount != $payment->AmountAmount) {
                    $this->reconciliationErrors[] = $payment;
                }
                $payment->RecPaymentCompletionDate = $line[ 'Payment Completed Date' ];
                $payment->RecAgencySettlementDate = $line[ 'Agency Settlement Date' ];
                $payment->RecGLIP = $line [ 'GLIP ID' ];
                // save the reconciliation data
                $payment->write();
            } catch (\Exception $e) {
                $this->errors[] = $line;
            }
        }
        return $this->reportLength > 0;
    }
}
