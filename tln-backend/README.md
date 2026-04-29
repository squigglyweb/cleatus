# TLN Offer System

Backend for The Local NearBuy offer claiming and validation.

## Files

- `server.js` - Main API server
- `public/claim.html` - Customer claim form
- `public/validate.html` - Business validation page

## Setup

1. Install Node.js
2. Run: `npm install`
3. Start: `npm start`

## Deploy to Render (Free)

1. Push code to GitHub
2. Go to render.com → New Web Service
3. Connect your GitHub repo
4. Build command: `npm install`
5. Start command: `npm start`

## API Endpoints

- `POST /api/offers` - Create offer
- `GET /api/offers` - List offers
- `POST /api/claim` - Claim offer (name, email, offer_id)
- `GET /api/validate/:code` - Check if valid
- `POST /api/redeem` - Mark as redeemed

## Configuration

Edit `server.js` to configure:
- SMTP for email (nodemailer)
- Base URL for QR codes
