# Rattlesnake Mountain

A custom HARPG utility and site for **Siat-s**, built to support worldbuilding, interactive rollers, and member account features. This application is designed to grow with the needs of the Rattlesnake Mountain universe.

---

## 📦 Stack

- **Laravel 12** – Back-end framework for routing, database access, and authentication.
- **Vue 3** (with Inertia.js) – Front-end framework for reactive UI and single-page application (SPA) feel without full decoupling.
- **Tailwind CSS** – Utility-first styling for a clean, responsive design.
- **MySQL** – Relational database for user data, roller results, and CMS content.
- **Inertia.js** – Bridges Laravel and Vue, allowing server-side routing with modern front-end interactions.
- **Vite** – Front-end build tool for fast development and asset bundling.

---

## ✅ Features (In Progress)

- Static informational pages (Home, About, Getting Started, etc.)
- Interactive roller utilities for breeding and training
- CMS for dynamic page content
- User authentication: register, login, profile management
- Admin dashboard for managing site data and user permissions
- Responsive layout suitable for desktop and mobile
- Component-based Vue structure for future scalability

---

## 🛠 Developer Notes

- Vue pages are stored in `resources/js/Pages/`, organized by route responsibility.
- Inertia routes are defined in `routes/web.php`, using Laravel’s route helpers.
- Styling follows Tailwind conventions with minimal custom CSS.
- Components are reusable and structured to support expansion into more advanced features (e.g., inventory systems, event logging).

---

## 🔒 Auth & Roles

- Users can register and log in via Laravel Breeze + Inertia.
- Admin role allows access to management panels (WIP).
- Role middleware and permissions to be implemented using Laravel Policies and Gates.

---

## 📂 File Structure Overview

```plaintext
resources/
├── js/
│   ├── Pages/
│   │   ├── Static/         # Home, About, Contact, etc.
│   │   ├── Rollers/        # Breeding/training tools
│   │   ├── Auth/           # Login, Register, Password reset
│   │   └── Dashboard.vue   # Main user landing area
│   └── Components/         # Reusable UI elements
├── views/
│   └── app.blade.php       # Inertia mount point
routes/
├── web.php                 # Route definitions
