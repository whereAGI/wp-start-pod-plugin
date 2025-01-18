# WP Start Pod Plugin

A WordPress plugin that integrates with WooCommerce to manage RunPod.io pods deployment and billing.

## Features

- Automatic pod deployment after WooCommerce payment confirmation
- Usage-based billing system
- Pod status monitoring
- Custom order statuses (Deployed, Paused, Terminated)
- Wallet integration for payments
- Automatic pod termination on low balance

## Requirements

- WordPress 5.0+
- WooCommerce 4.0+
- PHP 7.4+
- RunPod.io API access

## Installation

1. Download the plugin files
2. Upload to your WordPress plugins directory
3. Activate the plugin through WordPress admin
4. Configure your RunPod.io API key in the settings

## Configuration

1. Set up your RunPod.io API key
2. Configure WooCommerce products with appropriate mutation attributes
3. Set up the billing cron job

## Usage

1. Create products in WooCommerce with appropriate RunPod.io configurations
2. Users can purchase and deploy pods
3. Monitor pod status in the WooCommerce orders section
4. Automatic billing based on usage

## License

This project is licensed under the GPL v2 or later

## Credits

Created by [Your Name]