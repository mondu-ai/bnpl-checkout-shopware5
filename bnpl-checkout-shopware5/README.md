
# Mondu for Shopware 5

## Installation

- Download the latest plugin release zip file from [Releases](https://github.com/mondu-ai/bnpl-checkout-shopware5/releases)

- Upload the zip file in the shopware 5 plugin manager

## CLI commands

- sw:Mond1SWR5:validate

    Will try to validate with api_key config, if token valid, will add webhooks.

- sw:Mond1SWR5:activate:payment

    Will activate all payment methods which have "mondu" in their names.

- sw:Mond1SWR5:activate:shipment:cost

    Will activate payment methods that contain "mondu" in their names in first 20 shipping costs.