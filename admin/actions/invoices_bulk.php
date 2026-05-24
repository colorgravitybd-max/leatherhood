<?php
use LH\Core\Auth;
use LH\Core\Helpers;
use LH\Services\PdfInvoice;

Auth::gate('orders.read');
$ids = array_filter(array_map('intval', explode(',', (string)Helpers::input('ids', ''))));
if (!$ids) { http_response_code(422); echo 'No orders selected.'; exit; }
echo PdfInvoice::bulkHtml($ids);
