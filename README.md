# RoyalNest Hotel & Restaurant Management System

Welcome to the RoyalNest Hotel & Restaurant Management System! This web application provides a complete solution for managing hotel rooms, restaurant menus, bookings, and administrative tasks for luxury resorts.

## Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Screenshots](#screenshots)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Technologies Used](#technologies-used)
- [Folder Structure](#folder-structure)
- [Contributing](#contributing)
- [License](#license)

## Overview

RoyalNest is designed for both guests and administrators. Guests can browse rooms, view details, and make bookings, while administrators can manage rooms, food items, and monitor resort operations through a modern dashboard.

## Features

- **User Portal**

  - Browse and filter luxury rooms and suites
  - View detailed room information and amenities
  - Make reservations and manage bookings
  - Explore restaurant menu and order food

- **Admin Dashboard**

  - Add, edit, and delete rooms and food items
  - Upload images for rooms and menu items
  - View statistics (available/booked/maintenance rooms)
  - Responsive and intuitive interface

- **General**
  - Modern, mobile-friendly design
  - Secure file uploads
  - Oracle database integration

## Screenshots

> _Add screenshots of the dashboard, room listings, and menu management here._

## Installation

1. **Clone the repository:**
   ```bash
   git clone https://github.com/yourusername/royalnest-hotel.git
   ```
2. **Move the project to your web server directory:**
   - For XAMPP: `c:\xampp\htdocs\Hotel-Restaurant`
3. **Create the required database tables in Oracle.**
   - See `/Hotel-Restaurant/config/connect.php` for connection details.
4. **Set up folders for uploads:**
   - `uploads/rooms/`
   - `uploads/food/`
5. **Start your local server and access:**
   ```
   http://localhost/Hotel-Restaurant/
   ```

## Configuration

- Edit `config/connect.php` to set your Oracle DB credentials.
- Ensure PHP OCI8 extension is enabled.

## Usage

- **Guests:** Visit the homepage to browse rooms and make bookings.
- **Admins:** Log in to the admin dashboard to manage rooms and menu items.

## Technologies Used

- **Backend:** PHP (OCI8 for Oracle DB)
- **Frontend:** HTML, CSS, JavaScript
- **Icons:** Font Awesome
- **Web Server:** Apache (XAMPP recommended)

## Folder Structure

```
Hotel-Restaurant/
├── admin/
├── assets/
│   ├── Css/
│   ├── Js/
│   └── img/
├── config/
├── include/
├── uploads/
│   ├── rooms/
│   └── food/
├── user/
└── index.php
```

## Contributing

Contributions are welcome! Please fork the repository and submit a pull request.

## License

This project is for educational and demonstration purposes only.
