// NearBuy Google Apps Script
// Deploy as Web App to get URL

// SHEET NAMES
var BUSINESSES_SHEET = 'Businesses';
var MESSAGES_SHEET = 'Messages';
var VOUCHERS_SHEET = 'Vouchers';

// ==================== API ENDPOINTS ====================

function doPost(e) {
  var action = e.parameter.action;
  
  try {
    if (action === 'login') return handleLogin(e);
    if (action === 'sendMessage') return handleSendMessage(e);
    if (action === 'getMessages') return handleGetMessages(e);
    if (action === 'validateVoucher') return handleValidateVoucher(e);
    if (action === 'getBusinessData') return handleGetBusinessData(e);
    
    return returnJson({ success: false, error: 'Unknown action: ' + action });
  } catch (err) {
    return returnJson({ success: false, error: err.message });
  }
}

function doGet(e) {
  return returnJson({ status: 'NearBuy API is running', timestamp: new Date() });
}

// ==================== LOGIN ====================

function handleLogin(e) {
  var code = e.parameter.code.trim().toUpperCase();
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(BUSINESSES_SHEET);
  var data = sheet.getDataRange().getValues();
  
  // Find business by code (column A = code, B = name, C = email)
  for (var i = 1; i < data.length; i++) {
    if (data[i][0] === code) {
      return returnJson({ 
        success: true, 
        business: {
          code: data[i][0],
          name: data[i][1],
          email: data[i][2],
          spots: data[i][3],
          qrCode: data[i][4]
        }
      });
    }
  }
  
  return returnJson({ success: false, error: 'Invalid code' });
}

// ==================== MESSAGES ====================

function handleSendMessage(e) {
  var code = e.parameter.code.trim().toUpperCase();
  var message = e.parameter.message;
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(MESSAGES_SHEET);
  
  // Get business name
  var bizSheet = ss.getSheetByName(BUSINESSES_SHEET);
  var bizData = bizSheet.getDataRange().getValues();
  var bizName = '';
  for (var i = 1; i < bizData.length; i++) {
    if (bizData[i][0] === code) {
      bizName = bizData[i][1];
      break;
    }
  }
  
  // Append message
  sheet.appendRow([
    new Date(),
    code,
    bizName,
    message,
    'NO' // responded = NO
  ]);
  
  // TODO: Send email notification to you (Bryan)
  // MailApp.sendEmail('your-email@gmail.com', 'New Message from ' + bizName, message);
  
  return returnJson({ success: true });
}

function handleGetMessages(e) {
  var code = e.parameter.code.trim().toUpperCase();
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(MESSAGES_SHEET);
  var data = sheet.getDataRange().getValues();
  
  var messages = [];
  for (var i = 1; i < data.length; i++) {
    if (data[i][1] === code) {
      messages.push({
        date: data[i][0].toString(),
        message: data[i][3],
        responded: data[i][4]
      });
    }
  }
  
  return returnJson({ success: true, messages: messages });
}

// ==================== VOUCHER VALIDATION ====================

function handleValidateVoucher(e) {
  var code = e.parameter.code.trim().toUpperCase();
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(VOUCHERS_SHEET);
  var data = sheet.getDataRange().getValues();
  
  for (var i = 1; i < data.length; i++) {
    var voucherCode = data[i][0] ? data[i][0].toString().toUpperCase() : '';
    var businessCode = data[i][1] ? data[i][1].toString().toUpperCase() : '';
    var status = data[i][4];
    var expiry = data[i][3];
    
    if (voucherCode === code) {
      // Check if expired
      if (new Date(expiry) < new Date()) {
        return returnJson({ 
          success: true, 
          valid: false, 
          status: 'EXPIRED',
          message: 'This voucher has expired'
        });
      }
      
      // Check if already redeemed
      if (status === 'REDEEMED') {
        return returnJson({ 
          success: true, 
          valid: false, 
          status: 'ALREADY_REDEEMED',
          message: 'This voucher has already been redeemed'
        });
      }
      
      // Valid - mark as redeemed
      sheet.getRange(i + 1, 5).setValue('REDEEMED');
      sheet.getRange(i + 1, 6).setValue(new Date());
      
      return returnJson({ 
        success: true, 
        valid: true, 
        status: 'VALID',
        message: 'Voucher redeemed! Thank the customer.'
      });
    }
  }
  
  return returnJson({ 
    success: true, 
    valid: false, 
    status: 'INVALID',
    message: 'Voucher code not found'
  });
}

// ==================== BUSINESS DATA ====================

function handleGetBusinessData(e) {
  var code = e.parameter.code.trim().toUpperCase();
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(BUSINESSES_SHEET);
  var data = sheet.getDataRange().getValues();
  
  for (var i = 1; i < data.length; i++) {
    if (data[i][0] === code) {
      // Get voucher stats
      var vSheet = ss.getSheetByName(VOUCHERS_SHEET);
      var vData = vSheet.getDataRange().getValues();
      var scans = 0, claims = 0, redeemed = 0;
      
      for (var j = 1; j < vData.length; j++) {
        if (vData[j][1] === code) {
          scans++;
          if (vData[j][4] === 'CLAIMED' || vData[j][4] === 'REDEEMED') claims++;
          if (vData[j][4] === 'REDEEMED') redeemed++;
        }
      }
      
      return returnJson({
        success: true,
        business: {
          code: data[i][0],
          name: data[i][1],
          spots: data[i][3],
          scans: scans,
          claims: claims,
          redeemed: redeemed
        }
      });
    }
  }
  
  return returnJson({ success: false, error: 'Business not found' });
}

// ==================== HELPER ====================

function returnJson(obj) {
  return ContentService
    .createTextOutput(JSON.stringify(obj))
    .setMimeType(ContentService.MimeType.JSON);
}

// ==================== SETUP ====================

function setupSpreadsheet() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  
  // Create sheets if they don't exist
  if (!ss.getSheetByName(BUSINESSES_SHEET)) {
    ss.insertSheet(BUSINESSES_SHEET);
    var sheet = ss.getSheetByName(BUSINESSES_SHEET);
    sheet.appendRow(['Code', 'Name', 'Email', 'Spots', 'QR Code']);
    sheet.appendRow(['BUSINESS001', 'JGL Repair', 'jgl@email.com', 'S-1', 'NEARBUY-JGL001']);
    sheet.appendRow(['BUSINESS002', 'Papaya Skin Care', 'papaya@email.com', 'S-2', 'NEARBUY-PAPAYA002']);
    sheet.appendRow(['BUSINESS003', 'Marvin Pressure Washing', 'marvin@email.com', 'S-4,S-7', 'NEARBUY-MARVIN003']);
  }
  
  if (!ss.getSheetByName(MESSAGES_SHEET)) {
    ss.insertSheet(MESSAGES_SHEET);
    var sheet = ss.getSheetByName(MESSAGES_SHEET);
    sheet.appendRow(['Timestamp', 'Business Code', 'Business Name', 'Message', 'Responded']);
  }
  
  if (!ss.getSheetByName(VOUCHERS_SHEET)) {
    ss.insertSheet(VOUCHERS_SHEET);
    var sheet = ss.getSheetByName(VOUCHERS_SHEET);
    sheet.appendRow(['Voucher Code', 'Business Code', 'Email', 'Expiry Date', 'Status', 'Redeemed Date']);
    sheet.appendRow(['WAX-1001', 'BUSINESS001', 'mike@email.com', '2026-05-15', 'REDEEMED', '2026-03-20']);
    sheet.appendRow(['WAX-1002', 'BUSINESS001', 'sarah@email.com', '2026-05-15', 'CLAIMED', '']);
    sheet.appendRow(['WAX-2001', 'BUSINESS002', 'james@email.com', '2026-05-15', 'REDEEMED', '2026-03-18']);
  }
  
  Logger.log('Sheets created!');
}