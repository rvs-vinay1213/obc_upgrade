<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 10/01/2019
 * Time: 9:41
 */

namespace Magenest\StripePayment\Controller\Adminhtml\Config;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Backend\App\Action;
use Magento\Framework\Filesystem\Driver\File;

class DownloadDebug extends \Magento\Backend\App\Action
{
    /**
     * @var DirectoryList
     */
    protected $directory_list;

    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $fileFactory;

    /**
     * @var File
     */
    protected $fileDriver;

    /**
     * DownloadDebug constructor.
     * @param Action\Context $context
     * @param DirectoryList $directory_list
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param File $fileDriver
     */
    public function __construct(
        Action\Context $context,
        DirectoryList $directory_list,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        File $fileDriver
    ) {
        $this->directory_list = $directory_list;
        $this->fileFactory = $fileFactory;
        $this->fileDriver = $fileDriver;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function execute()
    {
        $version = $this->getRequest()->getParam('version');
        $filename = "stripe_debugfile_".$version."_".time().".log";
        $file = $this->directory_list->getPath("var")."/log/stripe/debug.log";
        if ($this->fileDriver->isExists($file)) {
            return $this->fileFactory->create($filename, $this->fileDriver->fileGetContents($file), "tmp");
        } else {
            return $this->resultRedirectFactory->create()->setRefererOrBaseUrl();
        }
    }
}
