/**
 * TLN Business Seeder
 * Uses Google Places API to find all businesses in an area
 * 
 * Setup:
 * 1. Get Google API key with Places API enabled
 * 2. Set GOOGLE_API_KEY environment variable
 * 3. Run: node seed-businesses.js
 */

const https = require('https');
const fs = require('fs');

// CONFIG: Your area
const CONFIG = {
  // Waxhaw area coordinates
  location: '34.9248,-80.7473', // Waxhaw, NC
  radius: 15000, // 15km radius
  // Alternatively use a bounding box
  // northeast: '35.0,-80.6',
  // southwest: '34.8,-80.9',
};

// Categories to search (one at a time)
const CATEGORIES = [
  'restaurant',
  'cafe',
  'bar',
  'pizza',
  'bakery',
  'grocery_store',
  'store',
  'shopping_mall',
  'beauty_salon',
  'hair_care',
  'spa',
  'gym',
  'fitness_center',
  'plumber',
  'electrician',
  'contractor',
  'roofing_contractor',
  'landscape_architect',
  'lawn_mower_repair',
  'car_repair',
  'car_wash',
  'gas_station',
  'bank',
  'atm',
  'pharmacy',
  'doctor',
  'dentist',
  'veterinary_care',
  'pet_store',
  'hotel',
  'motel',
];

// Function to make Google API request
function googlePlacesRequest(endpoint, params) {
  return new Promise((resolve, reject) => {
    const queryString = new URLSearchParams(params).toString();
    const options = {
      hostname: 'maps.googleapis.com',
      port: 443,
      path: `/maps/api/place/${endpoint}?${queryString}`,
      method: 'GET',
    };

    const req = https.request(options, (res) => {
      let data = '';
      res.on('data', (chunk) => data += chunk);
      res.on('end', () => {
        try {
          resolve(JSON.parse(data));
        } catch (e) {
          reject(e);
        }
      });
    });

    req.on('error', reject);
    req.end();
  });
}

// Search for businesses in a category
async function searchCategory(category) {
  const apiKey = process.env.GOOGLE_API_KEY;
  
  if (!apiKey) {
    console.log('❌ Set GOOGLE_API_KEY environment variable first');
    console.log('   Run: export GOOGLE_API_KEY="your-api-key"');
    return [];
  }

  try {
    console.log(`📍 Searching: ${category}...`);
    
    const response = await googlePlacesRequest('nearbysearch/json', {
      location: CONFIG.location,
      radius: CONFIG.radius,
      type: category,
      key: apiKey,
    });

    if (response.status === 'OK') {
      console.log(`   ✓ Found ${response.results.length} ${category} businesses`);
      return response.results;
    } else {
      console.log(`   ⚠ Status: ${response.status}`);
      return [];
    }
  } catch (error) {
    console.log(`   ❌ Error: ${error.message}`);
    return [];
  }
}

// Get full details for a place (including reviews)
async function getPlaceDetails(placeId) {
  const apiKey = process.env.GOOGLE_API_KEY;
  
  try {
    const response = await googlePlacesRequest('details/json', {
      place_id: placeId,
      fields: 'name,formatted_address,geometry,formatted_phone_number,website,rating,user_ratings_total,opening_hours,types',
      key: apiKey,
    });

    if (response.status === 'OK') {
      return response.result;
    }
    return null;
  } catch (error) {
    console.log(`   ❌ Details error: ${error.message}`);
    return null;
  }
}

// Main seeding function
async function seedBusinesses() {
  const apiKey = process.env.GOOGLE_API_KEY;
  
  if (!apiKey) {
    console.log('===========================================');
    console.log('TLN BUSINESS SEEDER');
    console.log('===========================================');
    console.log('');
    console.log('To get started:');
    console.log('1. Go to console.cloud.google.com');
    console.log('2. Create a project, enable Places API');
    console.log('3. Create API key in Credentials');
    console.log('4. Run: export GOOGLE_API_KEY="your-key-here"');
    console.log('5. Run: node seed-businesses.js');
    console.log('');
    return;
  }

  console.log('🚀 Starting TLN Business Seeder...');
  console.log(`📍 Location: Waxhaw, NC (${CONFIG.location})`);
  console.log(`📏 Radius: ${CONFIG.radius}m`);
  console.log('');

  const allBusinesses = [];
  const seenIds = new Set();

  for (const category of CATEGORIES) {
    const results = await searchCategory(category);
    
    for (const place of results) {
      if (!seenIds.has(place.place_id)) {
        seenIds.add(place.place_id);
        
        allBusinesses.push({
          name: place.name,
          place_id: place.place_id,
          address: place.vicinity || place.formatted_address,
          category: category,
          rating: place.rating || 0,
          review_count: place.user_ratings_total || 0,
          types: place.types,
          lat: place.geometry?.location?.lat,
          lng: place.geometry?.location?.lng,
        });
      }
    }
    
    // Rate limiting - wait between requests
    await new Promise(resolve => setTimeout(resolve, 100));
  }

  console.log('');
  console.log(`✅ Total unique businesses found: ${allBusinesses.length}`);
  console.log('');

  // Save to JSON file for import
  const output = {
    generated: new Date().toISOString(),
    location: CONFIG.location,
    total: allBusinesses.length,
    businesses: allBusinesses,
  };

  fs.writeFileSync('./tln-businesses.json', JSON.stringify(output, null, 2));
  console.log('💾 Saved to tln-businesses.json');
  console.log('');

  // Generate CSV for easy import
  let csv = 'Name,Address,Category,Rating,Reviews,PlaceID,Types,Lat,Lng\n';
  for (const b of allBusinesses) {
    const name = `"${b.name.replace(/"/g, '""')}"`;
    const address = `"${b.address.replace(/"/g, '""')}"`;
    const types = `"${b.types.join(', ')}"`;
    csv += `${name},${address},${b.category},${b.rating},${b.review_count},${b.place_id},${types},${b.lat},${b.lng}\n`;
  }

  fs.writeFileSync('./tln-businesses.csv', csv);
  console.log('💾 Also saved to tln-businesses.csv');
  console.log('');
  console.log('Next step: Import these into WordPress using WP All Import');
}

// Run if called directly
seedBusinesses();

module.exports = { seedBusinesses, searchCategory, getPlaceDetails };
