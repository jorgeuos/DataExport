// If I need to add JS...

let allFilesChecked = false;

// Check all the checkboxes
function selectAllFiles() {
  $('.de-all-files input[type="checkbox"]').prop('checked', true);
}
function deSelectAllFiles() {
  $('.de-all-files input[type="checkbox"]').prop('checked', true);
}

document.addEventListener('DOMContentLoaded', function() {
  const mainCheck = document.getElementById('de-sel-files');
  if (!mainCheck) {
    return;
  }
  const fileCheckboxes = document.querySelectorAll('.de-all-files input[type="checkbox"]');
  // Event listener for the "Select all" checkbox
  mainCheck.addEventListener('change', function() {
    // Loop through each file checkbox and update its "checked" state to match the "Select all" checkbox
    fileCheckboxes.forEach(function(checkbox) {
        checkbox.checked = mainCheck.checked;
    });
  });
});
