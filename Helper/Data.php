<?php
namespace Cenavice\Order\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context
    ) {
        parent::__construct($context);
    }

    public function createBonusOrder($orderIncrementId)
    {
        
    }
}
