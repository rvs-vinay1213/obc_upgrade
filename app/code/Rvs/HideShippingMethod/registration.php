<?php

\Magento\Framework\Component\ComponentRegistrar::register(
    \Magento\Framework\Component\ComponentRegistrar::MODULE,
    'Rvs_HideShippingMethod',
    isset($file) ? dirname($file) : __DIR__
);
