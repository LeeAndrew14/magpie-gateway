Changelogs:
v1.0.5 09-3-2020
• Add new column order_key in magpie_order_status table
• Add rewrite URL rule workaround for redirect URL
• Increase the minimum amount threshold from 3000 to 5000
• Fix return to cart if payment is cancelled
• Fix error message when insufficient fund occur

v1.0.4 09-2-2020
• Add 3d secure gateway, redirect and callback keys values pairs to magpie API
• Add wp_get_logger for every API request for error tracking
• Remove token only and auto charge options to minimize error
• Set capture to always true

v1.0.3 08-27-2020
• Proper handling when token only and auto charge are checked
• Set default card name as Guest when payment occurred in admin side with no customer details supplied
• Fix PHP notice in Magpie backend queries
• Fix return wc error notice immediately when error occurred
• Fix $currency_symbol typo

v1.0.2 08-24-2020
• Update Magpie API from v1 to v1.1
• Add validation when total amount is less than minimum amount Magpie required which is .50 USD
    - Current conversion of .50 USD to PHP is ₱ 24.32 multiplied by 100 is 2432
• Add margin of 568, a total of 3000, which converts to ₱ 30.00
    - Reason for this is not sure if conversion fluctuates
• Add validation when payment status is succeeded, not only relying on capture status
• Change the total amount number format display with two decimal places (ex. ₱ 50000 > ₱ 500.00)