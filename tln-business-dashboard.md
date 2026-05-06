# TLN Business Dashboard & Claim System

## Flow Overview

1. Visitor sees business in directory (free)
2. Visitor clicks "Claim This Business"
3. Business owner submits claim request
4. Bryan approves (or auto-approve)
5. Business gets dashboard access
6. Business connects Google Business Profile (optional)
7. Business adds custom content

---

## Claim Form (Front-End)

Fields:
- Business name (pre-filled from directory)
- Your name
- Email (will be their login)
- Phone
- Proof of ownership (checkbox + upload optional)

Shortcode: `[claim_business]`

---

## Business Dashboard (After Claim)

### Tab 1: Business Info
- Business name (editable)
- Description (textarea)
- Category selection

### Tab 2: Photos & Media
- Upload own photos (not Google)
- Add YouTube/Vimeo video link

### Tab 3: Offers
- Offer title (e.g., "10% off for neighbors")
- Offer description
- Offer code
- Start date / End date (or evergreen)
- Max uses (optional)

### Tab 4: Community Impact
- Meals donated counter (manual entry or auto-calculated)
- Display toggle: "Show our impact"

### Tab 5: Social Media
- Instagram URL
- Facebook URL
- TikTok URL
- Twitter URL

### Tab 6: Google Sync (Optional)
- Connect Google Business Profile button
- Toggle: Auto-sync posts/photos
- View Google reviews

---

## Pricing Integration

- Free: Basic listing only
- $99/mo Pro: Full dashboard + offer
- $199/mo Pro+: Dashboard + offer + loyalty program

---

## Implementation Options

### Option A: Gravity Forms + User Registration
- Gravity Forms for claim submission
- WordPress user registration for business login
- ACF for custom fields

### Option B: Custom Plugin (Recommended)
- Single plugin handles everything
- Cleaner, more control
- Can integrate with existing business post type

---

## Key Shortcodes

- `[claim_business]` - Claim form
- `[business_dashboard]` - Business owner dashboard (logged in only)
- `[business_offer_form]` - Add/edit offer

---

## Database Tables (if custom plugin)

```
wp_tln_claims
- id
- business_id (post ID)
- user_id
- status (pending/approved/rejected)
- submitted_at

wp_tln_business_meta
- business_id
- meals_donated
- custom_description
- video_url
- social_links (JSON)

wp_tln_offers
- id
- business_id
- title
- description
- code
- start_date
- end_date
- max_uses
- uses_count
```