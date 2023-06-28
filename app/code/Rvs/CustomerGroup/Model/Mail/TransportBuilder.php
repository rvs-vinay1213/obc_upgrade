<?php namespace Rvs\CustomerGroup\Model\Mail;

class TransportBuilder extends \Magento\Framework\Mail\Template\TransportBuilder
{
    /**
     * @param Api\AttachmentInterface $attachment
     */
/*public function addAttachment($body,$filename) {
        $this->message->createAttachment($body, \Zend_Mime::TYPE_OCTETSTREAM, \Zend_Mime::DISPOSITION_ATTACHMENT, \Zend_Mime::ENCODING_BASE64, $filename);
        return $this;
    }*/
	  /*public function addAttachment($body,$filename)
		{
					$attachment = new \Zend\Mime\Part($body);
					$attachment->type = \Zend_Mime::TYPE_OCTETSTREAM;
					$attachment->disposition = \Zend_Mime::DISPOSITION_ATTACHMENT;
					$attachment->encoding = \Zend_Mime::ENCODING_BASE64;
					$attachment->filename = $filename;
					return $attachment;
		}*/
	
	public function addAttachment($pdfString,$filename)
    {
       $attachment = new \Zend\Mime\Part($pdfString);
            $attachment->type = \Zend_Mime::TYPE_OCTETSTREAM;
            $attachment->disposition = \Zend_Mime::DISPOSITION_ATTACHMENT;
            $attachment->encoding = \Zend_Mime::ENCODING_BASE64;
            $attachment->filename = $filename;
        return $attachment;
}
	}