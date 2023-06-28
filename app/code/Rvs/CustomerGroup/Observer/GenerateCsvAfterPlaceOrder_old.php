<?php

namespace Rvs\CustomerGroup\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class GenerateCsvAfterPlaceOrder implements ObserverInterface
{
	protected $orderRepository;

	public function __construct(
	    \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
	){
	    $this->orderRepository = $orderRepository;
	}

	public function execute(EventObserver $observer)
    {
    	// return $this->createCsv();
    	// die("DIED12");
        // $order = $observer->getEvent()->getOrderIds();
        // $orderData = $this->orderRepository->get($order[0]);
        // echo "<pre>"; print_r($orderData->getCustomer()); die("DIED");

        $array = array(array(1,2,3,4,5,6,7), array(1,2,3,4,5,6,7), array(1,2,3,4,5,6,7));

		$this->sendCsvMail($array, "Website Report \r\n \r\n Order CSV");
    }

    public function createCsv($data)
    {
    	$data = array('1', 'Test Company', 'Name', 'AC', 'mail@mail.com', '123465', 'invoice');

	    // Open temp file pointer
	    if (!$fp = fopen('php://temp', 'w+')) return FALSE;
	    
	    fputcsv($fp, array('ID', 'Company', 'Name', 'Company Account Number', 'Email', 'Phone Number', 'Invoice'));
	    
	    // Loop data and write to file pointer
	    // while ($line = mysql_fetch_assoc($data)) fputcsv($fp, $line);

	    fputcsv($fp, $data);
	    
	    // Place stream pointer at beginning
	    rewind($fp);

	    // Return the data
	    return stream_get_contents($fp);
    }

    public function sendCsvMail($csvData, $body, $to = 'samvens123@gmail.com', $subject = 'CSV Order Report', $from = 'noreply@samvens.com')
    {
	    // This will provide plenty adequate entropy
	    $multipartSep = '-----'.md5(time()).'-----';

	    // Arrays are much more readable
	    $headers = array(
	        "From: $from",
	        "Reply-To: $from",
	        "Content-Type: multipart/mixed; boundary='$multipartSep'"
	    );

	    // Make the attachment
	    $attachment = chunk_split(base64_encode($this->createCsv($csvData))); 

	    // Make the body of the message
	    $body = "--$multipartSep\r\n"
	        . "Content-Type: text/plain; charset=ISO-8859-1; format=flowed\r\n"
	        . "Content-Transfer-Encoding: 7bit\r\n"
	        . "\r\n"
	        . "$body\r\n"
	        . "--$multipartSep\r\n"
	        . "Content-Type: text/csv\r\n"
	        . "Content-Transfer-Encoding: base64\r\n"
	        . "Content-Disposition: attachment; filename='Website-Report-' . date("F-j-Y") . ".csv"\r\n"
	        . "\r\n"
	        . "$attachment\r\n"
	        . "--$multipartSep--";

	    // Send the email, return the result
	    return @mail($to, $subject, $body, implode("\r\n", $headers)); 
	}
}