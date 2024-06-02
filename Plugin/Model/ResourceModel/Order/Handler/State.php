<?php
namespace Cenavice\Order\Plugin\Model\ResourceModel\Order\Handler;

use Magento\Sales\Model\Order;

class State
{
    public function beforeCheck(\Magento\Sales\Model\ResourceModel\Order\Handler\State $subject, $order)
    {
        $currentState = $order->getState();
        if ($currentState == Order::STATE_NEW && $order->getIsInProcess()) {
            // set custom order state and status for certain order
            $order->setState(Order::STATE_PROCESSING)
                ->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING));
            $currentState = Order::STATE_PROCESSING;
        }

        return [$order];
    }
}
