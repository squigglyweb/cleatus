/**
 * CHS VendorPrint Approval API v5.0
 * - Status workflow: DRAFT → PENDING → APPROVED / REVISION NEEDED / REJECTED → SUBMITTED → DELIVERED
 * - Proof upload to Drive
 * - Email notifications
 * - Multi-approver support
 */

const CONFIG = {
  SHEET_ID: '1jfOqV3ykRJwBPQLX5QegBbuarwirqJtObywZmEzhK8E',
  SHEET_NAME: 'Orders',
  DRIVE_FOLDER_ID: '1n9Pxm6dBYhYaeegbMMSzdUhGhREJxM8k', // Proofs root folder
  TOKEN: 'd!dxQPNM38zm',
  ALLOWED_DOMAIN: 'housingservices.com'
};

// Status enum
const STATUS = {
  DRAFT: 'DRAFT',
  PENDING: 'PENDING',
  APPROVED: 'APPROVED',
  REVISION_NEEDED: 'REVISION NEEDED',
  REJECTED: 'REJECTED',
  SUBMITTED: 'SUBMITTED',
  IN_PRODUCTION: 'IN PRODUCTION',
  DELIVERED: 'DELIVERED'
};

// Field mapping to exact sheet headers
const FIELD_MAP = {
  requester: 'Requester',
  item: 'Item',
  qty: 'Qty',
  shipTo: 'Ship To',
  status: 'Status',
  name: 'Name',
  title: 'Title',
  email: 'Email',
  office: 'Office',
  cell: 'Cell',
  approvedBy: 'Approved By',
  backupApprover: 'Backup Approver',
  approvedAt: 'Approved At',
  rep: 'Rep',
  notes: 'Notes',
  timestamp: 'Timestamp',
  tracking: 'Tracking',
  proofFrontUrl: 'Proof Front URL',
  proofBackUrl: 'Proof Back URL',
  revisionNotes: 'Revision Notes',
  statusChangedAt: 'Status Changed At'
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

    if (action === 'order.create') {
      // Legacy - redirect to new endpoint
      return jsonpOut(cb, handleSubmitForApproval(params));
    }

    if (action === 'order.submitForApproval') {
      return jsonpOut(cb, handleSubmitForApproval(params));
    }

    if (action === 'order.approve') {
      return jsonpOut(cb, handleApprove(params));
    }

    if (action === 'order.requestChanges') {
      return jsonpOut(cb, handleRequestChanges(params));
    }

    if (action === 'order.reject') {
      return jsonpOut(cb, handleReject(params));
    }

    if (action === 'order.updateStatus') {
      return jsonpOut(cb, handleUpdateStatus(params));
    }

    if (action === 'orders.list') {
      return jsonpOut(cb, handleOrdersList(params));
    }

    if (action === 'orders.pending') {
      return jsonpOut(cb, handlePendingOrders(params));
    }

    if (action === 'dashboard.stats') {
      return jsonpOut(cb, handleDashboardStats());
    }

    if (action === 'uploadProof') {
      return jsonpOut(cb, handleProofUpload(params));
    }

    // Health check
    return jsonpOut(cb, { ok: true, status: 'API v5.0 running' });

  } catch (err) {
    return jsonpOut(cb, { ok: false, error: String(err) });
  }
}

/* --------------------------------------------------
 SUBMIT FOR APPROVAL
 Creates order in PENDING status, sends notifications
--------------------------------------------------- */
function handleSubmitForApproval(params) {
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

  // Validate email domain
  const email = (incoming.email || '').toLowerCase();
  if (!email.endsWith('@' + CONFIG.ALLOWED_DOMAIN)) {
    return { ok: false, error: 'domain_not_allowed', message: 'Only ' + CONFIG.ALLOWED_DOMAIN + ' emails allowed' };
  }

  const data = sh.getDataRange().getValues();
  const header = data[0] || [];
  const idxMap = {};
  header.forEach((h, i) => { idxMap[String(h)] = i; });

  if (idxMap['Order #'] == null) {
    throw new Error('Missing "Order #" column');
  }

  const orderId = generateOrderId(sh, idxMap['Order #']);
  const now = new Date().toISOString();

  // Build row
  const rowArr = new Array(header.length).fill('');
  rowArr[idxMap['Order #']] = orderId;
  rowArr[idxMap['Status']] = STATUS.PENDING;
  rowArr[idxMap['Timestamp']] = now;
  rowArr[idxMap['Status Changed At']] = now;
  rowArr[idxMap['Requester']] = incoming.requester || incoming.name || '';
  rowArr[idxMap['Item']] = incoming.item || 'Business Cards';
  rowArr[idxMap['Qty']] = incoming.qty || 250;
  rowArr[idxMap['Ship To']] = incoming.shipTo || '';
  rowArr[idxMap['Name']] = incoming.name || '';
  rowArr[idxMap['Title']] = incoming.title || '';
  rowArr[idxMap['Email']] = incoming.email || '';
  rowArr[idxMap['Office']] = incoming.office || '';
  rowArr[idxMap['Cell']] = incoming.cell || '';

  // Auto-generate 4O-File Name for Business Cards
  const idxItem = idxMap['Item'];
  const idxEmail = idxMap['Email'];
  const idx4OFile = idxMap['4O-File Name'];

  if (idx4OFile != null && idxItem != null && idxEmail != null) {
    const itemVal = String(rowArr[idxItem] || '').trim();
    const emailVal = String(rowArr[idxEmail] || '').trim().toLowerCase();
    if (itemVal === 'Business Cards' && emailVal.includes('@')) {
      const userPart = emailVal.split('@')[0];
      rowArr[idx4OFile] = 'CHS-FR-250-' + userPart;
    }
  }

  sh.appendRow(rowArr);

  // Send notification emails
  sendApprovalRequestEmail(orderId, incoming);

  return { ok: true, orderId: orderId, status: STATUS.PENDING };
}

/* --------------------------------------------------
 APPROVE ORDER
--------------------------------------------------- */
function handleApprove(params) {
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
  const approverName = incoming.approverName || params.approverName || 'Unknown';
  const isBackup = incoming.isBackup || false;

  if (!orderId) {
    return { ok: false, error: 'missing_orderId' };
  }

  const row = findOrderRow(sh, orderId);
  if (!row) {
    return { ok: false, error: 'order_not_found' };
  }

  const idx = getHeaderIndex(sh);
  const currentStatus = String(sh.getRange(row, idx['Status'] + 1).getValue());

  if (currentStatus !== STATUS.PENDING && currentStatus !== STATUS.REVISION_NEEDED) {
    return { ok: false, error: 'invalid_status', message: 'Order is not pending approval' };
  }

  const now = new Date().toISOString();
  const approverField = isBackup ? 'Backup Approver' : 'Approved By';
  const existingApprover = String(sh.getRange(row, idx[approverField] + 1).getValue() || '');

  // Update approver
  sh.getRange(row, idx[approverField] + 1).setValue(approverName);
  sh.getRange(row, idx['Approved At'] + 1).setValue(now);
  sh.getRange(row, idx['Status'] + 1).setValue(STATUS.APPROVED);
  sh.getRange(row, idx['Status Changed At'] + 1).setValue(now);

  // Clear revision notes if any
  if (idx['Revision Notes'] != null) {
    sh.getRange(row, idx['Revision Notes'] + 1).setValue('');
  }

  // Notify requester
  sendApprovedEmail(orderId, sh, row, approverName);

  return { ok: true, orderId: orderId, status: STATUS.APPROVED, approvedBy: approverName };
}

/* --------------------------------------------------
 REQUEST CHANGES
--------------------------------------------------- */
function handleRequestChanges(params) {
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
  const revisionNotes = incoming.revisionNotes || params.revisionNotes || 'Please make changes';
  const requesterName = incoming.requesterName || params.requesterName || 'Approver';

  if (!orderId) {
    return { ok: false, error: 'missing_orderId' };
  }

  const row = findOrderRow(sh, orderId);
  if (!row) {
    return { ok: false, error: 'order_not_found' };
  }

  const idx = getHeaderIndex(sh);
  const now = new Date().toISOString();

  sh.getRange(row, idx['Status'] + 1).setValue(STATUS.REVISION_NEEDED);
  sh.getRange(row, idx['Status Changed At'] + 1).setValue(now);
  sh.getRange(row, idx['Revision Notes'] + 1).setValue(revisionNotes);

  // Notify requester
  sendRevisionNeededEmail(orderId, sh, row, revisionNotes, requesterName);

  return { ok: true, orderId: orderId, status: STATUS.REVISION_NEEDED };
}

/* --------------------------------------------------
 REJECT ORDER
--------------------------------------------------- */
function handleReject(params) {
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
  const rejectionReason = incoming.rejectionReason || params.rejectionReason || 'Rejected by approver';

  if (!orderId) {
    return { ok: false, error: 'missing_orderId' };
  }

  const row = findOrderRow(sh, orderId);
  if (!row) {
    return { ok: false, error: 'order_not_found' };
  }

  const idx = getHeaderIndex(sh);
  const now = new Date().toISOString();

  sh.getRange(row, idx['Status'] + 1).setValue(STATUS.REJECTED);
  sh.getRange(row, idx['Status Changed At'] + 1).setValue(now);
  sh.getRange(row, idx['Notes'] + 1).setValue('REJECTED: ' + rejectionReason);

  // Notify requester
  sendRejectedEmail(orderId, sh, row, rejectionReason);

  return { ok: true, orderId: orderId, status: STATUS.REJECTED };
}

/* --------------------------------------------------
 UPDATE STATUS (for VendorPrint workflow)
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

  // Notify requester of status change
  sendStatusUpdateEmail(orderId, sh, row, newStatus);

  return { ok: true, orderId: orderId, status: newStatus };
}

/* --------------------------------------------------
 LIST ORDERS (with optional filters)
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

  // Newest first
  orders.reverse();
  return { ok: true, orders: orders.slice(0, limit) };
}

/* --------------------------------------------------
 PENDING ORDERS (for approval dashboard)
--------------------------------------------------- */
function handlePendingOrders(params) {
  const result = handleOrdersList({ status: STATUS.PENDING, limit: 100 });
  return result;
}

/* --------------------------------------------------
 DASHBOARD STATS
--------------------------------------------------- */
function handleDashboardStats() {
  const ss = SpreadsheetApp.openById(CONFIG.SHEET_ID);
  const sh = ss.getSheetByName(CONFIG.SHEET_NAME);

  const data = sh.getDataRange().getValues();
  if (!data || data.length < 2) {
    return {
      ok: true,
      pending: 0,
      approved: 0,
      inProduction: 0,
      delivered: 0,
      rejected: 0
    };
  }

  const idx = getHeaderIndex(sh);
  const stats = { pending: 0, approved: 0, inProduction: 0, delivered: 0, rejected: 0 };

  for (let r = 1; r < data.length; r++) {
    const status = String(data[r][idx['Status']] || '').trim();
    switch (status) {
      case STATUS.PENDING: stats.pending++; break;
      case STATUS.APPROVED: stats.approved++; break;
      case STATUS.IN_PRODUCTION: stats.inProduction++; break;
      case STATUS.DELIVERED: stats.delivered++; break;
      case STATUS.REJECTED: stats.rejected++; break;
    }
  }

  return { ok: true, stats };
}

/* --------------------------------------------------
 PROOF UPLOAD
--------------------------------------------------- */
function handleProofUpload(params) {
  const orderId = params.orderId || '';
  const side = params.side || 'front'; // front or back
  const fileName = params.fileName || '';
  const base64 = params.base64 || '';

  if (!orderId) {
    return { ok: false, error: 'missing_orderId' };
  }

  if (!base64) {
    return { ok: false, error: 'missing_file' };
  }

  const rootFolder = DriveApp.getFolderById(CONFIG.DRIVE_FOLDER_ID);
  const safeId = String(orderId).replace(/[^\w\-]/g, '_');
  const folder = getOrCreateFolder(rootFolder, 'Proofs_' + safeId);

  // Determine mime type
  let mimeType = 'image/png';
  if (fileName.toLowerCase().endsWith('.pdf')) {
    mimeType = 'application/pdf';
  } else if (fileName.toLowerCase().endsWith('.jpg') || fileName.toLowerCase().endsWith('.jpeg')) {
    mimeType = 'image/jpeg';
  }

  // Decode base64
  let blob;
  try {
    const clean = base64.replace(/^data:\w+\/\w+;base64,/, '');
    blob = Utilities.newBlob(Utilities.base64Decode(clean), mimeType, safeId + '_' + side + '_' + fileName);
  } catch (err) {
    return { ok: false, error: 'decode_failed', message: String(err) };
  }

  const file = folder.createFile(blob);
  file.setSharing(DriveApp.Access.ANYONE_WITH_LINK, DriveApp.Permission.VIEW);
  const url = file.getUrl();

  // Update sheet
  const ss = SpreadsheetApp.openById(CONFIG.SHEET_ID);
  const sh = ss.getSheetByName(CONFIG.SHEET_NAME);
  const row = findOrderRow(sh, orderId);

  if (row) {
    const idx = getHeaderIndex(sh);
    const col = (side === 'front') ? idx['Proof Front URL'] : idx['Proof Back URL'];
    if (col != null) {
      sh.getRange(row, col + 1).setValue(url);
    }
  }

  return { ok: true, url: url, orderId: orderId, side: side };
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
      return r + 1; // 1-indexed for Apps Script
    }
  }
  return null;
}

function generateOrderId(sheet, orderColIndex) {
  const now = new Date();
  const mm = ('0' + (now.getMonth() + 1)).slice(-2);
  const dd = ('0' + now.getDate()).slice(-2);
  const yy = String(now.getFullYear()).slice(-2);
  const datePart = mm + dd + yy;

  const data = sheet.getDataRange().getValues();
  let countThisMonth = 0;

  for (let r = 1; r < data.length; r++) {
    const val = String(data[r][orderColIndex] || '');
    if (val.startsWith('ORD-' + mm)) {
      countThisMonth++;
    }
  }

  const seq = ('0' + (countThisMonth + 1)).slice(-2);
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
    backupApprover: String(get('Backup Approver')).trim(),
    approvedAt: String(get('Approved At')).trim(),
    proofFrontUrl: String(get('Proof Front URL')).trim(),
    proofBackUrl: String(get('Proof Back URL')).trim(),
    revisionNotes: String(get('Revision Notes')).trim(),
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
function sendApprovalRequestEmail(orderId, data) {
  const subject = '📋 New Order Pending Approval - ' + orderId;
  const body = `A new order requires your approval:

Order #: ${orderId}
Item: ${data.item || 'Business Cards'}
Qty: ${data.qty || 250}
Name: ${data.name || ''}
Title: ${data.title || ''}
Email: ${data.email || ''}

Please review and approve in the VendorPrint Dashboard.

- CHS VendorPrint System`;

  MailApp.sendEmail({
    to: 'bryan.somers@housingservices.com', // TODO: Configure approver email(s)
    subject: subject,
    body: body,
    name: 'CHS VendorPrint'
  });
}

function sendApprovedEmail(orderId, sheet, row, approverName) {
  const idx = getHeaderIndex(sheet);
  const requester = String(sheet.getRange(row, idx['Requester'] + 1).getValue());
  const email = String(sheet.getRange(row, idx['Email'] + 1).getValue());
  const item = String(sheet.getRange(row, idx['Item'] + 1).getValue());

  if (!email) return;

  const subject = '✅ Your Order Approved - ' + orderId;
  const body = `Great news! Your ${item} order has been approved.

Order #: ${orderId}
Approved by: ${approverName}
Approved at: ${new Date().toLocaleString()}

Your order will be processed shortly.

- CHS VendorPrint System`;

  MailApp.sendEmail({
    to: email,
    subject: subject,
    body: body,
    name: 'CHS VendorPrint'
  });
}

function sendRevisionNeededEmail(orderId, sheet, row, notes, requesterName) {
  const idx = getHeaderIndex(sheet);
  const email = String(sheet.getRange(row, idx['Email'] + 1).getValue());
  const item = String(sheet.getRange(row, idx['Item'] + 1).getValue());

  if (!email) return;

  const subject = '❗ Changes Needed - ' + orderId;
  const body = `Your ${item} order requires changes before it can be approved.

Order #: ${orderId}
Notes from approver: ${notes}

Please make the necessary updates and resubmit.

- CHS VendorPrint System`;

  MailApp.sendEmail({
    to: email,
    subject: subject,
    body: body,
    name: 'CHS VendorPrint'
  });
}

function sendRejectedEmail(orderId, sheet, row, reason) {
  const idx = getHeaderIndex(sheet);
  const email = String(sheet.getRange(row, idx['Email'] + 1).getValue());
  const item = String(sheet.getRange(row, idx['Item'] + 1).getValue());

  if (!email) return;

  const subject = '❌ Order Rejected - ' + orderId;
  const body = `Your ${item} order has been rejected.

Order #: ${orderId}
Reason: ${reason}

Please contact the approver for more details.

- CHS VendorPrint System`;

  MailApp.sendEmail({
    to: email,
    subject: subject,
    body: body,
    name: 'CHS VendorPrint'
  });
}

function sendStatusUpdateEmail(orderId, sheet, row, newStatus) {
  const idx = getHeaderIndex(sheet);
  const email = String(sheet.getRange(row, idx['Email'] + 1).getValue());
  const item = String(sheet.getRange(row, idx['Item'] + 1).getValue());

  if (!email) return;

  const subject = '📦 Order Status Update - ' + orderId;
  const body = `Your ${item} order status has been updated.

Order #: ${orderId}
New Status: ${newStatus}

- CHS VendorPrint System`;

  MailApp.sendEmail({
    to: email,
    subject: subject,
    body: body,
    name: 'CHS VendorPrint'
  });
}