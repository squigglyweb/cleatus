# TLN Stripe Setup Guide

## Overview
This document covers how to set up Stripe to accept payments for The Local NearBuy membership tiers.

---

## Step 1: Create Stripe Account

If you don't have Stripe yet:
1. Go to [stripe.com](https://stripe.com) and sign up
2. Complete account verification
3. Get your API keys from Dashboard → Developers → API Keys

---

## Step 2: Create Products

In your Stripe Dashboard:

### Create 3 Subscription Products:

1. **Pro Member** - $99/month
   - Products → Create Product → Name: "Pro Member"
   - Price: $99.00 USD → Recurring → Monthly
   - Save → Copy the **Price ID** (starts with `price_`)

2. **Pro+ Member** - $199/month
   - Products → Create Product → Name: "Pro+ Member"  
   - Price: $199.00 USD → Recurring → Monthly
   - Save → Copy the **Price ID**

3. **Sponsor Member** - $349/month
   - Products → Create Product → Name: "Sponsor Member"
   - Price: $349.00 USD → Recurring → Monthly
   - Save → Copy the **Price ID**

---

## Step 3: Get Your Keys

### Publishable Key (for the frontend)
- Dashboard → Developers → API Keys
- Copy `pk_live_...` (live mode)

### Secret Key (for the backend)
- Same page, reveal secret key
- Copy `sk_live_...` (live mode)

### Webhook Secret
- Dashboard → Developers → Webhooks
- Add endpoint: `https://thelocalnearbuy.com/wp-json/tln/v1/stripe-webhook`
- Select events: `checkout.session.completed`, `customer.subscription.created`, `customer.subscription.updated`, `customer.subscription.deleted`, `invoice.payment_succeeded`, `invoice.payment_failed`
- Copy the **Signing secret** (`whsec_...`)

---

## Step 4: Add Keys to WordPress

Go to your WordPress admin → TLN Settings (or wp-admin/admin.php?page=tln-settings)

Add these:
- **Stripe Publishable Key**: `pk_live_...`
- **Stripe Secret Key**: `sk_live_...`
- **Stripe Webhook Secret**: `whsec_...`

---

## Step 5: Update the Checkout Page

Edit `/home/openclaw/.openclaw/workspace/tln-upgrade.html` and replace:

```javascript
// Line ~95
const STRIPE_PUBLISHABLE_KEY = 'pk_live_YOUR_STRIPE_PUBLISHABLE_KEY';

// Line ~102-104
const PLANS = {
    pro: { priceId: 'price_YOUR_PRO_PRICE_ID', name: 'Pro Member' },
    pro_plus: { priceId: 'price_YOUR_PRO_PLUS_PRICE_ID', name: 'Pro+ Member' },
    sponsor: { priceId: 'price_YOUR_SPONSOR_PRICE_ID', name: 'Sponsor Member' }
};
```

With your actual Stripe values.

---

## Step 6: Alternative - Use Checkout Links (Easier!)

Instead of the complex integration, you can use Stripe Checkout Links:

1. In Stripe Dashboard → Products → Each Product
2. Click "Create Checkout Link"
3. Copy the URL
4. Replace the buttons in `tln-upgrade.html` with simple links:

```html
<a href="https://buy.stripe.com/your_checkout_link" class="cta-button">Get Started</a>
```

This method requires NO code changes and works immediately.

---

## Testing

Use Stripe Test Mode:
- Toggle to "Test Mode" in Dashboard
- Use test card: 4242 4242 4242 4242, any future date, any CVC
- Get test keys (starts with `pk_test_` / `sk_test_`)

---

## Need Help?

- Stripe Docs: https://stripe.com/docs
- TLN Support: https://thelocalnearbuy.com/contact