# TLN Launch Checklist

## Plugins to Upload (Re-download from GitHub)

| Plugin | File | What's Fixed |
|--------|------|--------------|
| Business Directory | `tln-directory.php` | Pulls from Google, filters, Waxhaw first |
| Golf Courses | `tln-golf-shortcode.php` | Green border, yellow stars, claim button |
| Business Dashboard | `tln-business-dashboard.php` | TOS with signature, claim pre-fill |
| Admin Dashboard | `tln-admin-dashboard.php` | Revenue tracker |

---

## Pages to Create in WordPress

| Page | Shortcode | Purpose |
|------|-----------|---------|
| Directory | `[tln_directory]` | Main business listing |
| Golf Courses | `[golf_directory]` | Golf page |
| Claim | `[claim_business]` | For businesses to claim |
| About | (copy from `tln-about.html`) | About page |
| Pricing/Upgrade Pro | (copy from `tln-sell-pro.html`) | Sales page |
| Sponsor | (copy from `tln-sell-sponsor.html`) | Sales page |

---

## Test Flow

1. Go to /directory → should show businesses from Google
2. Try filters (search, category, location, sort)
3. Click "Claim This Business" → goes to /claim with name filled
4. Fill form, agree to TOS, sign with name
5. Check admin → TLN Business → Claims

---

## What's Working Now

- ✅ Directory pulls live data from Google
- ✅ Filters: Search, Category, Location, Sort
- ✅ Waxhaw always first with special styling
- ✅ Claim form with pre-fill + TOS + signature
- ✅ Golf page with all 21 courses, filters, status pills
- ✅ Admin dashboard for revenue tracking

---

## What's NOT Done (Phase 2)

- Voucher system
- Classified ads
- Newsletter
- Postcard ads
- Payment processing

---

## Your First Revenue Goals

1. **First Pro ($99)** — just get one business to pay
2. **3 Pro ($297/mo)** — covers your costs
3. **10 Pro ($990/mo)** — real income starts

---

## What to Do RIGHT NOW

1. ✅ Re-download all 4 updated plugins
2. ✅ Upload to WordPress
3. ✅ Create /directory page with `[tln_directory]`
4. ✅ Create /claim page with `[claim_business]`
5. ✅ Test the full claim flow
6. ✅ Start selling Pro to 1 business

---

That's it. That's launch.
