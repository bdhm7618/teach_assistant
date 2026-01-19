# Payment Module - Teachify

A comprehensive payment management system designed specifically for private lessons in Egypt, supporting multiple payment methods, invoices, installments, and discounts.

## 📋 Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Architecture](#architecture)
- [Database Design](#database-design)
- [Payment Methods](#payment-methods)
- [Usage Guide](#usage-guide)
- [API Endpoints](#api-endpoints)
- [Examples](#examples)

## 🎯 Overview

The Payment module provides a complete financial management solution for educational institutions offering private lessons. It supports:

- **Multiple Payment Methods**: Cash, bank transfers, mobile wallets (Vodafone Cash, Orange Money, Etisalat Cash, Easy Pay), and card payments
- **Invoice Management**: Generate and track invoices for students
- **Installment Plans**: Create flexible payment plans with multiple installments
- **Discount System**: Apply percentage or fixed discounts with usage limits
- **Financial Reports**: Track revenue, payment statistics, and student summaries
- **Channel Scoping**: Multi-tenant support with automatic channel isolation

## ✨ Features

### Core Features

1. **Payment Management**
   - Create, update, and track payments
   - Support for multiple payment methods
   - Payment status tracking (pending, completed, failed, refunded, cancelled)
   - Automatic invoice and installment updates
   - Link payments to payment periods (months, weeks, sessions)
   - Automatic period assignment based on payment date

2. **Invoice System**
   - Generate unique invoice numbers
   - Track total, paid, and remaining amounts
   - Automatic status updates (pending, paid, overdue, cancelled)
   - Link payments to invoices

3. **Installment Plans**
   - Create payment plans with multiple installments
   - Track due dates and payment status
   - Automatic installment status updates

4. **Discount System**
   - Percentage or fixed discounts
   - Usage limits and expiration dates
   - Minimum amount requirements
   - Maximum discount caps

5. **Payment Periods**
   - Monthly, weekly, daily, and session-based periods
   - Custom period creation
   - Period status management (open/closed)
   - Automatic period detection for payments
   - Period statistics and reporting

6. **Financial Reports**
   - Revenue statistics by date range
   - Payment statistics by method
   - Student payment summaries
   - Group payment tracking
   - Period-based financial reports

## 🏗️ Architecture

The module follows Clean Architecture principles:

```
modules/Payment/
├── app/
│   ├── Enums/
│   │   ├── PaymentStatus.php      # Payment status enum
│   │   ├── PaymentMethod.php       # Payment method enum
│   │   └── PaymentPeriodType.php   # Payment period type enum
│   ├── Models/
│   │   ├── Payment.php             # Payment model
│   │   ├── Invoice.php              # Invoice model
│   │   ├── Installment.php          # Installment model
│   │   ├── Discount.php             # Discount model
│   │   └── PaymentPeriod.php        # Payment period model
│   ├── Repositories/
│   │   ├── PaymentRepository.php
│   │   ├── InvoiceRepository.php
│   │   ├── InstallmentRepository.php
│   │   └── DiscountRepository.php
│   ├── Http/
│   │   ├── Controllers/V1/
│   │   │   ├── PaymentController.php
│   │   │   ├── InvoiceController.php
│   │   │   ├── DiscountController.php
│   │   │   └── PaymentPeriodController.php
│   │   ├── Requests/V1/
│   │   │   ├── PaymentRequest.php
│   │   │   ├── InvoiceRequest.php
│   │   │   ├── DiscountRequest.php
│   │   │   └── PaymentPeriodRequest.php
│   │   └── Resources/V1/
│   │       ├── PaymentResource.php
│   │       ├── InvoiceResource.php
│   │       ├── InstallmentResource.php
│   │       ├── DiscountResource.php
│   │       └── PaymentPeriodResource.php
│   └── Providers/
│       ├── PaymentServiceProvider.php
│       ├── RouteServiceProvider.php
│       └── EventServiceProvider.php
├── database/migrations/
│   ├── create_payments_table.php
│   ├── create_invoices_table.php
│   ├── create_installments_table.php
│   ├── create_discounts_table.php
│   └── create_payment_periods_table.php
└── resources/lang/
    ├── en/app.php
    └── ar/app.php
```

## 🗄️ Database Design

### Payments Table

Stores individual payment transactions:

- `student_id`: Student who made the payment
- `group_id`: Group the payment is for (optional)
- `invoice_id`: Related invoice (optional)
- `installment_id`: Related installment (optional)
- `amount`: Original amount
- `discount_amount`: Discount applied
- `final_amount`: Amount after discount
- `payment_date`: Date and time of payment
- `payment_method`: Payment method enum
- `status`: Payment status enum
- `reference_number`: Transaction reference
- `transaction_id`: External transaction ID
- `paid_by`: User who recorded the payment

### Invoices Table

Stores invoice information:

- `invoice_number`: Unique invoice number (auto-generated)
- `student_id`: Student the invoice is for
- `group_id`: Group the invoice is for (optional)
- `total_amount`: Total invoice amount
- `discount_amount`: Discount applied
- `final_amount`: Amount after discount
- `paid_amount`: Amount paid so far
- `remaining_amount`: Amount still due
- `due_date`: Payment due date
- `issue_date`: Invoice issue date
- `status`: Invoice status (pending, paid, overdue, cancelled)

### Installments Table

Stores installment plan information:

- `invoice_id`: Related invoice
- `installment_number`: Installment sequence number
- `amount`: Installment amount
- `due_date`: Installment due date
- `paid_date`: Date installment was paid
- `status`: Installment status (pending, paid, overdue, cancelled)

### Payment Periods Table

Stores payment period information:

- `name`: Period name (auto-generated or custom)
- `period_type`: Type of period (monthly, weekly, daily, session, custom)
- `start_date`: Period start date
- `end_date`: Period end date
- `month`: Month number (for monthly periods)
- `year`: Year (for monthly periods)
- `is_open`: Whether period is open for payments
- `is_active`: Whether period is active
- `notes`: Additional notes

### Discounts Table

Stores discount codes and promotions:

- `code`: Unique discount code
- `name`: Discount name
- `description`: Discount description
- `type`: Discount type (percentage or fixed)
- `value`: Discount value
- `min_amount`: Minimum amount required
- `max_discount`: Maximum discount cap
- `start_date`: Discount start date
- `end_date`: Discount end date
- `usage_limit`: Maximum usage count
- `used_count`: Current usage count
- `is_active`: Active status
- `applies_to`: Scope (all, groups, students)

## 💳 Payment Methods

The system supports the following payment methods commonly used in Egypt:

1. **Cash** (`cash`) - Cash payments
2. **Bank Transfer** (`bank_transfer`) - Bank wire transfers
3. **Vodafone Cash** (`vodafone_cash`) - Vodafone mobile wallet
4. **Orange Money** (`orange_money`) - Orange mobile wallet
5. **Etisalat Cash** (`etisalat_cash`) - Etisalat mobile wallet
6. **Easy Pay** (`easy_pay`) - Easy Pay mobile wallet
7. **Credit Card** (`credit_card`) - Credit card payments
8. **Debit Card** (`debit_card`) - Debit card payments
9. **Online** (`online`) - Online payment gateways
10. **Other** (`other`) - Other payment methods

## 📖 Usage Guide

### Creating a Payment Period

#### Monthly Period
```php
POST /api/v1/payment-periods/monthly
{
    "year": 2026,
    "month": 1
}
```

#### Weekly Period
```php
POST /api/v1/payment-periods/weekly
{
    "start_date": "2026-01-01",
    "end_date": "2026-01-07"
}
```

#### Custom Period
```php
POST /api/v1/payment-periods
{
    "name": "January 2026 - Math Group",
    "period_type": "custom",
    "start_date": "2026-01-01",
    "end_date": "2026-01-31",
    "notes": "Custom period for Math group payments"
}
```

### Creating a Payment

```php
POST /api/v1/payments
{
    "student_id": 1,
    "group_id": 1,
    "payment_period_id": 1,
    "amount": 500.00,
    "discount_amount": 50.00,
    "payment_method": "vodafone_cash",
    "reference_number": "VFC123456789",
    "notes": "Monthly payment for Math group"
}
```

**Note:** If `payment_period_id` is not provided, the system will automatically assign the current active period based on the payment date.

### Creating an Invoice

```php
POST /api/v1/invoices
{
    "student_id": 1,
    "group_id": 1,
    "total_amount": 1000.00,
    "discount_amount": 100.00,
    "due_date": "2026-02-15",
    "notes": "Monthly invoice for Math group"
}
```

### Creating an Invoice with Installments

```php
POST /api/v1/invoices/with-installments
{
    "student_id": 1,
    "group_id": 1,
    "total_amount": 3000.00,
    "discount_amount": 300.00,
    "due_date": "2026-04-15",
    "installments": [
        {
            "amount": 900.00,
            "due_date": "2026-02-15"
        },
        {
            "amount": 900.00,
            "due_date": "2026-03-15"
        },
        {
            "amount": 900.00,
            "due_date": "2026-04-15"
        }
    ]
}
```

### Applying a Discount

```php
POST /api/v1/discounts/apply
{
    "code": "SUMMER2026",
    "amount": 1000.00
}
```

### Getting Financial Statistics

```php
GET /api/v1/payments/statistics?start_date=2026-01-01&end_date=2026-01-31
```

## 🔌 API Endpoints

### Payments

- `GET /api/v1/payments` - List all payments
- `GET /api/v1/payments/{id}` - Get payment details
- `POST /api/v1/payments` - Create payment
- `PUT /api/v1/payments/{id}` - Update payment
- `DELETE /api/v1/payments/{id}` - Delete payment
- `POST /api/v1/payments/{id}/complete` - Mark payment as completed
- `POST /api/v1/payments/{id}/refund` - Refund payment
- `GET /api/v1/payments/student/{studentId}` - Get student payments
- `GET /api/v1/payments/group/{groupId}` - Get group payments
- `GET /api/v1/payments/statistics` - Get financial statistics
- `GET /api/v1/payments/student/{studentId}/summary` - Get student summary

### Invoices

- `GET /api/v1/invoices` - List all invoices
- `GET /api/v1/invoices/{id}` - Get invoice details
- `POST /api/v1/invoices` - Create invoice
- `POST /api/v1/invoices/with-installments` - Create invoice with installments
- `PUT /api/v1/invoices/{id}` - Update invoice
- `DELETE /api/v1/invoices/{id}` - Delete invoice
- `GET /api/v1/invoices/student/{studentId}` - Get student invoices
- `GET /api/v1/invoices/overdue` - Get overdue invoices
- `GET /api/v1/invoices/pending` - Get pending invoices

### Discounts

- `GET /api/v1/discounts` - List all discounts
- `GET /api/v1/discounts/{id}` - Get discount details
- `POST /api/v1/discounts` - Create discount
- `PUT /api/v1/discounts/{id}` - Update discount
- `DELETE /api/v1/discounts/{id}` - Delete discount
- `POST /api/v1/discounts/apply` - Apply discount code
- `GET /api/v1/discounts/active` - Get active discounts

### Payment Periods

- `GET /api/v1/payment-periods` - List all payment periods
- `GET /api/v1/payment-periods/{id}` - Get period details
- `POST /api/v1/payment-periods` - Create custom period
- `POST /api/v1/payment-periods/monthly` - Create monthly period
- `POST /api/v1/payment-periods/weekly` - Create weekly period
- `PUT /api/v1/payment-periods/{id}` - Update period
- `DELETE /api/v1/payment-periods/{id}` - Delete period
- `GET /api/v1/payment-periods/open` - Get open periods
- `GET /api/v1/payment-periods/current` - Get current active period
- `GET /api/v1/payment-periods/{id}/statistics` - Get period statistics

## 💡 Examples

### Example 1: Create Monthly Payment Period

```php
// Create January 2026 payment period
POST /api/v1/payment-periods/monthly
{
    "year": 2026,
    "month": 1
}

// Response:
{
    "id": 1,
    "name": "January 2026",
    "period_type": "monthly",
    "start_date": "2026-01-01",
    "end_date": "2026-01-31",
    "is_open": true
}
```

### Example 2: Student Monthly Payment

```php
// Create payment for student's monthly fee
// Payment period will be auto-assigned if not specified
POST /api/v1/payments
{
    "student_id": 1,
    "group_id": 5,
    "payment_period_id": 1,
    "amount": 500.00,
    "payment_method": "vodafone_cash",
    "reference_number": "VFC987654321",
    "status": "completed"
}
```

### Example 3: Get Period Statistics

```php
// Get statistics for a payment period
GET /api/v1/payment-periods/1/statistics

// Response:
{
    "period": {...},
    "total_payments": 25,
    "total_amount": 12500.00,
    "by_group": [
        {
            "group_id": 1,
            "total": 5000.00,
            "count": 10
        }
    ]
}
```

### Example 4: Invoice with 3 Installments

```php
// Create invoice with 3 monthly installments
POST /api/v1/invoices/with-installments
{
    "student_id": 1,
    "group_id": 5,
    "total_amount": 1500.00,
    "discount_amount": 150.00,
    "due_date": "2026-04-15",
    "installments": [
        {"amount": 450.00, "due_date": "2026-02-15"},
        {"amount": 450.00, "due_date": "2026-03-15"},
        {"amount": 450.00, "due_date": "2026-04-15"}
    ]
}
```

### Example 5: Apply Discount

```php
// Apply 20% discount code
POST /api/v1/discounts/apply
{
    "code": "WELCOME20",
    "amount": 1000.00
}

// Response:
{
    "success": true,
    "message": "Discount applied successfully.",
    "discount": {...},
    "discount_amount": 200.00,
    "final_amount": 800.00
}
```

## 🔒 Security & Validation

- All endpoints require authentication (`auth:user` middleware)
- Channel scoping ensures data isolation
- Validation rules ensure data integrity
- Reference numbers required for non-cash payments
- Discount codes validated for validity and usage limits

## 📊 Business Logic

### Payment Flow

1. Payment created with status `pending`
2. Payment can be marked as `completed` with transaction ID
3. Completed payments automatically update related invoices/installments
4. Completed payments can be refunded if needed

### Invoice Flow

1. Invoice created with status `pending`
2. Payments linked to invoice update `paid_amount` and `remaining_amount`
3. Invoice status automatically updates to `paid` when fully paid
4. Invoice status updates to `overdue` if past due date and not paid

### Installment Flow

1. Installments created with invoice
2. Payments linked to installments track progress
3. Installment status updates to `paid` when fully paid
4. Installment status updates to `overdue` if past due date

## 🚀 Future Enhancements

- Payment gateway integration (Paymob, Fawry, etc.)
- Automated invoice reminders
- Payment receipts generation
- Advanced financial reporting
- Multi-currency support
- Payment reconciliation

## 📝 Notes

- All amounts are stored with 2 decimal places
- Payment dates include time for accurate tracking
- Invoice numbers are auto-generated with format: `INV-{channelId}-{YYYYMM}-{sequence}`
- Discount codes are case-insensitive
- Channel scoping is automatically applied to all queries
- Payment periods can be created manually or automatically
- If payment_period_id is not specified, system auto-assigns current active period
- Monthly periods are automatically named based on month and year
- Weekly periods are automatically named based on date range
- Periods can be opened/closed to control payment acceptance
- Private lessons (Groups) are linked to payments for better tracking

