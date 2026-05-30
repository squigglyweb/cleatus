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

function doPost(e) {
  const params = e.parameter;
  const action = params.action;
  
  if (action === 'append') {
    const data = JSON.parse(params.data);
    
    const ss = SpreadsheetApp.openById(SPREADSHEET_ID);
    let sheet = ss.getSheetByName(SHEET_NAME);
    
    // Create sheet if it doesn't exist
    if (!sheet) {
      sheet = ss.insertSheet(SHEET_NAME);
      // Add headers
      sheet.appendRow(['Submitted', 'Business Name', 'Contact Name', 'Email', 'Phone', 'Campaign Type', 'Message']);
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

// Test function to verify setup
function testConnection() {
  const ss = SpreadsheetApp.openById(SPREADSHEET_ID);
  return ss.getName();
}