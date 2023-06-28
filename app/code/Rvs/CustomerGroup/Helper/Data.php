<?php

namespace Rvs\CustomerGroup\Helper;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Translate\Inline\StateInterface;
use Rvs\CustomerGroup\Model\Mail\TransportBuilder;
use Magento\Framework\App\Area;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Filesystem\Io\File;

/**
 * Class SendMail
 * @package Magenest\Ticket\Controller\Adminhtml\Ticket   ,
 */
class Data extends Action
{
    /**
     * @var StateInterface
     */
    protected $inlineTranslation;

    /**
     * @var TransportBuilder
     */
    protected $_transportBuilder;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * SendMail constructor.
     * @param Context $context
     */
    protected $orderRepository;

    protected $deliveryModel;

    public function __construct(
        Context $context,
        StateInterface $inlineTranslation,
        TransportBuilder $transportBuilder,
        StoreManagerInterface $storeManager,
        File $file,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        // \Amasty\Checkout\Model\Delivery $deliveryModel,
        \Amasty\CheckoutDeliveryDate\Model\DeliveryDateProvider $deliveryModel,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface

    )
    {
        parent::__construct($context);
        $this->orderRepository = $orderRepository;
        $this->deliveryModel = $deliveryModel;
        $this->inlineTranslation = $inlineTranslation;
        $this->_transportBuilder = $transportBuilder;
        $this->_storeManager = $storeManager;
        $this->file = $file;
        $this->_customerRepositoryInterface = $customerRepositoryInterface;
    }

    /**
     * @return $this
     */
    public function execute()
    {
        $resultPage = $this->resultRedirectFactory->create();
        $this->sendMail();
        return $resultPage->setPath('*/*/index');
    }

    /**
     * Send Mail to customer
     *
     * @param $eventName
     */
    public function generateCSV($orderId)
    {

        $writers = new \Zend\Log\Writer\Stream(BP . '/var/log/csv.log');
        $clogger = new \Zend\Log\Logger();
        $clogger->addWriter($writers);


        $clogger->info("=====================================");
        $clogger->info("Generate Csv Start");
        $clogger->info("Order ID:" . $orderId);
        try {


            $orderData = $this->orderRepository->get($orderId);

            $data = $orderData->getData();
            $payment = $orderData->getPayment();
            $method = $payment->getMethodInstance();
            $order_details = $orderData->getIncrementId();
            $storeId = $orderData->getStoreId();
            $entity_id = $orderData->getEntityId();
            $quoteid = $orderData->getQuoteId();
            $deliveryData = $this->deliveryModel->findByQuoteId($quoteid);
            //$deliveryData = $this->deliveryModel->findByOrderId($orderId);
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // Instance of Object Manager
            $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
            $storeManager = $objectManager->get('Magento\Store\Model\StoreManagerInterface');
            $currencyCode = $storeManager->getStore()->getCurrentCurrencyCode();
            $currency = $objectManager->create('Magento\Directory\Model\CurrencyFactory')->create()->load($currencyCode);

            $connection = $resource->getConnection();
            // $entity_id = $orderData->getEntityId();
            $sql = "SELECT * FROM sales_order_status_history where parent_id = '$entity_id'";
            $result = $connection->fetchAll($sql);
            $cmt = '';
            $customer = $this->_customerRepositoryInterface->getById($orderData->getCustomerId());
            if (null !== $customer->getCustomAttribute('telephone_customfield')) {
                $customerTelephone = $customer->getCustomAttribute('telephone_customfield')->getValue();
            } else {
                $customerTelephone = '';
            }

            $payment_mthd = $payment->getMethod();

            $sql = "SELECT * FROM amasty_amcheckout_delivery where order_id = '$entity_id'";
            $result = $connection->fetchAll($sql);
            if (!empty($result)) {
                foreach ($result as $val) {
                    if ($payment_mthd == "worldpayform" || $payment_mthd == "checkmo") {
                        $cmt = $val['comment'];
                    } else {
                        $cmt = $val['comment'];
                    }
                }
            } else {
                if ($storeId == 2) {
                    $sql1 = "SELECT * FROM sales_order_status_history where parent_id = '$entity_id'";
                    $result1 = $connection->fetchAll($sql1);
                    date_default_timezone_set('Europe/London');
                    $date_time = date("Y-m-d");
                    $time = date("h");
                    foreach ($result1 as $val) {
                        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/test.log');
                        $logger = new \Zend\Log\Logger();
                        $logger->addWriter($writer);
                        $logger->info($entity_id);
                        if ($val['is_customer_notified'] == '1') {
                            $logger->info('Your text message3334566');
                            $cmt = $val['comment'];
                            $sql = "INSERT INTO `amasty_amcheckout_delivery`(`id`, `order_id`, `quote_id`, `date`, `time`, `comment`) VALUES ('',$entity_id,$quoteid,'$date_time','$time','$cmt')";
                            break;
                        } else {
                            $logger->info('no result');
                            $cmt = '';
                        }

                    }
                    $connection->query($sql);
                }
            }


            $date = $deliveryData->getDate();
            $newDate = date("d-m-Y", strtotime($date));
            $sqlFooman = "SELECT * FROM fooman_totals_order";
            $resultFooman = $connection->fetchAll($sqlFooman);

            $selectedtime = $deliveryData->getTime();
            if (is_null($selectedtime))
                $time = ''; // OLD TIME: 11:00 - 12:00
            else
                $time = $selectedtime . ':00 - ' . (($selectedtime) + 1) . ':00';

            $csv_time = $time;

            $CreatedAt = \Magento\Framework\App\ObjectManager::getInstance()
                ->create(\Magento\Framework\Intl\DateTimeFactory::class)
                ->create($orderData->getCreatedAt(), new \DateTimeZone('Europe/London')) // Update timezone, for example use from config
                ->format('d-m-Y - g:i');
            $IncrementId = $orderData->getIncrementId();

            $clogger->info("Customer Email:" . $orderData->getCustomerEmail());
            if (!$orderData->getIsVirtual()) {
                $bill_name = $orderData->getBillingAddress()->getFirstname();
                $bill_lname = $orderData->getBillingAddress()->getLastname();
                $bill_compny = $orderData->getBillingAddress()->getCompany();
                $street_address = $orderData->getBillingAddress()->getStreet();
                $billingStreet = $street_address[0];
                $bill_city = $orderData->getBillingAddress()->getCity();
                $bill_state = $orderData->getBillingAddress()->getRegion();
                $bill_postcode = $orderData->getBillingAddress()->getPostcode();
                $bill_country = $orderData->getBillingAddress()->getCountryId();
                $bill_telephone = $orderData->getBillingAddress()->getTelephone();
                $cust_email = $orderData->getCustomerEmail();
                $methodTitle = $method->getTitle();
                $ship_name = $orderData->getShippingAddress()->getFirstname();
                $ship_lname = $orderData->getShippingAddress()->getLastname();
                $ship_company = $orderData->getShippingAddress()->getCompany();
                $street_addr = $orderData->getShippingAddress()->getStreet();
                $shippingStreet = $street_addr[0];
                $ship_city = $orderData->getShippingAddress()->getCity();
                $ship_state = $orderData->getShippingAddress()->getRegion();
                $ship_postcode = $orderData->getShippingAddress()->getPostcode();
                $ship_country = $orderData->getShippingAddress()->getCountryId();
                $ship_telephone = $orderData->getShippingAddress()->getTelephone();
                $special_inst = $cmt;
                $payment_mthd = $payment->getMethod();
                $payment_title = $payment->getTitle();
                $customername = $bill_name . ' ' . $bill_lname;

                $deliveryfname = $ship_name . ' ' . $ship_lname;

                $shipcountry = $objectManager->create('\Magento\Directory\Model\Country')->load($ship_country)->getName();
                $billcountry = $objectManager->create('\Magento\Directory\Model\Country')->load($bill_country)->getName();


                $delivery_addr_html = '<p class="x_196354974MsoNormal" style="line-height: 16.2pt"><span style="font-size: 9pt; font-family: &quot;Verdana&quot;, sans-serif; color: rgb(47, 47, 47)">' . $deliveryfname . '<br>' . $ship_company . '<br>' . $shippingStreet . '<br>' . $ship_city . '<br>';
                if ($ship_state != '') {
                    $delivery_addr_html .= $ship_state . '<br>';
                }
                $delivery_addr_html .= $ship_postcode . '<br>' . $shipcountry . '<br>T:' . $ship_telephone . '<br>';
                if ($customerTelephone != '') {
                    $delivery_addr_html .= 'Mobile:' . $customerTelephone . '<br>';
                }
                $delivery_addr_html .= 'E-mail: <a href="mailto:' . $cust_email . '" target="_blank">' . $cust_email . '</a> </span></p>';

                $clogger->info("Deliver Address:" . $delivery_addr_html);
                $payment_method_html = "";
                $extra_fee_charge = '';
                foreach ($resultFooman as $keyFooman) {
                    if ($keyFooman['order_id'] == $orderId) {
                        $foomanAmount = $keyFooman['amount'];
                        $foomanLabel = $keyFooman['label'];
                        if ($foomanAmount > 0) {
                            $foomanAmount = number_format($foomanAmount, 2);
                            $currencySymbol = $currency->getCurrencySymbol();
                            $extra_fee_charge = "<tr>
                                    <td colspan='3' style='border: none; padding: 2.25pt 6.75pt 2.25pt 6.75pt'>
                                       <p class='x_196354974MsoNormal' align='right' style='text-align: right; line-height: 16.2pt'><span style='font-size: 8.5pt; font-family: &quot;Verdana&quot;, sans-serif; color: rgb(47, 47, 47)'>" . $foomanLabel . "</span></p>
                                    </td>
                                    <td style='border: none; padding: 2.25pt 6.75pt 2.25pt 6.75pt'>
                                       <p class='x_196354974MsoNormal' align='right' style='text-align: right; line-height: 16.2pt'><span class='x_196354974price'><span style='font-size: 8.5pt; font-family: &quot;Verdana&quot;, sans-serif; color: rgb(47, 47, 47)'>" . $currencySymbol . $foomanAmount . "</span></span><span style='font-size: 8.5pt; font-family: &quot;Verdana&quot;, sans-serif; color: rgb(47, 47, 47)'> </span></p>
                                    </td>
                                 </tr>";
                        } else {

                            $extra_fee_charge = '';
                        }
                    }
                }
                $date_time = $newDate . ' - ' . $time;


                $billing_info_html = '<p class="x_196354974MsoNormal" style="line-height: 16.2pt"><span style="font-size: 9pt; font-family: &quot;Verdana&quot;, sans-serif; color: rgb(47, 47, 47)">' . $customername . '<br>' . $bill_compny . '<br>' . $billingStreet . '<br>' . $bill_city . '<br>';
                if ($bill_state != '') {
                    $billing_info_html .= $bill_state . '<br/>';
                }
                $billing_info_html .= $bill_postcode . '<br>' . $billcountry . '<br>T:' . $bill_telephone . '<br>';
                if ($customerTelephone != '') {
                    $billing_info_html .= 'Mobile' . $customerTelephone . '<br>';
                }
                $billing_info_html .= '&nbsp; </span></p>';

                $delivery_info_html = '<p class="x_196354974MsoNormal" style="margin-bottom: 12pt; line-height: 16.2pt"><span style="font-size: 9pt; font-family: &quot;Verdana&quot;, sans-serif; color: rgb(47, 47, 47)">Delivery Date: <b>' . $date_time . '</b> <br>Order Instructions: <b>' . $special_inst . '</b></span></p>';


                $priceHelper = $objectManager->create('Magento\Framework\Pricing\Helper\Data'); // Instance of Pricing Helper
                $subtotal = $priceHelper->currency($orderData->getSubtotal(), true, false);
                $tax_amount = $priceHelper->currency($orderData->getBaseTaxAmount(), true, false);
                $ord_grandtotal = $priceHelper->currency($orderData->getGrandTotal(), true, false);
                $delivery_charge = $priceHelper->currency($orderData->getShippingAmount(), true, false);

                $csv_subtotal = $orderData->getSubtotal();
                $csv_tax_amount = $orderData->getBaseTaxAmount();
                $csv_ord_grandtotal = $orderData->getGrandTotal();
                $csv_delivery_charge = $orderData->getShippingAmount();

                //$csv_data = "$IncrementId,$bill_name,$bill_compny,$billingStreet,$bill_city,$bill_state,$bill_postcode,$bill_country,$bill_telephone,$cust_email,$methodTitle,$ship_name,$ship_company,$shippingStreet,$ship_city,$ship_state,$ship_postcode,$ship_country,$date,$time,$special_inst,$pro_name,$pro_sku,$qty_ord,$pro_price,$subtotal,$delivery_charge,$tax_amount,$ord_grandtotal";
                $subject = $IncrementId . '-' . $bill_name . '-' . $newDate . ' ' . $time;
                $filename = 'Owen Brothers: New Order #' . $IncrementId . '-' . $ship_company . '-' . $newDate . '-' . $time . '.csv';


                $clogger->info("Email Subject:" . $subject);
                $orderArray = [];
                $i = 1;
                foreach ($orderData->getAllItems() as $item) {
                    $pro_name = $item->getName();
                    $pro_sku = $item->getSku();
                    $qty_ord = intval($item->getQtyOrdered());
                    $pro_prices = $item->getPrice();
                    $pro_price = $pro_prices * $qty_ord;

                    if ($i == 1)
                        $orderArray[] = array($IncrementId, $bill_name, $bill_lname, $bill_compny, $billingStreet, $bill_city, $bill_state, $bill_postcode, $bill_country, $bill_telephone, $cust_email, $methodTitle, $ship_name, $ship_company, $shippingStreet, $ship_city, $ship_state, $ship_postcode, $ship_country, $date, $csv_time, $special_inst, $pro_name, $pro_sku, $qty_ord, $pro_price, $csv_subtotal, $csv_delivery_charge, $csv_tax_amount, $csv_ord_grandtotal);
                    else
                        $orderArray[] = array('', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', $pro_name, $pro_sku, $qty_ord, $pro_price, '', '', '', '');

                    $i++;
                }
                $html_format = '';
                $currency = $objectManager->get('Magento\Directory\Model\Currency');

                foreach ($orderData->getAllItems() as $item) {
                    $pro_name = $item->getName();
                    $pro_sku = $item->getSku();
                    $qty_ords = intval($item->getQtyOrdered());
                    $pro_prices = $item->getPrice();
                    //$qty_ord = strstr($qty_ords, '.', true);
                    $qty_ord = $qty_ords;
                    $final_subtotal = $pro_prices * $qty_ord;


                    $pro_price = $currency->format($final_subtotal, ['display' => \Zend_Currency::NO_SYMBOL], false);

                    $html_format .= "<tr>
                    <td valign='top' style='border: none; border-bottom: dotted rgb(204, 204, 204) 1pt; padding: 2.25pt 6.75pt 2.25pt 6.75pt'>
                       <p class='x_196354974MsoNormal' style='line-height: 16.2pt'><strong><span style='font-size: 8.5pt; font-family: &quot;Verdana&quot;, sans-serif; color: rgb(47, 47, 47)'>$pro_name</span></strong><span style='font-size: 8.5pt; font-family: &quot;Verdana&quot;, sans-serif; color: rgb(47, 47, 47)'> </span></p>
                    </td>
                                                        <td valign='top' style='border: none; border-bottom: dotted rgb(204, 204, 204) 1pt; padding: 2.25pt 6.75pt 2.25pt 6.75pt'>
                       <p class='x_196354974MsoNormal' style='line-height: 16.2pt'><span style='font-size: 8.5pt; font-family: &quot;Verdana&quot;, sans-serif; color: rgb(47, 47, 47)'>$pro_sku</span></p>
                    </td>

                    <td valign='top' style='border: none; border-bottom: dotted rgb(204, 204, 204) 1pt; padding: 2.25pt 6.75pt 2.25pt 6.75pt'>
                       <p class='x_196354974MsoNormal' align='center' style='text-align: center; line-height: 16.2pt'><span style='font-size: 8.5pt; font-family: &quot;Verdana&quot;, sans-serif; color: rgb(47, 47, 47)'>$qty_ord</span></p>
                    </td>
                    <td valign='top' style='border: none; border-bottom: dotted rgb(204, 204, 204) 1pt; padding: 2.25pt 6.75pt 2.25pt 6.75pt'>
                       <p class='x_196354974MsoNormal' align='right' style='text-align: right; line-height: 16.2pt'><span class='x_196354974price'><span style='font-size: 8.5pt; font-family: &quot;Verdana&quot;, sans-serif; color: rgb(47, 47, 47)'>£$pro_price</span></span><span style='font-size: 8.5pt; font-family: &quot;Verdana&quot;, sans-serif; color: rgb(47, 47, 47)'> </span></p>
                    </td>
                 </tr>";
                }


                $pdfFile = 'order.csv';
                $fp = fopen($pdfFile, 'w+');
                fputcsv($fp, array('Order Id', 'Billing First Name', 'Billing Last Name', 'Billing Company', 'Billing Street', 'Billing City', 'Billing County', 'Billing Postcode', 'Billing Country', 'Telephone', 'Email', 'Payment Method', 'Delivery Name', 'Delivery Company', 'Delivery Street', 'Delivery City', 'Delivery County', 'Delivery Postcode', 'Delivery Country', 'Delivery Date', 'Delivery Time', 'Order Instructions', 'Order Items', 'SKU', 'QTY', 'Price', 'Sub Total', 'Delivery Charge', 'VAT', 'Grand Total'));

                foreach ($orderArray as $csv) {
                    fputcsv($fp, $csv);
                }
                rewind($fp);
                fclose($fp);


                $clogger->info("Start Email Send to client");
                $this->inlineTranslation->suspend();
                $transport = $this->_transportBuilder->setTemplateIdentifier('hello_template')->setTemplateOptions(
                    [
                        'area' => Area::AREA_FRONTEND,
                        'store' => $this->_storeManager->getStore()->getId(),
                    ]
                )->setTemplateVars(
                    [

                        'message' => 'Please find attached Order CSV below',
                        'order_no' => $IncrementId,
                        'compny_name' => $ship_company,
                        'date_time' => $date_time,

                        'subject' => $subject,
                    ]
                )->setFrom(
                    [
                        'email' => 'sales@owenbrotherscatering.com',
                        'name' => 'Sales'
                    ]
                )->addTo(
                    'shehan@owenbrotherscatering.com'
                );
                $transport = $this->_transportBuilder->getTransport();
                $html = "Please find attached Order CSV below";
                $bodyMessage = new \Zend\Mime\Part($html);
                $bodyMessage->type = 'text/html';
                $attachment = $this->_transportBuilder->addAttachment(file_get_contents($pdfFile), $filename);
                $bodyPart = new \Zend\Mime\Message();
                $bodyPart->setParts(array($bodyMessage, $attachment));
                $transport->getMessage()->setBody($bodyPart);

                // $transport->sendMessage();
                
                $this->inlineTranslation->resume();

                $clogger->info("Start Email Send to Customer");
                $clogger->info("Send To Email:" . $cust_email);
                /* Second code for email */

                $this->inlineTranslation->suspend();
                $transport = $this->_transportBuilder->setTemplateIdentifier('hello_template2')->setTemplateOptions(
                    [
                        'area' => Area::AREA_FRONTEND,
                        'store' => $this->_storeManager->getStore()->getId(),
                    ]
                )->setTemplateVars(
                    [
                        'pro_names' => $html_format,
                        'cust_name' => $customername,
                        'order_no' => $IncrementId,
                        'compny_name' => $ship_company,
                        'date_time' => $date_time,
                        'order_date' => $CreatedAt,
                        'delivery_addr' => $delivery_addr_html,
                        'payment_method' => $methodTitle,
                        'extra_fee_charge' => $extra_fee_charge,
                        'billing_info' => $billing_info_html,
                        'delivery_info' => $delivery_info_html,
                        'subtotal' => $subtotal,
                        'tax_amount' => $tax_amount,
                        'delivery_charge' => $delivery_charge,
                        'ord_grandtotal' => $ord_grandtotal,
                        'message' => '',
                        'subject' => $subject,
                    ]
                )->setFrom(
                    [
                        'email' => 'sales@owenbrotherscatering.com',
                        'name' => 'Sales'
                    ]
                )->addTo(
                    $cust_email
                )->addBcc(
                    array('orders@owenbrotherscatering.com')
                )->addcc(
                    array('john@owenbrotherscatering.co.uk', 'sales@owenbrotherscatering.com')
                )->getTransport();
                $transport->sendMessage();
                $this->inlineTranslation->resume();
            } else {
                date_default_timezone_set('Europe/London');
                $bill_name = $orderData->getBillingAddress()->getFirstname();
                $bill_lname = $orderData->getBillingAddress()->getLastname();
                $bill_compny = $orderData->getBillingAddress()->getCompany();
                $street_address = $orderData->getBillingAddress()->getStreet();
                $billingStreet = $street_address[0];
                $bill_city = $orderData->getBillingAddress()->getCity();
                $bill_state = $orderData->getBillingAddress()->getRegion();
                $bill_postcode = $orderData->getBillingAddress()->getPostcode();
                $bill_country = $orderData->getBillingAddress()->getCountryId();
                $bill_telephone = $orderData->getBillingAddress()->getTelephone();
                $cust_email = $orderData->getCustomerEmail();
                $methodTitle = $method->getTitle();
                $ship_name = 'Bellville';
                $ship_lname = 'Brewery';
                $ship_company = 'Belleville Brewing Company';
                $street_addr1 = 'The Taproom';
                $shippingStreet = '44 jaggard way, Balham';
                $ship_city = 'London';
                $ship_state = '';
                $ship_postcode = 'SW12 8SG';
                $ship_country = 'United Kingdom';
                $ship_telephone = '';
                $special_inst = $cmt;
                $payment_mthd = $payment->getMethod();
                $payment_title = $payment->getTitle();
                $customername = $bill_name . ' ' . $bill_lname;

                $deliveryfname = $ship_name . ' ' . $ship_lname;

                $shipcountry = 'United Kingdom';
                $billcountry = $objectManager->create('\Magento\Directory\Model\Country')->load($bill_country)->getName();


                $delivery_addr_html = '<p class="x_196354974MsoNormal" style="line-height: 16.2pt"><span style="font-size: 9pt; font-family: &quot;Verdana&quot;, sans-serif; color: rgb(47, 47, 47)">' . $ship_company . '<br>' . $street_addr1 . '<br>' . $shippingStreet . '<br>' . $ship_city . '<br>';
                if ($ship_state != '') {
                    $delivery_addr_html .= $ship_state . '<br>';
                }
                $delivery_addr_html .= $ship_postcode . '<br>' . $shipcountry . '<br>T:' . $ship_telephone . '<br>';
                if ($customerTelephone != '') {
                    $delivery_addr_html .= 'Mobile:' . $customerTelephone . '<br>';
                }
                $delivery_addr_html .= 'E-mail: <a href="mailto:' . $cust_email . '" target="_blank">' . $cust_email . '</a> </span></p>';


                $payment_method_html = "";
                $extra_fee_charge = '';
                foreach ($resultFooman as $keyFooman) {
                    if ($keyFooman['order_id'] == $orderId) {
                        $foomanAmount = $keyFooman['amount'];
                        $foomanLabel = $keyFooman['label'];
                        if ($foomanAmount > 0) {
                            $foomanAmount = number_format($foomanAmount, 2);
                            $currencySymbol = $currency->getCurrencySymbol();
                            $extra_fee_charge = "<tr>
                                    <td colspan='3' style='border: none; padding: 2.25pt 6.75pt 2.25pt 6.75pt'>
                                       <p class='x_196354974MsoNormal' align='right' style='text-align: right; line-height: 16.2pt'><span style='font-size: 8.5pt; font-family: &quot;Verdana&quot;, sans-serif; color: rgb(47, 47, 47)'>" . $foomanLabel . "</span></p>
                                    </td>
                                    <td style='border: none; padding: 2.25pt 6.75pt 2.25pt 6.75pt'>
                                       <p class='x_196354974MsoNormal' align='right' style='text-align: right; line-height: 16.2pt'><span class='x_196354974price'><span style='font-size: 8.5pt; font-family: &quot;Verdana&quot;, sans-serif; color: rgb(47, 47, 47)'>" . $currencySymbol . $foomanAmount . "</span></span><span style='font-size: 8.5pt; font-family: &quot;Verdana&quot;, sans-serif; color: rgb(47, 47, 47)'> </span></p>
                                    </td>
                                 </tr>";
                        } else {

                            $extra_fee_charge = '';
                        }
                    }
                }

                $date_time = date("d-m-Y H:i");


                $billing_info_html = '<p class="x_196354974MsoNormal" style="line-height: 16.2pt"><span style="font-size: 9pt; font-family: &quot;Verdana&quot;, sans-serif; color: rgb(47, 47, 47)">' . $customername . '<br>' . $bill_compny . '<br>' . $billingStreet . '<br>' . $bill_city . '<br>';
                if ($bill_state != '') {
                    $billing_info_html .= $bill_state . '<br/>';
                }
                $billing_info_html .= $bill_postcode . '<br>' . $billcountry . '<br>T:' . $bill_telephone . '<br>';
                if ($customerTelephone != '') {
                    $billing_info_html .= 'Mobile:' . $customerTelephone . '<br>';
                }
                $billing_info_html .= '&nbsp; </span></p>';

                $delivery_info_html = '<p class="x_196354974MsoNormal" style="margin-bottom: 12pt; line-height: 16.2pt"><span style="font-size: 9pt; font-family: &quot;Verdana&quot;, sans-serif; color: rgb(47, 47, 47)">Delivery Date: <b>' . $date_time . '</b> <br>Order Instructions: <b>' . $special_inst . '</b></span></p>';


                $priceHelper = $objectManager->create('Magento\Framework\Pricing\Helper\Data'); // Instance of Pricing Helper
                $subtotal = $priceHelper->currency($orderData->getSubtotal(), true, false);
                $tax_amount = $priceHelper->currency($orderData->getBaseTaxAmount(), true, false);
                $ord_grandtotal = $priceHelper->currency($orderData->getGrandTotal(), true, false);
                $delivery_charge = $priceHelper->currency($orderData->getShippingAmount(), true, false);

                $csv_subtotal = $orderData->getSubtotal();
                $csv_tax_amount = $orderData->getBaseTaxAmount();
                $csv_ord_grandtotal = $orderData->getGrandTotal();
                $csv_delivery_charge = $orderData->getShippingAmount();

                //$csv_data = "$IncrementId,$bill_name,$bill_compny,$billingStreet,$bill_city,$bill_state,$bill_postcode,$bill_country,$bill_telephone,$cust_email,$methodTitle,$ship_name,$ship_company,$shippingStreet,$ship_city,$ship_state,$ship_postcode,$ship_country,$date,$time,$special_inst,$pro_name,$pro_sku,$qty_ord,$pro_price,$subtotal,$delivery_charge,$tax_amount,$ord_grandtotal";
                $subject = $IncrementId . '-' . $bill_name . '-' . $date_time;
                $filename = 'Owen Brothers: New Order #' . $IncrementId . '-' . $ship_company . '-' . $date_time . '.csv';

                $orderArray = [];
                $i = 1;
                foreach ($orderData->getAllItems() as $item) {
                    $pro_name = $item->getName();
                    $pro_sku = $item->getSku();
                    $qty_ord = intval($item->getQtyOrdered());
                    $pro_prices = $item->getPrice();
                    $pro_price = $pro_prices * $qty_ord;

                    if ($i == 1)
                        $orderArray[] = array($IncrementId, $bill_name, $bill_lname, $bill_compny, $billingStreet, $bill_city, $bill_state, $bill_postcode, $bill_country, $bill_telephone, $cust_email, $methodTitle, $ship_name, $ship_company, $shippingStreet, $ship_city, $ship_state, $ship_postcode, $ship_country, $date, $csv_time, $special_inst, $pro_name, $pro_sku, $qty_ord, $pro_price, $csv_subtotal, $csv_delivery_charge, $csv_tax_amount, $csv_ord_grandtotal);
                    else
                        $orderArray[] = array('', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', $pro_name, $pro_sku, $qty_ord, $pro_price, '', '', '', '');

                    $i++;
                }
                $html_format = '';
                $currency = $objectManager->get('Magento\Directory\Model\Currency');

                foreach ($orderData->getAllItems() as $item) {
                    $pro_name = $item->getName();
                    $pro_sku = $item->getSku();
                    $qty_ords = intval($item->getQtyOrdered());
                    $pro_prices = $item->getPrice();
                    //$qty_ord = strstr($qty_ords, '.', true);
                    $qty_ord = $qty_ords;
                    $final_subtotal = $pro_prices * $qty_ord;

                    $pro_price = $currency->format($final_subtotal, ['display' => \Zend_Currency::NO_SYMBOL], false);

                    $html_format .= "<tr>
                    <td valign='top' style='border: none; border-bottom: dotted rgb(204, 204, 204) 1pt; padding: 2.25pt 6.75pt 2.25pt 6.75pt'>
                       <p class='x_196354974MsoNormal' style='line-height: 16.2pt'><strong><span style='font-size: 8.5pt; font-family: &quot;Verdana&quot;, sans-serif; color: rgb(47, 47, 47)'>$pro_name</span></strong><span style='font-size: 8.5pt; font-family: &quot;Verdana&quot;, sans-serif; color: rgb(47, 47, 47)'> </span></p>
                    </td>
                                                        <td valign='top' style='border: none; border-bottom: dotted rgb(204, 204, 204) 1pt; padding: 2.25pt 6.75pt 2.25pt 6.75pt'>
                       <p class='x_196354974MsoNormal' style='line-height: 16.2pt'><span style='font-size: 8.5pt; font-family: &quot;Verdana&quot;, sans-serif; color: rgb(47, 47, 47)'>$pro_sku</span></p>
                    </td>
        
                    <td valign='top' style='border: none; border-bottom: dotted rgb(204, 204, 204) 1pt; padding: 2.25pt 6.75pt 2.25pt 6.75pt'>
                       <p class='x_196354974MsoNormal' align='center' style='text-align: center; line-height: 16.2pt'><span style='font-size: 8.5pt; font-family: &quot;Verdana&quot;, sans-serif; color: rgb(47, 47, 47)'>$qty_ord</span></p>
                    </td>
                    <td valign='top' style='border: none; border-bottom: dotted rgb(204, 204, 204) 1pt; padding: 2.25pt 6.75pt 2.25pt 6.75pt'>
                       <p class='x_196354974MsoNormal' align='right' style='text-align: right; line-height: 16.2pt'><span class='x_196354974price'><span style='font-size: 8.5pt; font-family: &quot;Verdana&quot;, sans-serif; color: rgb(47, 47, 47)'>£$pro_price</span></span><span style='font-size: 8.5pt; font-family: &quot;Verdana&quot;, sans-serif; color: rgb(47, 47, 47)'> </span></p>
                    </td>
                 </tr>";
                }


                $pdfFile = 'order.csv';
                $fp = fopen($pdfFile, 'w+');
                fputcsv($fp, array('Order Id', 'Billing First Name', 'Billing Last Name', 'Billing Company', 'Billing Street', 'Billing City', 'Billing County', 'Billing Postcode', 'Billing Country', 'Telephone', 'Email', 'Payment Method', 'Delivery Name', 'Delivery Company', 'Delivery Street', 'Delivery City', 'Delivery County', 'Delivery Postcode', 'Delivery Country', 'Delivery Date', 'Delivery Time', 'Order Instructions', 'Order Items', 'SKU', 'QTY', 'Price', 'Sub Total', 'Delivery Charge', 'VAT', 'Grand Total'));

                foreach ($orderArray as $csv) {
                    fputcsv($fp, $csv);
                }
                rewind($fp);
                fclose($fp);
                $clogger->info("Send Virtual Email:" . $cust_email);
                $this->inlineTranslation->suspend();
                $transport = $this->_transportBuilder->setTemplateIdentifier('hello_template')->setTemplateOptions(
                    [
                        'area' => Area::AREA_FRONTEND,
                        'store' => $this->_storeManager->getStore()->getId(),
                    ]
                )->setTemplateVars(
                    [

                        'message' => 'Please find attached Order CSV below',
                        'order_no' => $IncrementId,
                        'compny_name' => $ship_company,
                        'date_time' => $date_time,

                        'subject' => $subject,
                    ]
                )->setFrom(
                    [
                        'email' => 'sales@owenbrotherscatering.com',
                        'name' => 'Sales'
                    ]
                )->addTo(
                    'shehan@owenbrotherscatering.com'
                );
                $transport = $this->_transportBuilder->getTransport();
                $html = "Please find attached Order CSV below";
                $bodyMessage = new \Zend\Mime\Part($html);
                $bodyMessage->type = 'text/html';
                $attachment = $this->_transportBuilder->addAttachment(file_get_contents($pdfFile), $filename);
                $bodyPart = new \Zend\Mime\Message();
                $bodyPart->setParts(array($bodyMessage, $attachment));
                $transport->getMessage()->setBody($bodyPart);

                // $transport->sendMessage();
                $this->inlineTranslation->resume();

                /* Second code for email */

                $this->inlineTranslation->suspend();
                $transport = $this->_transportBuilder->setTemplateIdentifier('hello_template2')->setTemplateOptions(
                    [
                        'area' => Area::AREA_FRONTEND,
                        'store' => $this->_storeManager->getStore()->getId(),
                    ]
                )->setTemplateVars(
                    [
                        'pro_names' => $html_format,
                        'cust_name' => $customername,
                        'order_no' => $IncrementId,
                        'compny_name' => $ship_company,
                        'date_time' => $date_time,
                        'order_date' => $CreatedAt,
                        'delivery_addr' => $delivery_addr_html,
                        'payment_method' => $methodTitle,
                        'extra_fee_charge' => $extra_fee_charge,
                        'billing_info' => $billing_info_html,
                        'delivery_info' => $delivery_info_html,
                        'subtotal' => $subtotal,
                        'tax_amount' => $tax_amount,
                        'delivery_charge' => $delivery_charge,
                        'ord_grandtotal' => $ord_grandtotal,
                        'message' => '',
                        'subject' => $subject,
                    ]
                )->setFrom(
                    [
                        'email' => 'sales@owenbrotherscatering.com',
                        'name' => 'Sales'
                    ]
                )->addTo(
                    $cust_email
                )->addBcc(
                    array('orders@owenbrotherscatering.com')
                )->addcc(
                    array('john@owenbrotherscatering.co.uk', 'sales@owenbrotherscatering.com')
                )->getTransport();
                $transport->sendMessage();
                $this->inlineTranslation->resume();

            }
        } catch (\Exception $e) {
            $clogger->err($e->getMessage());
        }
        $clogger->info("Email Generate Csv Finish");
    }
}