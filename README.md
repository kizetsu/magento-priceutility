# magento-priceutility
Priceutility module for magento 0.1.0

Module to get tierprices of Bundle product children and configurable product children.

If a customer has i.e. product A with taste 1 and with taste 2 in his cart, each 5 pieces. So the customer has total 10 pieces of product A in his cart.
Magento can't handle this because it looks only at the simple products. This module gets magento to look wich items have the same parent.

The Priceutility module also can looks the configurable product children have the same options.
I.e. you sell a product in different sizes but want to have all options at one configurable product. So far so good but you don't want to sell all sizes for the same price.
The module let you define specific options and use them for comparision.
Example:
	Shisha Tobacco
	- Tastes: {
		Cheesecake
		Apple
		Strawberry
		Cuban Cigar
	}
	- Sizes: {
		50g: {
			1   piece  = 7 €
			10  pieces = 5 €
			100 pieces = 3 €
		}
		200g: {
			1   piece  = 14 €
			10  pieces = 12 €
			100 pieces = 10 €
		}
		500g: {
			1   piece  = 30 €
			10  pieces = 28 €
			100 pieces = 26 €
		}
	}

	Shopping Cart (without Priceutility):
		5x Shisha Tobacco, 50g, Apple 			- 35 $
		2x Shisha Tobacco, 500g, Cheesecake 	- 60 $
		7x Shisha Tobacco, 50g, Cuban Cigar 	- 49 $

	Shopping Cart (with Priceutility):
		5x Shisha Tobacco, 50g, Apple 			- 25 $
		2x Shisha Tobacco, 500g, Cheesecake 	- 60 $
		7x Shisha Tobacco, 50g, Cuban Cigar 	- 35 $

To change the options, just modify the array at Kizetsu_Priceutility_Helper::getOptionAttributes().
In this Array you have to define the option label, that the module should look for.
The standard value is "Size".