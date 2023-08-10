#!/bin/bash
shopt -s expand_aliases

SHOPWARE_CLI_PATH="/var/www/html/bin/console"
alias shpw5="php ${SHOPWARE_CLI_PATH}"

shpw5 sw:plugin:install Mond1SWR5
shpw5 sw:plugin:activate Mond1SWR5
shpw5 sw:cache:clear
shpw5 sw:plugin:config:set Mond1SWR5 mondu/credentials/api_token $BNPL_MERCHANT_API_TOKEN
echo $BNPL_MERCHANT_API_TOKEN
STATUS=`shpw5 sw:Mond1SWR5:validate`

if [[ $STATUS != "Credentials are valid. Successfully registered webhooks" ]]; then
    exit 5
fi

shpw5 sw:Mond1SWR5:activate:payment 
shpw5 sw:Mond1SWR5:activate:shipment:cost
