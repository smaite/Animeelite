# AnimeElite Streaming Website

An anime streaming website built with HTML, Tailwind CSS, and JavaScript. Features a modern UI, video player, user authentication, and subscription system.

## Features

- **User Authentication**: Sign up, login, and persistent user sessions using Firebase Authentication.
- **Video Player**: Stream anime episodes with season and episode navigation.
- **Subscription System**: Free, Premium, and Ultimate subscription tiers with various features.
- **Coupon System**: Apply discount coupons to subscriptions.
- **AMOLED UI**: Dark, sleek interface designed for OLED displays.
- **Responsive Design**: Works on desktop, tablet, and mobile devices.
- **Favorites**: Save anime to your favorites list.
- **Watch History**: Track your watched episodes.
- **Search Functionality**: Find your favorite anime.

## Pages

- **Home**: Featured anime and latest episodes.
- **Player**: Video player with episode navigation and comments.
- **Login/Signup**: User authentication pages.
- **Subscription**: Premium subscription plans.
- **Profile**: User profile management.
- **Favorites**: User's favorite anime.
- **History**: User's watch history.

## Project Structure

```
AnimeElite/
│
├── index.html               # Main landing page
├── css/
│   └── styles.css           # Custom CSS styles
│
├── js/
│   ├── main.js              # Main JavaScript functions
│   ├── firebase-config.js   # Firebase configuration
│   ├── auth.js              # Authentication logic
│   ├── player.js            # Video player functionality
│   └── subscription.js      # Subscription handling
│
├── pages/
│   ├── login.html           # Login page
│   ├── signup.html          # Sign-up page
│   ├── player.html          # Video player page
│   └── subscription.html    # Premium subscriptions page
│
└── server/                  # Backend PHP files (to be deployed separately)
    ├── subscription_status.php   # Check subscription status API
    ├── validate_coupon.php       # Coupon validation API
    ├── db_setup.sql              # Database setup script
    │
    └── admin/                    # Admin panel
        ├── subscription_management.php   # Manage subscriptions
        │
        └── ajax/                        # Admin AJAX endpoints
            ├── subscription_history.php # Get user subscription history
            └── deactivate_coupon.php    # Deactivate a coupon
```

## Technology Stack

- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **CSS Framework**: Tailwind CSS
- **Authentication**: Firebase Authentication
- **Database**: Firebase Firestore, MySQL (for subscription system)
- **Video Playback**: HTML5 iframe embedding

## Getting Started

1. Clone the repository
2. Configure your Firebase project and update `firebase-config.js` with your credentials
3. Host the frontend files on a static web server
4. Set up MySQL database using `server/db_setup.sql`
5. Deploy PHP backend files to a PHP-capable server

## Note

This is a frontend prototype. In a production environment, you should:

1. Implement proper backend services and APIs
2. Set up secure authentication flows
3. Add proper error handling and validation
4. Optimize assets for production
5. Implement proper video streaming security

## Database Setup

To set up the MySQL database for the subscription system:

1. Create a MySQL database
2. Run the `server/db_setup.sql` script to create the necessary tables
3. Update database credentials in the PHP files in the `server` directory

## Backend Deployment

The `server` directory contains PHP files that should be deployed on a PHP-capable server with MySQL access. These files handle:

- Subscription status checking
- Coupon validation
- Admin functionality for managing subscriptions

Make sure to update the database connection settings in each PHP file to match your MySQL setup. 