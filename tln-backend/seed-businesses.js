/**
 * TLN Business Seeder - Uses Google Places API (New)
 */

const https = require('https');
const fs = require('fs');

const CONFIG = {
  location: '34.9248,-80.7473', // Waxhaw, NC
  radius: 15000, // 15km
};

const CATEGORIES = [
  'restaurant', 'cafe', 'bar', 'pizza', 'bakery',
  'grocery_store', 'store', 'shopping_mall',
  'beauty_salon', 'hair_care', 'spa',
  'gym', 'fitness_center',
  'plumber', 'electrician', 'contractor', 'roofing_contractor',
  'car_repair', 'car_wash', 'gas_station',
  'bank', 'atm', 'pharmacy',
  'doctor', 'dentist', 'veterinary_care',
  'hotel', 'motel'
];

function makeRequest(url) {
  return new Promise((resolve, reject) => {
    https.get(url, (res) => {
      let data = '';
      res.on('data', chunk => data += chunk);
      res.on('end', () => {
        try {
          resolve(JSON.parse(data));
        } catch (e) {
          reject(e);
        }
      });
    }).on('error', reject);
  });
}

async function searchCategory(category) {
  const apiKey = process.env.GOOGLE_API_KEY;
  
  const url = `https://places.googleapis.com/v1/places:searchNearby?key=${apiKey}`;
  
  const body = JSON.stringify({
    locationRestriction: {
      circle: {
        center: { latitude: 34.9248, longitude: -80.7473 },
        radius: CONFIG.radius
      },
    },
    includedTypes: [category],
    maxResultCount: 20
  });

  return new Promise((resolve, reject) => {
    const req = https.request(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Goog-Api-Key': apiKey,
        'X-Goog-FieldMask': 'places.displayName,places.formattedAddress,places.rating,places.userRatingCount,places.location,places.id'
      }
    }, (res) => {
      let data = '';
      res.on('data', chunk => data += chunk);
      res.on('end', async () => {
        try {
          const json = JSON.parse(data);
          if (json.places) {
            console.log(`   ✓ Found ${json.places.length} ${category}`);
            resolve(json.places.map(p => ({
              name: p.displayName?.text || 'Unknown',
              address: p.formattedAddress || '',
              rating: p.rating || 0,
              review_count: p.userRatingCount || 0,
              place_id: p.id,
              lat: p.location?.latitude,
              lng: p.location?.longitude,
              category: category
            })));
          } else {
            console.log(`   ⚠ ${json.error?.message || 'No results'}`);
            resolve([]);
          }
        } catch (e) {
          console.log(`   ❌ Error: ${e.message}`);
          resolve([]);
        }
      });
    });
    
    req.on('error', e => {
      console.log(`   ❌ Request error: ${e.message}`);
      resolve([]);
    });
    
    req.write(body);
    req.end();
  });
}

async function seedBusinesses() {
  const apiKey = process.env.GOOGLE_API_KEY;
  
  if (!apiKey) {
    console.log('Set GOOGLE_API_KEY first');
    return;
  }

  console.log('🚀 Starting TLN Business Seeder (New API)...\n');

  const allBusinesses = [];
  const seenIds = new Set();

  for (const category of CATEGORIES) {
    const results = await searchCategory(category);
    
    for (const place of results) {
      if (!seenIds.has(place.place_id)) {
        seenIds.add(place.place_id);
        allBusinesses.push(place);
      }
    }
    
    await new Promise(r => setTimeout(r, 100));
  }

  console.log(`\n✅ Total: ${allBusinesses.length} businesses`);

  fs.writeFileSync('./tln-businesses.json', JSON.stringify({
    generated: new Date().toISOString(),
    total: allBusinesses.length,
    businesses: allBusinesses
  }, null, 2));

  // CSV
  let csv = 'Name,Address,Category,Rating,Reviews,PlaceID,Lat,Lng\n';
  for (const b of allBusinesses) {
    csv += `"${b.name}","${b.address}","${b.category}",${b.rating},${b.review_count},${b.place_id},${b.lat},${b.lng}\n`;
  }
  fs.writeFileSync('./tln-businesses.csv', csv);
  console.log('💾 Saved to tln-businesses.json and .csv');
}

seedBusinesses();
