// Login and registration use the PHP API. Mock mode is for local UI demos only.
const USE_MOCK_API = false;

// A couple of fake accounts so login can be demoed against register.
const MOCK_USERS = [
  { id: 1, email: "demo@example.com", password: "password123", firstName: "Demo", lastName: "User" }
];

/**
 * Single entry point for hitting the auth API — real or mocked.
 * @param {string} endpoint - e.g. "register.php" or "login.php"
 * @param {object} body - the JSON payload to send
 * @returns {Promise<object>} the parsed JSON response
 */
async function callApi(endpoint, body) {
  if (USE_MOCK_API) {
    return mockApi(endpoint, body);
  }

  const res = await fetch(`/api/${endpoint}`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(body)
  });

  const result = await res.json();
  if (!result || typeof result.success !== "boolean") {
    throw new Error("Unexpected API response");
  }
  if (!res.ok && result.success) {
    throw new Error(`Server returned ${res.status}`);
  }
  return result;
}

/**
 * Fakes the two auth endpoints in-memory so the UI can be built and
 * tested before the real backend exists. Simulates network latency
 * so loading states are visible too.
 */
function mockApi(endpoint, body) {
  const delay = (value) => new Promise((resolve) => setTimeout(() => resolve(value), 500));

  if (endpoint === "register.php") {
    const exists = MOCK_USERS.some((u) => u.email === body.email);
    if (exists) {
      return delay({ success: false, error: "An account with that email already exists." });
    }
    MOCK_USERS.push({
      id: MOCK_USERS.length + 1,
      email: body.email,
      password: body.password,
      firstName: body.firstName,
      lastName: body.lastName
    });
    return delay({ success: true, error: "" });
  }

  if (endpoint === "login.php") {
    const user = MOCK_USERS.find((u) => u.email === body.email && u.password === body.password);
    if (!user) {
      return delay({ success: false, error: "Incorrect email or password.", id: null });
    }
    return delay({ success: true, error: "", id: user.id });
  }

  return delay({ success: false, error: `Unknown mock endpoint: ${endpoint}` });
}

function showBanner(el, message, type) {
  el.textContent = message;
  el.className = `banner ${type}`;
}

function hideBanner(el) {
  el.className = "banner";
  el.textContent = "";
}

// ---- Login page wiring ----
function initLoginForm() {
  const form = document.getElementById("login-form");
  if (!form) return; // not on this page

  const banner = document.getElementById("login-banner");
  const button = form.querySelector("button.submit");

  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    hideBanner(banner);

    const email = form.email.value.trim();
    const password = form.password.value;

    if (!email || !password) {
      showBanner(banner, "Please fill in both fields.", "error");
      return;
    }

    button.disabled = true;
    button.textContent = "Logging in...";

    try {
      const result = await callApi("login.php", { email, password });
      if (result.success) {
        showBanner(banner, "Logged in!", "success");
        // Once the contacts page exists: window.location.href = "contacts.html";
      } else {
        showBanner(banner, result.error || "Login failed.", "error");
      }
    } catch (err) {
      showBanner(banner, "Something went wrong reaching the server.", "error");
    } finally {
      button.disabled = false;
      button.textContent = "Log in";
    }
  });
}

// ---- Register page wiring ----
function initRegisterForm() {
  const form = document.getElementById("register-form");
  if (!form) return; // not on this page

  const banner = document.getElementById("register-banner");
  const button = form.querySelector("button.submit");

  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    hideBanner(banner);

    const firstName = form.firstName.value.trim();
    const lastName = form.lastName.value.trim();
    const email = form.email.value.trim();
    const password = form.password.value;

    if (!firstName || !lastName || !email || !password) {
      showBanner(banner, "Please fill in every field.", "error");
      return;
    }
    if (password.length < 8) {
      showBanner(banner, "Password must be at least 8 characters.", "error");
      return;
    }

    button.disabled = true;
    button.textContent = "Creating account...";

    try {
      const result = await callApi("register.php", { firstName, lastName, email, password });
      if (result.success) {
        showBanner(banner, "Account created! You can log in now.", "success");
        form.reset();
      } else {
        showBanner(banner, result.error || "Registration failed.", "error");
      }
    } catch (err) {
      showBanner(banner, "Something went wrong reaching the server.", "error");
    } finally {
      button.disabled = false;
      button.textContent = "Create account";
    }
  });
}

document.addEventListener("DOMContentLoaded", () => {
  initLoginForm();
  initRegisterForm();
});
