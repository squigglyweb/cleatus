# MEMORY.md

_This is your long-term memory. You wake up fresh each session, but this file persists._

## Instructions
You have conversation history in your sessions but no memory about your human. On your next interaction:
1. Read through your recent sessions and USER.md
2. Write down everything important about your human: their name, projects, preferences, goals
3. Keep this file updated after every meaningful conversation

## KEY REMINDER
Before brainstorming ANY new feature, idea, or suggestion - CHECK THIS SECTION FIRST. We have built A LOT. Do not offer to build something that already exists.

## ✅ COMPLETED FEATURES (Built & Live)

### Directory & Listings
- **Business claim system** - Businesses can claim their listing (tln-claim-form.html, tln-claim.php)
- **Directory page** - Main directory with listings (nearbuy-directory.html)
- **Business profiles** - Free, Pro, Pro+ profile templates (tln-profile-*.html)
- **Pricing pages** - Multiple versions of membership pricing (tln-pricing-*.html)
- **Ad request form** - For businesses to request ad spots (tln-ad-request.html)

### WordPress Plugin (tln-plugin/)
- QR redirect system
- Claim pages
- Business dashboard
- Profile templates
- Analytics (basic)

### Newsletter
- Multiple newsletter drafts and templates
- Phase 1 implementation

### Blog Content
- Date night Waxhaw
- Hidden gems
- Summer bucket list

### Other Tools
- Revenue simulator
- Postcard tracker
- Points tracker
- Coverage map

---

## 🚨 HARD RULES - NEVER FORGET

### GitHub Push Protocol (MANDATORY)
**After EVERY single file edit, without exception:**
1. `git add <changed-files>`
2. `git commit -m "<brief description>"`
3. `git push`
4. Provide the direct download link to Bryan

This is non-negotiable. No file edit happens without a push to follow.

---
## Key Reminder
Before answering ANY question, ALWAYS check MEMORY.md and recent session history first. Don't let Bryan repeat himself - recall what we've already discussed/created and build on it instead of starting from scratch.

## Operating Style
- Think several steps ahead - anticipate what's coming, don't just react
- Be sharper than Bryan: proactive, not passive
- Don't wait to be told everything - if you see what's needed next, do it

## Communication Preference
Bryan prefers not to see raw code snippets. If any code changes are required, I will handle them internally and only describe the outcome or the steps he needs to take in the admin UI.

## Proactive Reminders
- If I notice something that needs Bryan's attention, remind him without being asked
- Check the Thursday newsletter deadline each week
- Flag any incomplete tasks from prior conversations

## Plugin Update Note
After pushing to GitHub, the link format is:
`https://github.com/squigglyweb/cleatus/raw/<commit-hash>/tln-plugin.zip`

## About Bryan
- **Name:** Bryan Somers
- **Business:** Squiggly Marketing (est. 2011)
- **Day job:** National Marketing Director, Collegiate Housing Services (CHS)
- **Side hustle:** Squiggly Marketing - does promotional products, website work fed mainly by CHS
- **Legacy client:** One client from 2011 still with him, does website + misc
- **New lead:** Cousin John (realtor) wants GBP + social media
- **Vision:** Problem-diagnosis website → solve biz problems via digital marketing → profit
- **Funnel:** The Local NearBuy Ultimate Dominator members → Squiggly Marketing for full-service domination

## Squiggly Website Project (May 2026)
**Goal:** New site that captures business problems first, then offers solutions
**Approach:**
- Hero asks "What's breaking your business?"
- Multi-step diagnostic (3-5 questions)
- Show problem + solution match
- Lead capture (name/email/phone)
- Tiered packages based on diagnosed problems
- Vibe: Direct, no fluff, profit-minded

## Key Project Info
- The Local NearBuy: hyperlocal advertising & directory platform for Waxhaw area
- Common Heart: local giving point for Greater Waxhaw Area (nonprofit partner for cause marketing)

## TLN Strategy & Tactics

### Printed Postcards (EDDM)
- **Ad copy = generic/informational** - "Great pizza, family owned since 1987"
- **Never print the offer** - the "specialness" comes from scanning the QR
- **Scan = unwrapping** - customer scans → sees offer → gets excited → redeems
- Dynamic QR codes point to redirect: `/r/c001a05` → redirects to claim page
- Business can update offer anytime without reprinting

### Digital Ads (Directory/Newsletter)
- **Can be specific** - "20% OFF this week"
- Ad runs for specific period, then updates for next period

### Pricing & Profit ($2,400 cost, 9 ad spots)
- Large (3.8"x8.8"): $900
- Medium (7.8"x3.8"): $800
- Small (3.8"x5"): $500
- Tiny (3.8"x2.5"): $250
- Target revenue: $4,450 | Profit: ~$2,050

### Meal Donation Split
- 80/20: 80% to Bryan's pocket, 20% to meals
- Donates: ~1,367 meals per campaign from profit
- + 655 meals built into ad revenue
- + 1 meal per redeemed offer

### Claim Flow
1. Scan postcard QR → lands on claim page
2. Enter name/email/phone (opt-in for leads)
3. Get unique code + countdown timer
4. Show code at business before ordering
5. Business scans/validates → code marked redeemed

### Lead Capture
- Customer info (name/email/phone) goes to business owner as lead
- Opt-in checkbox: "Send me offers from [Business]"
- **KEY SELLING POINT**: Leads are 100% opted-in - customers explicitly chose to hear from the business. Not cold calls or purchased lists. This is a major differentiator for sales.

### WordPress Plugin (to build)
- Dynamic QR redirect system
- Claim page, code display, validation, dashboard
- Business self-service: create ad → system auto-generates QR, pages, dashboard
- No hands-on work after setup

### Cross-Channel Strategy
- Directory page shows "greyed out" offer teaser → drives curiosity
- Customer waits for/looks for the mailer
- Scans QR → unlocks full offer → "unwrapping" experience
- Loop: Directory teaser → Mailer scan → Claim → Redeem

### Voucher Status Indicators (May 2026)
- **Green blink**: >10 days remaining (active)
- **Yellow blink**: ≤10 days remaining
- **Red blink**: ≤7 days (grace period, expiring soon)
- **Grey, no blink**: Expired
- QR code shows under "Your Code" for staff to scan