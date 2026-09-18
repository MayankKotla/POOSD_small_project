const addContactButton = document.getElementById("add-contact-button");
const cancelContactButton = document.getElementById("cancel-contact-button");
const contactFormContainer = document.getElementById("contact-form-container");
const contactFormTitle = document.getElementById("contact-form-title");
const contactForm = document.getElementById("contact-form");
const contactTableBody = document.getElementById("contacts-table-body");

const firstNameInput = document.getElementById("first-name");
const lastNameInput = document.getElementById("last-name");
const emailInput = document.getElementById("email");
const phoneInput = document.getElementById("phone");
const searchInput = document.getElementById("search-input");
const searchButton = document.getElementById("search-button");
const contactsBanner = document.getElementById("contacts-banner");

let rowBeingEdited = null;

function showError(message) {
    contactsBanner.textContent = message;
    contactsBanner.className = "banner error";
}

function showSuccess(message) {
    contactsBanner.textContent = message;
    contactsBanner.className = "banner success";
}

function clearBanner() {
    contactsBanner.textContent = "";
    contactsBanner.className = "banner";
}


// Open Add Contact form
addContactButton.addEventListener("click", function () {
  
    clearBanner();
  
    contactFormTitle.textContent = "Add Contact";

  contactForm.reset();

  rowBeingEdited = null;

  contactFormContainer.classList.remove("hidden");
});


// Close form
cancelContactButton.addEventListener("click", function () {
  contactForm.reset();

  rowBeingEdited = null;

  contactFormContainer.classList.add("hidden");
});


// Handle Edit button clicks
contactTableBody.addEventListener("click", function (event) {

  if (event.target.classList.contains("edit-button")) {

    clearBanner();
    
    const button = event.target;

    const row = button.closest("tr");

    const cells = row.querySelectorAll("td");

    contactFormTitle.textContent = "Edit Contact";

    firstNameInput.value = cells[0].textContent.trim();
    lastNameInput.value = cells[1].textContent.trim();
    emailInput.value = cells[2].textContent.trim();
    phoneInput.value = cells[3].textContent.trim();

    rowBeingEdited = row;

    contactFormContainer.classList.remove("hidden");
  }

  if (event.target.classList.contains("delete-button")) {

  const row = event.target.closest("tr");

  const firstName = row.cells[0].textContent.trim();
  const lastName = row.cells[1].textContent.trim();

  const confirmed = confirm(
    `Are you sure you want to delete ${firstName} ${lastName}?`
  );

  if (confirmed) {
    row.remove();
    showSuccess("Contact deleted successfully.");
  }
}

});


// Save Contact
contactForm.addEventListener("submit", function (event) {

  event.preventDefault();

  const firstName = firstNameInput.value.trim();
  const lastName = lastNameInput.value.trim();
  const email = emailInput.value.trim();
  const phone = phoneInput.value.trim();

  const today = new Date().toLocaleDateString();

  clearBanner();

if (
    firstName === "" ||
    lastName === "" ||
    email === "" ||
    phone === ""
) {
    showError("Please complete all contact fields.");
    return;
}

if (!email.includes("@") || !email.includes(".")) {
    showError("Please enter a valid email address.");
    return;
}

  // Editing an existing contact
  if (rowBeingEdited !== null) {

    const cells = rowBeingEdited.querySelectorAll("td");

    cells[0].textContent = firstName;
    cells[1].textContent = lastName;
    cells[2].textContent = email;
    cells[3].textContent = phone;

    showSuccess("Contact updated successfully.");

  }

  // Adding a new contact
  else {

    const newRow = document.createElement("tr");

    newRow.innerHTML = `
      <td>${firstName}</td>
      <td>${lastName}</td>
      <td>${email}</td>
      <td>${phone}</td>
      <td>${today}</td>
      <td>
        <button class="edit-button">
          Edit
        </button>

        <button class="delete-button">
          Delete
        </button>
      </td>
    `;

    contactTableBody.appendChild(newRow);

    showSuccess("Contact added successfully.");
  }

  contactForm.reset();

  rowBeingEdited = null;

  contactFormContainer.classList.add("hidden");
});

// Search contacts
searchButton.addEventListener("click", function () {

  const searchTerm = searchInput.value.trim().toLowerCase();

  const rows = contactTableBody.querySelectorAll("tr");

  rows.forEach(function (row) {

    const cells = row.querySelectorAll("td");

    const firstName = cells[0].textContent.toLowerCase();
    const lastName = cells[1].textContent.toLowerCase();

    const matchesSearch =
      firstName.includes(searchTerm) ||
      lastName.includes(searchTerm);

    if (matchesSearch) {
      row.style.display = "";
    } else {
      row.style.display = "none";
    }

  });

});

// Search when Enter key is pressed
searchInput.addEventListener("keydown", function (event) {

    if (event.key === "Enter") {
        searchButton.click();
    }

});