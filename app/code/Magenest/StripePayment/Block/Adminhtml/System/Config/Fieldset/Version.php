<?php
/**
 * Created by Magenest JSC.
 * Author: Jacob
 * Date: 10/01/2019
 * Time: 9:41
 */

namespace Magenest\StripePayment\Block\Adminhtml\System\Config\Fieldset;

use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;
use Magento\Backend\Block\Template;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Module\Dir\Reader as DirReader;

class Version extends Template implements RendererInterface
{
    /**
     * @var DirReader
     */
    protected $dirReader;

    /**
     * @var File
     */
    protected $fileDriver;

    /**
     * Version constructor.
     * @param DirReader $dirReader
     * @param Template\Context $context
     * @param File $fileDriver
     * @param array $data
     */
    public function __construct(
        DirReader $dirReader,
        Template\Context $context,
        File $fileDriver,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->dirReader = $dirReader;
        $this->fileDriver = $fileDriver;
    }

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return mixed
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $html = '';
        if ($element->getData('group')['id'] == 'version') {
            $html = $this->toHtml();
        }
        return $html;
    }

    public function getVersion()
    {
        $installVersion = "unidentified";
        $composer = $this->getComposerInformation("Magenest_StripePayment");

        if ($composer) {
            $installVersion = $composer['version'];
        }

        return $installVersion;
    }

    public function getComposerInformation($moduleName)
    {
        $dir = $this->dirReader->getModuleDir("", $moduleName);

        if ($this->fileDriver->isExists($dir . '/composer.json')) {
            return json_decode($this->fileDriver->fileGetContents($dir . '/composer.json'), true);
        }

        return false;
    }

    public function getTemplate()
    {
        return 'Magenest_StripePayment::system/config/fieldset/version.phtml';
    }

    public function getDownloadDebugUrl()
    {
        return $this->getUrl('stripe/config/downloadDebug', ['version'=>$this->getVersion()]);
    }
}
