-- Reset invoice numbers
UPDATE ps_order_invoice oi
JOIN ps_orders o ON o.id_order = oi.id_order
SET oi.number = o.invoice_number
WHERE oi.number = 0 AND o.invoice_number != 0;

-- Clear temporarly invoice #1
UPDATE ps_order_invoice oi
SET oi.id_order = 0, oi.number = 0
WHERE oi.id_order_invoice = 1;

-- Link payments to correct invoice
UPDATE ps_order_invoice_payment oip
JOIN ps_order_invoice oi ON oi.id_order = oip.id_order
SET oip.id_order_invoice = oi.id_order_invoice;

-- Reset invoice #1 order (looking at payments)
UPDATE ps_order_invoice oi
JOIN ps_order_invoice_payment oip ON oip.id_order_invoice = oi.id_order_invoice
JOIN ps_orders o ON o.id_order = oip.id_order
SET oi.id_order = o.id_order, oi.number = o.invoice_number
WHERE oi.id_order_invoice = 1;

-- Reset invoice amounts
UPDATE ps_order_invoice oi
JOIN ps_orders o ON o.id_order = oi.id_order
SET
	o.invoice_date = oi.date_add,
	oi.total_products = o.total_products,
	oi.total_products_wt = o.total_products_wt,
	oi.total_discount_tax_excl = o.total_discounts_tax_excl,
	oi.total_discount_tax_incl = o.total_discounts_tax_incl,
	oi.total_shipping_tax_excl = o.total_shipping_tax_excl,
	oi.total_shipping_tax_incl = o.total_shipping_tax_incl,
	oi.total_wrapping_tax_excl = o.total_wrapping_tax_excl,
	oi.total_wrapping_tax_incl = o.total_wrapping_tax_incl,
	oi.total_paid_tax_excl = o.total_paid_tax_excl,
	oi.total_paid_tax_incl = o.total_paid_tax_incl;

-- Link products to correct invoice
UPDATE ps_order_detail od
JOIN ps_order_invoice oi ON oi.id_order = od.id_order
SET od.id_order_invoice = oi.id_order_invoice;
