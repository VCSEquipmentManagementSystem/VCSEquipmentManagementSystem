<?php
if (!function_exists('getStockStatus')) {
    function getStockStatus($stock_quantity) {
        // Define thresholds
        $out_of_stock_threshold = 0;
        $low_stock_threshold = 10;
        
        if ($stock_quantity <= $out_of_stock_threshold) {
            return [
                'text' => 'out of stock',
                'class' => 'bg-danger'
            ];
        } else if ($stock_quantity <= $low_stock_threshold) {
            return [
                'text' => 'low stock',
                'class' => 'bg-warning text-dark'
            ];
        } else {
            return [
                'text' => 'in stock',
                'class' => 'bg-success'
            ];
        }
    }
}
?>
