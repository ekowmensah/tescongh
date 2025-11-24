# API Directory

This directory contains API integration files and examples for the TESCON Ghana Membership Database system.

## Files

### `hubtel_sms_examples.php`

Comprehensive examples demonstrating all Hubtel SMS API features.

**Usage:**

```bash
# Run from command line
php api/hubtel_sms_examples.php

# Or access via web browser
http://localhost/tescongh/api/hubtel_sms_examples.php
```

**Contains:**
- 11 practical examples covering all API methods
- Code snippets ready to copy and use
- Validation examples
- Cost estimation examples
- Error handling patterns
- Database integration examples

**Note:** All sending examples are commented out by default to prevent accidental SMS sending. Uncomment the lines you want to test.

## Related Documentation

- **Complete Guide:** `../HUBTEL_SMS_INTEGRATION.md`
- **Quick Start:** `../HUBTEL_SMS_QUICK_START.md`
- **Implementation Summary:** `../HUBTEL_SMS_IMPLEMENTATION_SUMMARY.md`

## API Classes

The main API classes are located in the `classes/` directory:

- `classes/HubtelSMS.php` - Main Hubtel SMS API wrapper
- `classes/SMSClient.php` - SMS client interface and factory
- `classes/SMSTemplateRenderer.php` - Template rendering

## Web Interfaces

- `sms.php` - Send SMS interface
- `sms_status_check.php` - Check message status
- `sms_logs.php` - View SMS logs

## Support

For questions or issues:
- Check the documentation files listed above
- Review the examples in this directory
- Contact system administrator
