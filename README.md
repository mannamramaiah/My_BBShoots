# My_BBShoots
# BBShoots — Professional Photography & Videography Platform

![BBShoots Logo](https://via.placeholder.com/150x50?text=BBShoots) <!-- Replace with actual logo URL -->

BBShoots is a complete web solution for a photography and videography production company. It includes a **public-facing website** where clients can learn about services, contact the team, and book sessions, as well as a **secure admin panel** for managing bookings, clients, and project workflows.

The system is built with a dark cinematic theme, custom cursor effects, and fully responsive design. It integrates with a PHP backend API and MySQL database (or PostgreSQL via Supabase) to handle data persistence, authentication, and email notifications.

---

## ✨ Features

### Public Website (`index.html`)
- **Hero Section** with animated grain effect and call-to-action.
- **Services Grid** showcasing various production packages (Wedding, College Festivals, Instagram Reels, etc.).
- **About Section** with company story and statistics.
- **Contact Form** for inquiries (integrated with backend email).
- **Booking Flow** – multi‑step form to collect client and event details, select a package, and submit a booking request.
- **Client Portal** – registered clients can log in to view their bookings, track project progress (with visual stage indicators), and access delivered video links.

### Admin Panel (`admin/index.html`)
- **Secure Login** – protected area with session-based authentication.
- **Overview Dashboard** – statistics cards showing total bookings, pending, confirmed, completed, active clients, and unread notifications.
- **Bookings Management** – sortable, filterable table with inline editing:
  - Change booking status (pending, confirmed, completed, cancelled, rejected).
  - Update project stage (scheduled, shooting, editing, review, completed) via dropdown or clickable stage buttons with progress bar.
  - Add/edit video links for client delivery.
  - Full edit modal for all fields (client info, event details, notes).
- **Client Management** – list all clients, suspend/restore/remove accounts.
- **Notifications** – real‑time feed of system events (new bookings, status changes, contact messages).
- **WhatsApp Integration** – one‑click button to message the admin (configurable number).

---

## 🛠️ Tech Stack

| Component        | Technology                                                                 |
|------------------|----------------------------------------------------------------------------|
| **Frontend**     | HTML5, CSS3 (custom variables, animations), vanilla JavaScript             |
| **Backend API**  | PHP (procedural) – REST‑style endpoints                                    |
| **Database**     | MySQL (or PostgreSQL via Supabase)                                         |
| **Email**        | PHPMailer (or any SMTP service)                                            |
| **Hosting**      | Frontend: [Vercel](https://vercel.com) (free) / Backend: [Coolify](https://coolify.io) on free Oracle VM / Database: [Supabase](https://supabase.com) (free tier) |
| **Version Control** | Git + GitHub                                                             |

---

## 🚀 Getting Started

### Prerequisites
- A local web server with PHP ≥ 7.4 and MySQL (e.g., XAMPP, MAMP, Laravel Valet).
- Git installed.
- (Optional) A [Supabase](https://supabase.com) account for cloud database.

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/my_bbshoots.git
   cd my_bbshoots
   
   


📁 Project Structure
 htdocs/
├── index.html          ← main website
├── admin/
│   └── index.html      ← admin panel
└── api/
    ├── config.php      ← updated with online DB details
    ├── index.php
    └── mailer.php
