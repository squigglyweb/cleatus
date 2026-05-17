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
| ✅ DONE | Fine‑tune styling | Workflow tabs, progress bars, cards, badges, QR box, responsive design |
| ✅ DONE | Deploy to production | Settings page with test/live mode toggle, webhook config |
| ⏳ TODO | Connect support tickets | If you use a ticket system (e.g., HelpScout, Zendesk), we can map those to the support_ticket post type for the widget. |

## Installation
1. Upload `tln-plugin.zip` via WordPress Plugins > Add New > Upload
2. Activate the plugin
3. Configure settings in WP Admin > TLN Dashboard

## Stripe Webhooks
Add this endpoint in your Stripe dashboard:
`https://yourdomain.com/wp-json/tln/v1/stripe-webhook`