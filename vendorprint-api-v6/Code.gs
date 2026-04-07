/**
 * CHS VendorPrint API v6.2 - With Dashboard Endpoints + Order Lookup
 * 
 * Endpoints added:
 * - orders.myOrders: get orders by requester email
 * - orders.byOrderId: get single order by order number
 * - dashboard.quickStats: get quick stats for a requester
 */

const CONFIG = {
  SHEET_ID: '1jfOqV3ykRJwBPQLX5QegBbuarwirqJtObywZmEzhK8E',
  SHEET_NAME: 'Orders',
  DRIVE_FOLDER_ID: '1n9Pxm6dBYhYaeegbMMSzdUhGhREJxM8k',
  TOKEN: 'd!dxQPNM38zm',
  ALLOWED_DOMAIN: 'housingservices.com'
};

// Status enum
const STATUS = {
  APPROVED: 'APPROVED',
  SUBMITTED: 'SUBMITTED',
  IN_PRODUCTION: 'IN PRODUCTION',
  SHIPPED: 'SHIPPED',
  DELIVERED: 'DELIVERED'
};

/* --------------------------------------------------
 Entry Point
--------------------------------------------------- */
function doGet(e) {
  const params = (e && e.parameter) || {};
  const cb = params.callback || '';
  const action = String(params.action || '').trim();

  try {
    // Token gate
    if (!params.token || String(params.token) !== CONFIG.TOKEN) {
      return jsonpOut(cb, { ok: false, error: 'bad_token' });
    }

    if (action === 'order.submit') {
      return jsonpOut(cb, handleSubmitBatch(params));
    }

    if (action === 'proof.upload') {
      return jsonpOut(cb, handleProofUpload(params));
    }

    if (action === 'orders.list') {
      return jsonpOut(cb, handleOrdersList(params));
    }

    if (action === 'orders.myOrders') {
      return jsonpOut(cb, handleMyOrders(params));
    }

    if (action === 'orders.byOrderId') {
      return jsonpOut(cb, handleOrderById(params));
    }

    if (action === 'orders.approved') {
      return jsonpOut(cb, handleApprovedOrders(params));
    }

    if (action === 'order.updateStatus') {
      return jsonpOut(cb, handleUpdateStatus(params));
    }

    if (action === 'dashboard.stats') {
      return jsonpOut(cb, handleDashboardStats());
    }

    if (action === 'dashboard.quickStats') {
      return jsonpOut(cb, handleQuickStats(params));
    }

    // Health check
    return jsonpOut(cb, { ok: true, status: 'API v6.2 running' });

  } catch (err) {
    return jsonpOut(cb, { ok: false, error: String(err) });
  }
}

/* --------------------------------------------------
 SUBMIT BATCH
--------------------------------------------------- */
function handleSubmitBatch(params) {
  const ss = SpreadsheetApp.openById(CONFIG.SHEET_ID);
  const sh = ss.getSheetByName(CONFIG.SHEET_NAME);
  if (!sh) throw new Error('Sheet not found: ' + CONFIG.SHEET_NAME);

  let incoming = {};
  if (params.payload) {
    try {
      const p = JSON.parse(params.payload);
      incoming = (p && p.row) ? p.row : p;
    } catch (err) {
      incoming = {};
    }
  }
  if (!Object.keys(incoming).length) incoming = params;

  let cards = [];
  if (Array.isArray(incoming.cards)) {
    cards = incoming.cards;
  } else if (incoming.card) {
    cards = [incoming.card];
  } else {
    return { ok: false, error: 'no_cards', message: 'Expected {cards: [...]}' };
  }

  if (!cards.length) {
    return { ok: false, error: 'empty_batch' };
  }

  const data = sh.getDataRange().getValues();
  const header = data[0] || [];
  const idxMap = {};
  header.forEach((h, i) => { idxMap[String(h)] = i; });

  if (idxMap['Order #'] == null) {
    throw new Error('Missing "Order #" column');
  }

  const now = new Date().toISOString();
  let submittedCount = 0;
  const orderIds = [];

  for (let i = 0; i < cards.length; i++) {
    const card = cards[i];
    const orderId = generateOrderId(sh, idxMap['Order #'], i + 1);
    orderIds.push(orderId);

    const rowArr = new Array(header.length).fill('');
    rowArr[idxMap['Order #']] = orderId;
    rowArr[idxMap['Status']] = STATUS.APPROVED;
    rowArr[idxMap['Timestamp']] = now;
    rowArr[idxMap['Status Changed At']] = now;
    rowArr[idxMap['Requester']] = card.requesterName || card.approverName || '';
    rowArr[idxMap['Item']] = card.item || 'Business Cards';
    rowArr[idxMap['Qty']] = card.qty || 250;
    rowArr[idxMap['Ship To']] = card.shipTo || '';
    rowArr[idxMap['Name']] = card.name || '';
    rowArr[idxMap['Title']] = card.title || '';
    rowArr[idxMap['Email']] = card.email || '';
    rowArr[idxMap['Office']] = card.office || '';
    rowArr[idxMap['Cell']] = card.cell || '';
    rowArr[idxMap['Approved By']] = card.approverName || card.requesterName || '';
    rowArr[idxMap['Approved At']] = now;

    if (idxMap['Proof Front URL'] != null && card.proofFrontUrl) {
      rowArr[idxMap['Proof Front URL']] = card.proofFrontUrl;
    }
    if (idxMap['Proof Back URL'] != null && card.proofBackUrl) {
      rowArr[idxMap['Proof Back URL']] = card.proofBackUrl;
    }

    const idxItem = idxMap['Item'];
    const idxEmail = idxMap['Email'];
    const idx4OFile = idxMap['4O-File Name'];

    if (idx4OFile != null && idxItem != null && idxEmail != null) {
      const itemVal = String(card.item || 'Business Cards').trim();
      const emailVal = String(card.email || '').trim().toLowerCase();
      if (itemVal === 'Business Cards' && emailVal.includes('@')) {
        const userPart = emailVal.split('@')[0];
        rowArr[idx4OFile] = 'CHS-FR-250-' + userPart;
      }
    }

    sh.appendRow(rowArr);
    submittedCount++;
  }

  sendBatchSubmittedEmail(submittedCount, orderIds);

  return { 
    ok: true, 
    submitted: submittedCount, 
    orderIds: orderIds,
    status: STATUS.APPROVED
  };
}

/* --------------------------------------------------
 PROOF UPLOAD
--------------------------------------------------- */
function handleProofUpload(params) {
  const orderId = params.orderId || 'ORD-' + Date.now();
  const side = params.side || 'proof';
  const fileName = params.fileName || 'proof.png';
  const base64 = params.base64 || '';

  if (!base64) {
    return { ok: false, error: 'missing_file', message: 'No base64 data provided' };
  }

  const rootFolder = DriveApp.getFolderById(CONFIG.DRIVE_FOLDER_ID);
  const safeId = String(orderId).replace(/[^\w\-]/g, '_');
  const orderFolder = getOrCreateFolder(rootFolder, 'Proofs_' + safeId);

  let mimeType = 'image/png';
  if (fileName.toLowerCase().endsWith('.pdf')) {
    mimeType = 'application/pdf';
  } else if (fileName.toLowerCase().endsWith('.jpg') || fileName.toLowerCase().endsWith('.jpeg')) {
    mimeType = 'image/jpeg';
  }

  let blob;
  try {
    const clean = base64.replace(/^data:\w+\/\w+;base64,/, '');
    blob = Utilities.newBlob(Utilities.base64Decode(clean), mimeType, side + '_' + fileName);
  } catch (err) {
    return { ok: false, error: 'decode_failed', message: String(err) };
  }

  const file = orderFolder.createFile(blob);
  file.setSharing(DriveApp.Access.ANYONE_WITH_LINK, DriveApp.Permission.VIEW);
  const url = file.getUrl();

  return { 
    ok: true, 
    url: url, 
    orderId: orderId, 
    side: side,
    fileId: file.getId()
  };
}

/* --------------------------------------------------
 LIST ORDERS
--------------------------------------------------- */
function handleOrdersList(params) {
  const ss = SpreadsheetApp.openById(CONFIG.SHEET_ID);
  const sh = ss.getSheetByName(CONFIG.SHEET_NAME);

  const data = sh.getDataRange().getValues();
  if (!data || data.length < 2) {
    return { ok: true, orders: [] };
  }

  const idx = getHeaderIndex(sh);
  const statusFilter = params.status || '';
  const limit = parseInt(params.limit, 10) || 50;

  const orders = [];
  for (let r = 1; r < data.length; r++) {
    const row = data[r];
    const orderId = String(row[idx['Order #']] || '').trim();
    if (!orderId) continue;

    const status = String(row[idx['Status']] || '').trim();

    if (statusFilter && status !== statusFilter) continue;

    orders.push(buildOrderObject(row, idx));
  }

  orders.reverse();
  return { ok: true, orders: orders.slice(0, limit) };
}

/* --------------------------------------------------
 MY ORDERS - by email
--------------------------------------------------- */
function handleMyOrders(params) {
  const ss = SpreadsheetApp.openById(CONFIG.SHEET_ID);
  const sh = ss.getSheetByName(CONFIG.SHEET_NAME);

  const email = String(params.email || '').toLowerCase().trim();
  if (!email) {
    return { ok: false, error: 'missing_email', message: 'Email required' };
  }

  const data = sh.getDataRange().getValues();
  if (!data || data.length < 2) {
    return { ok: true, orders: [] };
  }

  const idx = getHeaderIndex(sh);
  const limit = parseInt(params.limit, 10) || 20;

  const orders = [];
  for (let r = 1; r < data.length; r++) {
    const row = data[r];
    const orderEmail = String(row[idx['Email']] || '').toLowerCase().trim();
    
    // Match by email
    if (orderEmail !== email) continue;

    orders.push(buildOrderObject(row, idx));
  }

  orders.reverse();
  return { ok: true, orders: orders.slice(0, limit) };
}

/* --------------------------------------------------
 ORDER BY ID
--------------------------------------------------- */
function handleOrderById(params) {
  const ss = SpreadsheetApp.openById(CONFIG.SHEET_ID);
  const sh = ss.getSheetByName(CONFIG.SHEET_NAME);

  const orderId = String(params.orderId || '').trim();
  if (!orderId) {
    return { ok: false, error: 'missing_orderId' };
  }

  const data = sh.getDataRange().getValues();
  if (!data || data.length < 2) {
    return { ok: false, error: 'order_not_found' };
  }

  const idx = getHeaderIndex(sh);

  for (let r = 1; r < data.length; r++) {
    const row = data[r];
    const thisOrderId = String(row[idx['Order #']] || '').trim();
    
    if (thisOrderId === orderId) {
      return { ok: true, order: buildOrderObject(row, idx) };
    }
  }

  return { ok: false, error: 'order_not_found' };
}

/* --------------------------------------------------
 APPROVED ORDERS
--------------------------------------------------- */
function handleApprovedOrders(params) {
  return handleOrdersList({ status: STATUS.APPROVED, limit: 100 });
}

/* --------------------------------------------------
 UPDATE STATUS
--------------------------------------------------- */
function handleUpdateStatus(params) {
  const ss = SpreadsheetApp.openById(CONFIG.SHEET_ID);
  const sh = ss.getSheetByName(CONFIG.SHEET_NAME);

  let incoming = {};
  if (params.payload) {
    try {
      const p = JSON.parse(params.payload);
      incoming = (p && p.row) ? p.row : p;
    } catch (err) {
      incoming = {};
    }
  }
  if (!Object.keys(incoming).length) incoming = params;

  const orderId = incoming.orderId || params.orderId;
  const newStatus = incoming.status || params.status;

  if (!orderId || !newStatus) {
    return { ok: false, error: 'missing_parameters' };
  }

  const row = findOrderRow(sh, orderId);
  if (!row) {
    return { ok: false, error: 'order_not_found' };
  }

  const idx = getHeaderIndex(sh);
  const now = new Date().toISOString();

  sh.getRange(row, idx['Status'] + 1).setValue(newStatus);
  sh.getRange(row, idx['Status Changed At'] + 1).setValue(now);

  return { ok: true, orderId: orderId, status: newStatus };
}

/* --------------------------------------------------
 DASHBOARD STATS (all orders)
--------------------------------------------------- */
function handleDashboardStats() {
  const ss = SpreadsheetApp.openById(CONFIG.SHEET_ID);
  const sh = ss.getSheetByName(CONFIG.SHEET_NAME);

  const data = sh.getDataRange().getValues();
  if (!data || data.length < 2) {
    return {
      ok: true,
      approved: 0,
      submitted: 0,
      inProduction: 0,
      shipped: 0,
      delivered: 0
    };
  }

  const idx = getHeaderIndex(sh);
  const stats = { approved: 0, submitted: 0, inProduction: 0, shipped: 0, delivered: 0 };

  for (let r = 1; r < data.length; r++) {
    const status = String(data[r][idx['Status']] || '').trim();
    switch (status) {
      case STATUS.APPROVED: stats.approved++; break;
      case STATUS.SUBMITTED: stats.submitted++; break;
      case STATUS.IN_PRODUCTION: stats.inProduction++; break;
      case STATUS.SHIPPED: stats.shipped++; break;
      case STATUS.DELIVERED: stats.delivered++; break;
    }
  }

  return { ok: true, stats };
}

/* --------------------------------------------------
 QUICK STATS (by email)
--------------------------------------------------- */
function handleQuickStats(params) {
  const ss = SpreadsheetApp.openById(CONFIG.SHEET_ID);
  const sh = ss.getSheetByName(CONFIG.SHEET_NAME);

  const email = String(params.email || '').toLowerCase().trim();
  
  const data = sh.getDataRange().getValues();
  if (!data || data.length < 2) {
    return {
      ok: true,
      total: 0,
      inProduction: 0,
      delivered: 0,
      thisMonth: 0
    };
  }

  const idx = getHeaderIndex(sh);
  const now = new Date();
  const thisMonth = now.getMonth();
  const thisYear = now.getFullYear();

  let total = 0;
  let inProduction = 0;
  let delivered = 0;
  let thisMonthCount = 0;

  for (let r = 1; r < data.length; r++) {
    const row = data[r];
    const orderEmail = String(row[idx['Email']] || '').toLowerCase().trim();
    
    if (email && orderEmail !== email) continue;

    const status = String(row[idx['Status']] || '').trim();
    if (status === STATUS.IN_PRODUCTION || status === STATUS.SHIPPED) {
      inProduction++;
    }
    if (status === STATUS.DELIVERED) {
      delivered++;
    }
    total++;

    // This month
    const ts = row[idx['Timestamp']];
    if (ts) {
      let d;
      if (ts instanceof Date) {
        d = ts;
      } else {
        d = new Date(String(ts));
      }
      if (!isNaN(d) && d.getMonth() === thisMonth && d.getFullYear() === thisYear) {
        thisMonthCount++;
      }
    }
  }

  return { 
    ok: true, 
    total: total,
    inProduction: inProduction,
    delivered: delivered,
    thisMonth: thisMonthCount
  };
}

/* --------------------------------------------------
 HELPERS
--------------------------------------------------- */
function getHeaderIndex(sh) {
  const data = sh.getDataRange().getValues();
  const header = data[0] || [];
  const idx = {};
  header.forEach((h, i) => { idx[String(h).trim()] = i; });
  return idx;
}

function findOrderRow(sh, orderId) {
  const data = sh.getDataRange().getValues();
  const idx = getHeaderIndex(sh);
  const orderCol = idx['Order #'];

  for (let r = 1; r < data.length; r++) {
    if (String(data[r][orderCol]).trim() === String(orderId).trim()) {
      return r + 1;
    }
  }
  return null;
}

function generateOrderId(sheet, orderColIndex, sequenceOffset) {
  const now = new Date();
  const mm = ('0' + (now.getMonth() + 1)).slice(-2);
  const dd = ('0' + now.getDate()).slice(-2);
  const yy = String(now.getFullYear()).slice(-2);
  const datePart = mm + dd + yy;

  const data = sheet.getDataRange().getValues();
  let countThisMonth = 0;

  for (let r = 1; r < data.length; r++) {
    const val = String(data[r][orderColIndex] || '');
    if (val.startsWith('ORD-' + mm + dd + yy)) {
      countThisMonth++;
    }
  }

  const seq = ('0' + (countThisMonth + sequenceOffset)).slice(-2);
  return 'ORD-' + datePart + '-' + seq;
}

function buildOrderObject(row, idx) {
  const get = (key) => {
    const i = idx[key];
    return (i != null) ? row[i] : '';
  };

  return {
    orderId: String(get('Order #')).trim(),
    requester: String(get('Requester')).trim(),
    item: String(get('Item')).trim(),
    qty: Number(get('Qty')) || 0,
    shipTo: String(get('Ship To')).trim(),
    status: String(get('Status')).trim(),
    name: String(get('Name')).trim(),
    title: String(get('Title')).trim(),
    email: String(get('Email')).trim(),
    office: String(get('Office')).trim(),
    cell: String(get('Cell')).trim(),
    approvedBy: String(get('Approved By')).trim(),
    approvedAt: String(get('Approved At')).trim(),
    proofFrontUrl: String(get('Proof Front URL')).trim(),
    proofBackUrl: String(get('Proof Back URL')).trim(),
    timestamp: String(get('Timestamp')).trim(),
    statusChangedAt: String(get('Status Changed At')).trim(),
    tracking: String(get('Tracking')).trim()
  };
}

function getOrCreateFolder(parent, name) {
  const it = parent.getFoldersByName(name);
  return it.hasNext() ? it.next() : parent.createFolder(name);
}

function jsonpOut(cb, obj) {
  const text = cb ? cb + '(' + JSON.stringify(obj) + ');' : JSON.stringify(obj);
  return ContentService.createTextOutput(text).setMimeType(ContentService.MimeType.JAVASCRIPT);
}

/* --------------------------------------------------
 EMAIL NOTIFICATIONS
--------------------------------------------------- */
function sendBatchSubmittedEmail(count, orderIds) {
  const subject = '📦 New VendorPrint Batch - ' + count + ' cards ready';
  const body = `A new batch has been submitted and is ready for production.

Cards: ${count}
Order IDs: ${orderIds.join(', ')}

View in Google Sheet:
https://docs.google.com/spreadsheets/d/${CONFIG.SHEET_ID}

- CHS VendorPrint System`;

  MailApp.sendEmail({
    to: 'bryan.somers@housingservices.com',
    subject: subject,
    body: body,
    name: 'CHS VendorPrint'
  });
}