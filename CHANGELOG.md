Changelogs: 
v1.0.2
• Update Magpie API from v1 to v1.1
• Add validation when total amount is less than minimum amount Magpie required which is .50 USD
    - Current conversion of .50 USD to PHP is ₱ 24.32 multiplied by 100 is 2432
• Add margin of 568, a total of 3000, which converts to ₱ 30.00
    - Reason for this is not sure if conversion fluctuates
• Add validation when payment status is succeeded, not only relying on capture status
• Change the total amount number format display with two decimal places (ex. ₱ 50000 > ₱ 500.00)