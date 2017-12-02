Commerce Moyasar

***********************************************************
* PLEASE NOTE THIS MODULE IS FOR DEVELOPMENT AND TESTING  *
* PURPOSES ONLY											  *
* DO NOT INSTALL IT ON PRODUCTION SITES                   *
* THERE IS SECURITY ISSUES THAT NEEDS TO BE ADDRESSED BY  *
* THE PAYMENT GATEWAY DEVELOPERS AND THE DEVELOPERS OF    *
* THIS MODULE                                             *
***********************************************************

###########################################################
# The credit card authorize and capture process is not
# clear, at least to me.
# Moyasar's CC authorize and capture has to be fully
# understood in order to finish coding this D8 commerce
# payment gateway
###########################################################

CONTENTS OF THIS FILE
---------------------
* Introduction
* Requirements
* Installation
* Configuration

INTRODUCTION
------------
This project integrates Moyasar online payments into
the Drupal Commerce payment and checkout systems.

REQUIREMENTS
------------
This module requires the following:
 - Drupal Commerce and Commerce payment.
 - Moyasar PHP Library (https://github.com/moyasar/moyasar-php);
 - Moyasar Merchant account (https://dashboard.moyasar.com/session/login).


INSTALLATION
------------
* This module needs to be installed via Composer, which will download
the required libraries.
composer require "drupal/commerce_moyasar"

CONFIGURATION
-------------
* Create new Moyasar payment gateway
  Administration > Commerce > Configuration > Payment gateways > Add payment gateway
  Provide the following settings:
  - Secret key.
  - Publishable key.




