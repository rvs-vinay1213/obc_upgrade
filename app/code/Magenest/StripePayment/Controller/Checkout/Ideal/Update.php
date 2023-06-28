<?php

namespace Magenest\StripePayment\Controller\Checkout\Ideal;

use Magenest\StripePayment\Exception\StripePaymentException;
use Magento\Framework\Controller\ResultFactory;

class Update extends \Magenest\StripePayment\Controller\Checkout\Source
{
    const DEBUG_DATA = "iDEAL Element updating source";
    /**
     * @return array
     */
    public function execute()
    {
        $this->_debug(self::DEBUG_DATA);
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        try {
            if (!$this->_formKeyValidator->validate($this->getRequest())) {
                throw new StripePaymentException(
                    __("Invalid form key")
                );
            }
            $quote = $this->_checkoutSession->getQuote();
            $source = $this->getRequest()->getParam('source');
            $this->_debug($source);
            $sourceId = isset($source['id']) ? $source['id'] : "";
            $clientSecret = isset($source['client_secret']) ? $source['client_secret'] : "";
            if (!$sourceId) {
                throw new StripePaymentException(
                    __("Source error")
                );
            }
            $payment = $quote->getPayment();
            $sourceModel = $this->sourceFactory->create();
            $sourceModel->setData("source_id", $sourceId);
            $sourceModel->setData("method", $payment->getMethod());
            $sourceModel->setData("quote_id", $quote->getEntityId());
            $sourceModel->isObjectNew(true);
            $sourceModel->save();
            $payment->setAdditionalInformation("stripe_source_id", $sourceId);
            $payment->setAdditionalInformation("stripe_client_secret", $clientSecret);
            $quote->save();

            $data = [
                'success' => true,
                'error' => false,
            ];
            $result->setData($data);
        } catch (\Magenest\StripePayment\Exception\StripePaymentException $e) {
            $result->setData([
                'error' => true,
                'success' => false,
                'message' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            $result->setData([
                'error' => true,
                'success' => false,
                'message' => __("Cannot process payment")
            ]);
        } finally {
            return $result;
        }
    }

    /**
     * @return array
     */
    protected function getPostRequest($quote)
    {
        return [];
    }

    protected function getReturnUrl()
    {
        // TODO: Implement getReturnUrl() method.
    }

    protected function getSourceType()
    {
        // TODO: Implement getSourceType() method.
    }
}
