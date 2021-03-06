# Magento2DevReport

## Description
This module provides various console commands to generate a HTML report in order to help
Magento 2 developers to asset some implementations in their project. It scans all the modules
(but the Magento's ones) located in app/code.

## Features
* Generate a report listing all the implemented observers
* Generate a report listing all the implemented plugins
* Generate a report listing all the implemented preferences

## Installation
The most simple way is to use composer:
```
composer require enrico69/magento2-dev-report --dev
```
## Usage
Once installed and enabled, this extension add three commands in the Magento console:

```
dev:observer:report                      Generate a report of all user-created observers
dev:plugin:report                        Generate a report of all user-created plugins
dev:preference:report                    Generate a report of all user-created preferences
```
