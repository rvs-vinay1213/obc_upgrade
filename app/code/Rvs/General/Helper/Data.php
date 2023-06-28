<?php

namespace Rvs\General\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
	protected $_filesystem;

	public function __construct(
	    \Magento\Framework\Filesystem $_filesystem,
	    \Magento\Framework\App\Helper\Context $context
	)
	{
	    $this->_filesystem = $_filesystem;
	    parent::__construct($context);
	}

	public function getMediaUrl()
	{
		return $this->_filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath();
	}
}