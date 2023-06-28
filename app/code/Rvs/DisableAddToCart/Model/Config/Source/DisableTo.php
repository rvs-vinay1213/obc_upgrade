<?php
namespace Rvs\DisableAddToCart\Model\Config\Source;

class DisableTo implements \Magento\Framework\Option\ArrayInterface
{
 public function toOptionArray()
 {
  return [
    ['value' => '00', 'label' => __('12:00 AM')],
    ['value' => '01', 'label' => __('1:00 AM')],
    ['value' => '02', 'label' => __('2:00 AM')],
    ['value' => '03', 'label' => __('3:00 AM')],
	['value' => '04', 'label' => __('4:00 AM')],
	['value' => '05', 'label' => __('5:00 AM')],
	['value' => '06', 'label' => __('6:00 AM')],
	['value' => '07', 'label' => __('7:00 AM')],
	['value' => '08', 'label' => __('8:00 AM')],
	['value' => '09', 'label' => __('9:00 AM')],
	['value' => '10', 'label' => __('10:00 AM')],
	['value' => '11', 'label' => __('11:00 AM')],
	['value' => '12', 'label' => __('12:00 PM')],
	['value' => '13', 'label' => __('1:00 PM')],
	['value' => '14', 'label' => __('2:00 PM')],
	['value' => '15', 'label' => __('3:00 PM')],
	['value' => '16', 'label' => __('4:00 PM')],
	['value' => '17', 'label' => __('5:00 PM')],
	['value' => '18', 'label' => __('6:00 PM')],
	['value' => '19', 'label' => __('7:00 PM')],
	['value' => '20', 'label' => __('8:00 PM')],
	['value' => '21', 'label' => __('9:00 PM')],
	['value' => '22', 'label' => __('10:00 PM')],
	['value' => '23', 'label' => __('11:00 PM')]
  ];
 }
}
