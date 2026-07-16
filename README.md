# Rattlesnake Mountain

A custom HARPG utility and site for **Siat-s**, built to support worldbuilding, interactive rollers, and member account features. This application is designed to grow with the needs of the Rattlesnake Mountain universe.

## 📦 Stack

- **Laravel 12** – Back-end framework for routing, database access, and authentication.
- **Vue 3** (with Inertia.js) – Front-end framework for reactive UI and single-page application (SPA) feel without full decoupling.
- **Tailwind CSS** – Utility-first styling for a clean, responsive design.
- **MySQL** – Relational database for user data, roller results, and CMS content.
- **Inertia.js** – Bridges Laravel and Vue, allowing server-side routing with modern front-end interactions.
- **Vite** – Front-end build tool for fast development and asset bundling.

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
```

## 📄 Notes
