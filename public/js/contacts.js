/*
 * contacts.js — wired to the real PHP API documented in docs/API.md.
 *
 * On load: confirm the user is logged in (GET /api/me.php), then load
 * their contacts (GET /api/getContacts.php). Add/Edit/Delete/Search all
 * hit the real endpoints and re-fetch the list afterward so the table
 * always reflects what's actually in the database.
 */

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
const logoutButton = document.getElementById("logout-button");

// The ID of the contact currently loaded into the form, or null when adding.
let contactIdBeingEdited = null;

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

/**
 * Single entry point for every API call on this page.
 * Always parses the JSON body, even on a non-2xx response, so the
 * caller can show the API's own error message.
 */
async function callApi(endpoint, { method = "GET", body } = {}) {
    const options = { method };
    if (body !== undefined) {
        options.headers = { "Content-Type": "application/json" };
        options.body = JSON.stringify(body);
    }

    const res = await fetch(`/api/${endpoint}`, options);

    if (res.status === 401) {
        // Session missing or expired — send the user back to log in.
        window.location.href = "login.html";
        // Throw so the caller's try/catch stops instead of continuing
        // with a response that will never resolve usefully.
        throw new Error("Not logged in");
    }

    let result;
    try {
        result = await res.json();
    } catch (err) {
        throw new Error("Unexpected response from the server.");
    }

    if (res.ok) {
        return result;
    }

    // Non-2xx, non-401: surface the API's own error message.
    throw new Error((result && result.error) || `Server returned ${res.status}`);
}

/** Builds one <tr> for a contact using textContent — never innerHTML — so a
 *  contact's own name/email/phone can never be interpreted as markup. */
function buildContactRow(contact) {
    const row = document.createElement("tr");
    row.dataset.id = contact.id;

    const firstNameCell = document.createElement("td");
    firstNameCell.textContent = contact.firstName;

    const lastNameCell = document.createElement("td");
    lastNameCell.textContent = contact.lastName;

    const emailCell = document.createElement("td");
    emailCell.textContent = contact.email;

    const phoneCell = document.createElement("td");
    phoneCell.textContent = contact.phone;

    const dateCell = document.createElement("td");
    dateCell.textContent = contact.dateAdded
        ? new Date(contact.dateAdded).toLocaleDateString()
        : "";

    const actionsCell = document.createElement("td");

    const editButton = document.createElement("button");
    editButton.className = "edit-button";
    editButton.textContent = "Edit";

    const deleteButton = document.createElement("button");
    deleteButton.className = "delete-button";
    deleteButton.textContent = "Delete";

    actionsCell.append(editButton, deleteButton);
    row.append(firstNameCell, lastNameCell, emailCell, phoneCell, dateCell, actionsCell);

    return row;
}

function renderContacts(contacts) {
    contactTableBody.innerHTML = "";
    contacts.forEach((contact) => {
        contactTableBody.appendChild(buildContactRow(contact));
    });
}

/** Loads the full contact list (used on page load, after add/edit/delete,
 *  and whenever the search box is cleared). */
async function loadContacts() {
    try {
        const contacts = await callApi("getContacts.php");
        renderContacts(contacts);
    } catch (err) {
        showError(err.message || "Could not load contacts.");
    }
}

async function runSearch(term) {
    try {
        const contacts = await callApi(
            `searchContacts.php?search=${encodeURIComponent(term)}`
        );
        renderContacts(contacts);
    } catch (err) {
        showError(err.message || "Search failed.");
    }
}

// ---- Page load: confirm login, then load contacts ----
document.addEventListener("DOMContentLoaded", async () => {
    try {
        await callApi("me.php");
    } catch (err) {
        // callApi already redirects to login.html on a 401.
        return;
    }
    loadContacts();
});

// ---- Log out ----
logoutButton.addEventListener("click", async () => {
    try {
        await callApi("logout.php", { method: "POST" });
    } catch (err) {
        // Even if the request fails, still send the user to the login page.
    }
    window.location.href = "login.html";
});

// Open Add Contact form
addContactButton.addEventListener("click", function () {
    clearBanner();

    contactFormTitle.textContent = "Add Contact";
    contactForm.reset();
    contactIdBeingEdited = null;

    contactFormContainer.classList.remove("hidden");
});

// Close form
cancelContactButton.addEventListener("click", function () {
    contactForm.reset();
    contactIdBeingEdited = null;
    contactFormContainer.classList.add("hidden");
});

// Handle Edit / Delete button clicks (event delegation on the table body)
contactTableBody.addEventListener("click", async function (event) {

    if (event.target.classList.contains("edit-button")) {
        clearBanner();

        const row = event.target.closest("tr");
        const cells = row.querySelectorAll("td");

        contactFormTitle.textContent = "Edit Contact";

        firstNameInput.value = cells[0].textContent.trim();
        lastNameInput.value = cells[1].textContent.trim();
        emailInput.value = cells[2].textContent.trim();
        phoneInput.value = cells[3].textContent.trim();

        contactIdBeingEdited = row.dataset.id;

        contactFormContainer.classList.remove("hidden");
    }

    if (event.target.classList.contains("delete-button")) {
        const row = event.target.closest("tr");
        const cells = row.querySelectorAll("td");
        const firstName = cells[0].textContent.trim();
        const lastName = cells[1].textContent.trim();

        const confirmed = confirm(
            `Are you sure you want to delete ${firstName} ${lastName}?`
        );
        if (!confirmed) {
            return;
        }

        try {
            await callApi("deleteContact.php", {
                method: "POST",
                body: { id: Number(row.dataset.id) },
            });
            showSuccess("Contact deleted successfully.");
            loadContacts();
        } catch (err) {
            showError(err.message || "Could not delete contact.");
        }
    }

});

// Save Contact (add or edit)
contactForm.addEventListener("submit", async function (event) {
    event.preventDefault();

    const firstName = firstNameInput.value.trim();
    const lastName = lastNameInput.value.trim();
    const email = emailInput.value.trim();
    const phone = phoneInput.value.trim();

    clearBanner();

    if (firstName === "" || lastName === "" || email === "" || phone === "") {
        showError("Please complete all contact fields.");
        return;
    }

    if (!email.includes("@") || !email.includes(".")) {
        showError("Please enter a valid email address.");
        return;
    }

    const fields = { firstName, lastName, email, phone };

    try {
        if (contactIdBeingEdited !== null) {
            await callApi("updateContact.php", {
                method: "POST",
                body: { id: Number(contactIdBeingEdited), ...fields },
            });
            showSuccess("Contact updated successfully.");
        } else {
            await callApi("addContact.php", {
                method: "POST",
                body: fields,
            });
            showSuccess("Contact added successfully.");
        }

        contactForm.reset();
        contactIdBeingEdited = null;
        contactFormContainer.classList.add("hidden");
        loadContacts();
    } catch (err) {
        showError(err.message || "Could not save contact.");
    }
});

// Search contacts (server-side, matches first/last/full name, phone, or email)
searchButton.addEventListener("click", function () {
    const searchTerm = searchInput.value.trim();
    runSearch(searchTerm);
});

// Search when Enter key is pressed
searchInput.addEventListener("keydown", function (event) {
    if (event.key === "Enter") {
        event.preventDefault();
        searchButton.click();
    }
});
