function showStep(id) {
  document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
  document.getElementById(id).classList.add('active');
}
function nextStep(id) { showStep('step' + id); }
function prevStep(id) { showStep('step' + id); }
function showLeadCapture() { showStep('leadCapture'); }

// Placeholder submit - you would hook to a backend or Zapier
document.getElementById('diagnoseForm').addEventListener('submit', function(e){
  e.preventDefault();
  const data = new FormData(this);
  // For demo, just alert JSON
  const obj = {};
  data.forEach((value, key) => { obj[key] = value; });
  alert('Thanks! We received your info: ' + JSON.stringify(obj, null, 2));
  this.reset();
  showStep('step1');
});
