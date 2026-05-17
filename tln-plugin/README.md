# The Local NearBuy - WordPress Plugin

## Current Features
- Business directory with profiles (Free, Pro, Pro Plus tiers)
- Voucher/offer system with QR code redemption
- EDDM campaign management
- Admin dashboard with revenue tracking
- Claim form for offers

## Roadmap / Future Enhancements

| Status | Action | How |
| ------ | ---------------------------------- | ----------------------------------------------------------------------------------------------------------------------- |
| ✅ DONE | USPS EDDM zone picker UI | Zones table + management page with Suggest/Approve toggles |
| ✅ DONE | Campaign workflow tabs | Tabbed view: Sell → Artwork → Printing → Mailed → Scanning with progress bars |
| ⏳ TODO | Connect support tickets | If you use a ticket system (e.g., HelpScout, Zendesk), we can map those to the support_ticket post type for the widget. |
| ⏳ TODO | Fine‑tune styling | Adjust colors, responsive breakpoints, or add hover effects for the new cards. |
| ⏳ TODO | Deploy to production | When you're ready, flip the Stripe keys from test to live and the webhook will start capturing real payments. |

## Installation
1. Upload `tln-plugin.zip` via WordPress Plugins > Add New > Upload
2. Activate the plugin
3. Configure settings in WP Admin > TLN Dashboard

## Stripe Webhooks
Add this endpoint in your Stripe dashboard:
`https://yourdomain.com/wp-json/tln/v1/stripe-webhook`