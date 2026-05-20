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
      customer_phone TEXT,
      unique_code TEXT UNIQUE,
      claimed_at DATETIME,
      redeemed BOOLEAN DEFAULT 0,
      redeemed_at DATETIME,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY(offer_id) REFERENCES offers(id)
    )
  `);

  // Business directory tables
  db.run(`
    CREATE TABLE IF NOT EXISTS businesses (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      name TEXT NOT NULL,
      slug TEXT UNIQUE,
      address TEXT,
      city TEXT,
      state TEXT,
      zip TEXT,
      phone TEXT,
      email TEXT,
      website TEXT,
      description TEXT,
      category TEXT,
      subcategory TEXT,
      logo_url TEXT,
      hero_url TEXT,
      photos TEXT,
      hours TEXT,
      tier TEXT DEFAULT 'free',
      claimed BOOLEAN DEFAULT 0,
      owner_id INTEGER,
      rating REAL DEFAULT 0,
      review_count INTEGER DEFAULT 0,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
  `);

  db.run(`
    CREATE TABLE IF NOT EXISTS business_claims (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      business_id INTEGER,
      user_name TEXT,
      user_email TEXT,
      user_phone TEXT,
      tier TEXT,
      status TEXT DEFAULT 'pending',
      notes TEXT,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY(business_id) REFERENCES businesses(id)
    )
  `);

  db.run(`
    CREATE TABLE IF NOT EXISTS business_users (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      email TEXT UNIQUE,
      name TEXT,
      business_id INTEGER,
      role TEXT DEFAULT 'owner',
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY(business_id) REFERENCES businesses(id)
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
  const { offer_id, customer_name, customer_email, customer_phone } = req.body;
  
  // Generate unique code
  const unique_code = crypto.randomBytes(4).toString('hex').toUpperCase();
  const claimed_at = new Date().toISOString();
  
  db.run(`
    INSERT INTO claims (offer_id, customer_name, customer_email, customer_phone, unique_code, claimed_at)
    VALUES (?, ?, ?, ?, ?, ?)
  `, [offer_id, customer_name, customer_email, customer_phone, unique_code, claimed_at], async function(err) {
    if (err) return res.status(500).json({ error: err.message });
    
    // Generate QR code as data URL
    const qrDataUrl = await QRCode.toDataURL(`https://yourdomain.com/validate?code=${unique_code}`);
    
    // TODO: Send email with QR code (configure SMTP below)
    // For now, return QR code directly
    res.json({ 
      success: true, 
      unique_code,
      qr_code: qrDataUrl,
      claimed_at: claimed_at,
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
    
    // Calculate time remaining (60 days + 7 grace from claim)
    let daysRemaining = 60;
    let hoursRemaining = 0;
    if (row.claimed_at) {
      const claimedAt = new Date(row.claimed_at);
      const expiryDate = new Date(claimedAt.getTime() + (67 * 24 * 60 * 60 * 1000));
      const now = new Date();
      const diff = expiryDate - now;
      daysRemaining = Math.max(0, Math.floor(diff / (24 * 60 * 60 * 1000)));
      hoursRemaining = Math.max(0, Math.floor((diff % (24 * 60 * 60 * 1000)) / (60 * 60 * 1000)));
    }
    
    if (row.redeemed) {
      return res.json({ valid: false, message: 'Already redeemed', redeemed_at: row.redeemed_at, status: 'redeemed' });
    }
    
    // Check expiration
    if (row.offer_expiration) {
      const expDate = new Date(row.offer_expiration);
      if (expDate < new Date()) {
        return res.json({ valid: false, message: 'Offer expired', status: 'expired' });
      }
    }
    
    // Check voucher expiration
    if (daysRemaining <= 0) {
      return res.json({ valid: false, message: 'Voucher expired', status: 'expired' });
    }
    
    res.json({
      valid: true,
      status: daysRemaining <= 7 ? 'expiring' : 'active',
      customer_name: row.customer_name,
      customer_email: row.customer_email,
      customer_phone: row.customer_phone,
      offer_title: row.offer_title,
      offer_description: row.offer_description,
      offer_type: row.offer_type,
      meals_per_redemption: row.meals_per_redemption,
      business_name: row.business_name,
      claimed_at: row.claimed_at,
      days_remaining: daysRemaining,
      hours_remaining: hoursRemaining
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

// Business profile page
app.get('/profile', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'profile.html'));
});

app.get('/profile/:slug', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'profile.html'));
});

// Business claim form
app.get('/claim-business', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'claim-business.html'));
});

app.listen(PORT, () => {
  console.log(`TLN Offer System running on port ${PORT}`);
});

// ===== BUSINESS DIRECTORY API =====

// Get all businesses (directory)
app.get('/api/businesses', (req, res) => {
  const { category, city, claimed } = req.query;
  let sql = 'SELECT * FROM businesses WHERE 1=1';
  const params = [];
  
  if (category) { sql += ' AND category = ?'; params.push(category); }
  if (city) { sql += ' AND city = ?'; params.push(city); }
  if (claimed === 'true') { sql += ' AND claimed = 1'; }
  else if (claimed === 'false') { sql += ' AND claimed = 0'; }
  
  sql += ' ORDER BY name ASC';
  
  db.all(sql, params, (err, rows) => {
    if (err) return res.status(500).json({ error: err.message });
    // Parse photos JSON
    rows = rows.map(r => ({...r, photos: r.photos ? JSON.parse(r.photos) : []}));
    res.json(rows);
  });
});

// Get single business by ID or slug
app.get('/api/business/:identifier', (req, res) => {
  const { identifier } = req.params;
  const isNumeric = !isNaN(identifier);
  const sql = isNumeric 
    ? 'SELECT * FROM businesses WHERE id = ?'
    : 'SELECT * FROM businesses WHERE slug = ?';
  
  db.get(sql, [identifier], (err, row) => {
    if (err) return res.status(500).json({ error: err.message });
    if (!row) return res.status(404).json({ error: 'Business not found' });
    row.photos = row.photos ? JSON.parse(row.photos) : [];
    row.hours = row.hours ? JSON.parse(row.hours) : null;
    res.json(row);
  });
});

// Submit business claim
app.post('/api/business/claim', (req, res) => {
  const { business_id, user_name, user_email, user_phone, tier, notes } = req.body;
  
  if (!business_id || !user_name || !user_email || !tier) {
    return res.status(400).json({ error: 'Missing required fields' });
  }
  
  db.run(`
    INSERT INTO business_claims (business_id, user_name, user_email, user_phone, tier, notes)
    VALUES (?, ?, ?, ?, ?, ?)
  `, [business_id, user_name, user_email, user_phone, tier, notes], function(err) {
    if (err) return res.status(500).json({ error: err.message });
    res.json({ success: true, claim_id: this.lastID, message: 'Claim submitted for review' });
  });
});

// Get business claims (admin)
app.get('/api/business/claims', (req, res) => {
  db.all(`
    SELECT bc.*, b.name as business_name
    FROM business_claims bc
    JOIN businesses b ON bc.business_id = b.id
    ORDER BY bc.created_at DESC
  `, [], (err, rows) => {
    if (err) return res.status(500).json({ error: err.message });
    res.json(rows);
  });
});

// Approve/reject claim
app.post('/api/business/claims/:id/status', (req, res) => {
  const { id } = req.params;
  const { status } = req.body; // 'approved' or 'rejected'
  
  if (!['approved', 'rejected'].includes(status)) {
    return res.status(400).json({ error: 'Invalid status' });
  }
  
  db.get('SELECT * FROM business_claims WHERE id = ?', [id], (err, claim) => {
    if (err) return res.status(500).json({ error: err.message });
    if (!claim) return res.status(404).json({ error: 'Claim not found' });
    
    db.run('UPDATE business_claims SET status = ? WHERE id = ?', [status, id], function(err) {
      if (err) return res.status(500).json({ error: err.message });
      
      if (status === 'approved') {
        // Mark business as claimed and update tier
        db.run('UPDATE businesses SET claimed = 1, tier = ? WHERE id = ?', 
          [claim.tier, claim.business_id], (err) => {
          if (err) return res.status(500).json({ error: err.message });
          // Create user account
          db.run(`INSERT OR IGNORE INTO business_users (email, name, business_id) VALUES (?, ?, ?)`,
            [claim.user_email, claim.user_name, claim.business_id], (err) => {
            res.json({ success: true, message: 'Claim approved, business claimed' });
          });
        });
      } else {
        res.json({ success: true, message: 'Claim rejected' });
      }
    });
  });
});

// Update business profile
app.put('/api/business/:id', (req, res) => {
  const { id } = req.params;
  const { name, address, city, state, zip, phone, email, website, description, hours } = req.body;
  
  db.run(`
    UPDATE businesses SET 
      name = COALESCE(?, name),
      address = COALESCE(?, address),
      city = COALESCE(?, city),
      state = COALESCE(?, state),
      zip = COALESCE(?, zip),
      phone = COALESCE(?, phone),
      email = COALESCE(?, email),
      website = COALESCE(?, website),
      description = COALESCE(?, description),
      hours = COALESCE(?, hours),
      updated_at = datetime('now')
    WHERE id = ?
  `, [name, address, city, state, zip, phone, email, website, description, hours, id], function(err) {
    if (err) return res.status(500).json({ error: err.message });
    res.json({ success: true, message: 'Business updated' });
  });
});

// Get business dashboard data
app.get('/api/business/:id/dashboard', (req, res) => {
  const { id } = req.params;
  
  db.get('SELECT * FROM businesses WHERE id = ?', [id], (err, business) => {
    if (err) return res.status(500).json({ error: err.message });
    if (!business) return res.status(404).json({ error: 'Business not found' });
    
    // Get offers for this business with claim counts
    db.all(`
      SELECT o.*, 
        (SELECT COUNT(*) FROM claims c WHERE c.offer_id = o.id) as claim_count,
        (SELECT COUNT(*) FROM claims c WHERE c.offer_id = o.id AND c.redeemed = 1) as redeemed_count
      FROM offers o 
      WHERE o.business_name = ? 
      ORDER BY o.created_at DESC
    `, [business.name], (err, offers) => {
      if (err) return res.status(500).json({ error: err.message });
      
      const stats = {
        total_offers: offers.length,
        total_claims: offers.reduce((sum, o) => sum + (o.claim_count || 0), 0),
        total_redemptions: offers.reduce((sum, o) => sum + (o.redeemed_count || 0), 0)
      };
      
      res.json({ business, offers, stats });
    });
  });
});

// Seed some sample businesses
app.get('/api/seed-businesses', (req, res) => {
  const sampleBusinesses = [
    { name: 'XYZ Repair', slug: 'xyz-repair', address: '2300 E Providence Dr', city: 'Waxhaw', state: 'NC', zip: '28173', phone: '(704) 555-0123', category: 'auto', description: 'Quality auto repair services', tier: 'pro', claimed: 1 },
    { name: 'Taco Town', slug: 'taco-town', address: '123 Main St', city: 'Waxhaw', state: 'NC', zip: '28173', phone: '(704) 555-0456', category: 'restaurant', description: 'Authentic Mexican cuisine', tier: 'pro_plus', claimed: 1 },
    { name: 'NOMA Salon', slug: 'noma-salon', address: '456 Oak Ave', city: 'Waxhaw', state: 'NC', zip: '28173', phone: '(704) 555-0789', category: 'beauty', description: 'Full service salon and spa', tier: 'free', claimed: 0 }
  ];
  
  const stmt = db.prepare(`INSERT OR IGNORE INTO businesses (name, slug, address, city, state, zip, phone, category, description, tier, claimed) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`);
  
  sampleBusinesses.forEach(b => {
    stmt.run(b.name, b.slug, b.address, b.city, b.state, b.zip, b.phone, b.category, b.description, b.tier, b.claimed);
  });
  
  stmt.finalize();
  res.json({ success: true, message: 'Sample businesses seeded' });
});

// ===== END BUSINESS API =====

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
