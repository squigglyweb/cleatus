/**
 * Google Apps Script - Campaign Request Handler
 * 
 * INSTRUCTIONS:
 * 1. Go to https://script.google.com and create a new project
 * 2. Paste this entire code into the editor
 * 3. Update the SPREADSHEET_ID variable below (your sheet ID from the URL)
 * 4. Save the project
 * 5. Click Deploy > New Deployment
 * 6. Select "Web app"
 * 7. Set Execute as: "Me"
 * 8. Set Who has access: "Anyone" (so your form can submit)
 * 9. Deploy and copy the Web App URL
 * 10. Paste that URL into submit-campaign.php for $googleSheetUrl
 */

const SPREADSHEET_ID = 'YOUR_SPREADSHEET_ID_HERE';
const SHEET_NAME = 'Campaign Requests';

function doGet(e) {
  return handleRequest(e);
}

function doPost(e) {
  return handleRequest(e);
}

function handleRequest(e) {
  const params = e.parameter;
  const action = params.action;
  
  if (action === 'orders.list') {
    const ss = SpreadsheetApp.openById(SPREADSHEET_ID);
    let sheet = ss.getSheetByName(SHEET_NAME);
    
    if (!sheet) {
      return jsonpOutput(params.callback, { ok: false, error: 'No orders yet' });
    }
    
    const data = sheet.getDataRange().getValues();
    const headers = data[0];
    const orders = [];
    
    for (let i = 1; i < data.length; i++) {
      const row = data[i];
      const orderId = row[0];
      if (!orderId) continue;
      
      orders.push({
        orderId: String(orderId),
        requester: row[1] || '',
        item: row[2] || 'Business Cards',
        qty: row[3] || 250,
        shipTo: row[4] || '',
        status: row[5] || 'In Production',
        lastScan: row[6] || '',
        tracking: row[7] || '',
        name: row[8] || '',
        rep: row[9] || '',
        timestamp: row[10] || '',
        approvedAt: row[11] || ''
      });
    }
    
    return jsonpOutput(params.callback, { ok: true, orders: orders });
  }
  
  if (action === 'append') {
    const data = JSON.parse(params.data);
    
    const ss = SpreadsheetApp.openById(SPREADSHEET_ID);
    let sheet = ss.getSheetByName(SHEET_NAME);
    
    // Create sheet if it doesn't exist
    if (!sheet) {
      sheet = ss.insertSheet(SHEET_NAME);
      // Add headers for orders.list
      sheet.appendRow(['Order ID', 'Requester', 'Item', 'Qty', 'Ship To', 'Status', 'Last Scan', 'Tracking', 'Name', 'Rep', 'Timestamp', 'Approved At']);
    }
    
    // Append the data
    sheet.appendRow(data);
    
    return ContentService
      .createTextOutput(JSON.stringify({ success: true }))
      .setMimeType(ContentService.MimeType.JSON);
  }
  
  return ContentService
    .createTextOutput(JSON.stringify({ success: false, error: 'Invalid action' }))
    .setMimeType(ContentService.MimeType.JSON);
}

function jsonpOutput(callback, data) {
  const json = JSON.stringify(data);
  const output = callback + '(' + json + ')';
  return ContentService
    .createTextOutput(output)
    .setMimeType(ContentService.MimeType.TEXT);
}

// Test function to verify setup
function testConnection() {
  const ss = SpreadsheetApp.openById(SPREADSHEET_ID);
  return ss.getName();
}