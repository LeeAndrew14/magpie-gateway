Changelogs: 
v1.0.2
• Update Magpie API endpoints from v1 to v1.1
• Add validation when total amount is less than minimum amount Magpie required which is 50 dollar cents
    - Current convertion of 50 dollar cents to php is 24.32 multplied by 100 is 2432
• Add magrin of 568, a total of 3000, which converts to 30 php 
    - Reason for this is not sure if convertion fluctiates
• Add validation when payment status is succeded, not only relying on capture status
• Change to total number format with two decimal places