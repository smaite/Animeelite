# AnimeElite

A modern anime streaming platform with Firebase authentication and premium subscription features.

## Local Development

### Setup

1. Clone the repository
2. Set up a local server (using XAMPP, WAMP, or VS Code Live Server)
3. Open the project in your browser

### API Endpoints

When developing locally, you'll encounter CORS issues when trying to access the production API endpoints. To work around this, you can:

1. Use the mock API endpoint for local testing:
   - For PHP server: `http://localhost/path-to-project/mock_api.php?endpoint=get_anime_details`
   - For VS Code Live Server: You'll need to set up a PHP server separately

### Mock API Usage

The `mock_api.php` file provides mock responses for testing:

- **Anime Details**: `mock_api.php?endpoint=get_anime_details`
- **Subscription Status**: `mock_api.php?endpoint=subscription_status`
- **Coupon Validation**: `mock_api.php?endpoint=validate_coupon` (POST request with `couponCode` parameter)

### Local API Testing

For local development, you can modify the API URLs in the JavaScript files:

```javascript
// Example for player.js
function fetchAnimeDetails(animeId, seasonId, episodeId) {
    // For local testing
    let url = `mock_api.php?endpoint=get_anime_details&anime_id=${animeId}`;
    
    // For production
    // let url = `https://cdn.glorioustradehub.com/get_anime_details.php?anime_id=${animeId}`;
    
    // Rest of the function...
}
```

## Features

- **Authentication**: Email/password and social login via Firebase
- **Video Streaming**: Embedded video player with season and episode selection
- **Premium Content**: Subscription-based access to premium content
- **Coupon System**: Apply discount codes to subscriptions
- **Responsive Design**: Modern UI with Tailwind CSS
- **Dark Mode**: AMOLED-friendly dark theme

## Special Features

- **Special Coupon**: Use code `xsse3` for 100% discount (free premium access)
- **Offline Mode**: For development, the site will work without API access

## Troubleshooting

### CORS Errors

If you see CORS errors in the console:

1. The app will fall back to mock data for development
2. For production, ensure the server has proper CORS headers
3. Use the mock API for local testing

### Missing Images

If images fail to load, the app will automatically use placeholder images. 