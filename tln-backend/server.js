const express = require('express');
const sqlite3 = require('sqlite3').verbose();
const QRCode = require('qrcode');
const nodemailer = require('nodemailer');
const crypto = require('crypto');
const path = require('path');

const app = express();
const PORT = process.env.PORT || 3000;

// Database setup
const db = new sqlite3.Database('./offers.db');

db.serialize(() => {
  db.run(`
    CREATE TABLE IF NOT EXISTS offers (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      business_name TEXT,
      offer_title TEXT,
      offer_description TEXT,
      offer_type TEXT,
      offer_limit INTEGER,
      offer_expiration TEXT,
      meals_per_redemption INTEGER,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
  `);

  db.run(`
    CREATE TABLE IF NOT EXISTS claims (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      offer_id INTEGER,
      customer_name TEXT,
      customer_email TEXT,
      unique_code TEXT UNIQUE,
      redeemed BOOLEAN DEFAULT 0,
      redeemed_at DATETIME,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY(offer_id) REFERENCES offers(id)
    )
  `);
});

app.use(express.json());
app.use(express.static('public'));

// API: Create a new offer
app.post('/api/offers', (req, res) => {
  const { business_name, offer_title, offer_description, offer_type, offer_limit, offer_expiration, meals_per_redemption } = req.body;
  
  db.run(`
    INSERT INTO offers (business_name, offer_title, offer_description, offer_type, offer_limit, offer_expiration, meals_per_redemption)
    VALUES (?, ?, ?, ?, ?, ?, ?)
  `, [business_name, offer_title, offer_description, offer_type, offer_limit, offer_expiration, meals_per_redemption], function(err) {
    if (err) return res.status(500).json({ error: err.message });
    res.json({ success: true, offer_id: this.lastID });
  });
});

// API: Get all offers (for directory)
app.get('/api/offers', (req, res) => {
  db.all('SELECT * FROM offers ORDER BY created_at DESC', [], (err, rows) => {
    if (err) return res.status(500).json({ error: err.message });
    res.json(rows);
  });
});

// API: Claim an offer
app.post('/api/claim', async (req, res) => {
  const { offer_id, customer_name, customer_email } = req.body;
  
  // Generate unique code
  const unique_code = crypto.randomBytes(4).toString('hex').toUpperCase();
  
  db.run(`
    INSERT INTO claims (offer_id, customer_name, customer_email, unique_code)
    VALUES (?, ?, ?, ?)
  `, [offer_id, customer_name, customer_email, unique_code], async function(err) {
    if (err) return res.status(500).json({ error: err.message });
    
    // Generate QR code as data URL
    const qrDataUrl = await QRCode.toDataURL(`https://yourdomain.com/validate?code=${unique_code}`);
    
    // TODO: Send email with QR code (configure SMTP below)
    // For now, return QR code directly
    res.json({ 
      success: true, 
      unique_code,
      qr_code: qrDataUrl,
      message: 'Offer claimed! Show this QR code at the business.'
    });
  });
});

// API: Validate a code (for business to check)
app.get('/api/validate/:code', (req, res) => {
  const { code } = req.params;
  
  db.get(`
    SELECT c.*, o.offer_title, o.offer_description, o.offer_type, o.offer_limit, o.offer_expiration, o.meals_per_redemption, o.business_name
    FROM claims c
    JOIN offers o ON c.offer_id = o.id
    WHERE c.unique_code = ?
  `, [code], async (err, row) => {
    if (err) return res.status(500).json({ error: err.message });
    if (!row) return res.json({ valid: false, message: 'Code not found' });
    
    if (row.redeemed) {
      return res.json({ valid: false, message: 'Already redeemed', redeemed_at: row.redeemed_at });
    }
    
    // Check expiration
    if (row.offer_expiration) {
      const expDate = new Date(row.offer_expiration);
      if (expDate < new Date()) {
        return res.json({ valid: false, message: 'Offer expired' });
      }
    }
    
    res.json({
      valid: true,
      customer_name: row.customer_name,
      offer_title: row.offer_title,
      offer_description: row.offer_description,
      offer_type: row.offer_type,
      meals_per_redemption: row.meals_per_redemption,
      business_name: row.business_name
    });
  });
});

// API: Redeem a code (business marks as used)
app.post('/api/redeem', (req, res) => {
  const { code } = req.body;
  
  db.run(`
    UPDATE claims SET redeemed = 1, redeemed_at = datetime('now')
    WHERE unique_code = ? AND redeemed = 0
  `, [code], function(err) {
    if (err) return res.status(500).json({ error: err.message });
    if (this.changes === 0) return res.json({ success: false, message: 'Already redeemed or invalid' });
    res.json({ success: true, message: 'Offer redeemed! Thank you for supporting local.' });
  });
});

// Serve static frontend
app.get('/validate', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'validate.html'));
});

app.get('/claim/:offer_id', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'claim.html'));
});

app.listen(PORT, () => {
  console.log(`TLN Offer System running on port ${PORT}`);
});

// API: Dashboard stats
app.get('/api/dashboard', (req, res) => {
  db.all(`
    SELECT 
      o.id, o.business_name, o.offer_title, o.offer_type, o.offer_expiration, o.meals_per_redemption,
      (SELECT COUNT(*) FROM claims c WHERE c.offer_id = o.id) as claims_count,
      (SELECT COUNT(*) FROM claims c WHERE c.offer_id = o.id AND c.redeemed = 1) as redeemed_count
    FROM offers o
    ORDER BY o.created_at DESC
  `, [], (err, offers) => {
    if (err) return res.status(500).json({ error: err.message });
    
    const stats = {
      total_offers: offers.length,
      total_claims: offers.reduce((sum, o) => sum + o.claims_count, 0),
      total_redemptions: offers.reduce((sum, o) => sum + o.redeemed_count, 0),
      total_meals: offers.reduce((sum, o) => sum + o.redeemed_count, 0) // 1 redemption = 1 meal
    };
    
    // Add active status
    offers = offers.map(o => ({
      ...o,
      is_active: !o.offer_expiration || new Date(o.offer_expiration) > new Date()
    }));
    
    res.json({ stats, offers });
  });
});

// Serve admin
app.get('/admin', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'admin.html'));
});

// API: Generate report
app.get('/api/report', (req, res) => {
  const { month, year } = req.query;
  
  db.all(`
    SELECT 
      o.business_name, o.offer_title, o.meals_per_redemption,
      (SELECT COUNT(*) FROM claims c WHERE c.offer_id = o.id) as claims_count,
      (SELECT COUNT(*) FROM claims c WHERE c.offer_id = o.id AND c.redeemed = 1) as redeemed_count
    FROM offers o
  `, [], (err, offers) => {
    const totalMeals = offers.reduce((sum, o) => sum + o.redeemed_count, 0);
    const totalRedemptions = offers.reduce((sum, o) => sum + o.redeemed_count, 0);
    const totalClaims = offers.reduce((sum, o) => sum + o.claims_count, 0);
    
    res.json({
      report_month: `${month}/${year}`,
      total_claims: totalClaims,
      total_redemptions: totalRedemptions,
      total_meals_donated: totalMeals,
      offers: offers
    });
  });
});
