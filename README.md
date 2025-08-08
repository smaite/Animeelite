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

For local development with VS Code Live Server (which doesn't support PHP), we use a JavaScript-based mock API:

- **`mock_api.js`**: A JavaScript file that returns mock data for all API endpoints
- **`mock_api.php`**: A PHP version that requires a PHP server (like XAMPP or WAMP)

The application automatically detects if it's running on localhost and uses the appropriate mock API.

#### Using the JavaScript Mock API:

Simply access endpoints like this:
```
mock_api.js?endpoint=get_anime_details&anime_id=1
```

Available endpoints:
- `get_anime_details`: Returns details for a specific anime
- `subscription_status`: Returns mock subscription data
- `validate_coupon`: Validates coupon codes
- `get_featured_anime`: Returns featured anime list
- `get_latest_episodes`: Returns latest episodes list

### Local API Testing

For local development, the application automatically uses the JavaScript mock API when running on localhost:

```javascript
// Example for player.js
function fetchAnimeDetails(animeId, seasonId, episodeId) {
    // Determine if we should use local mock API or production API
    const useMockApi = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
    
    // Build URL with parameters
    let url;
    if (useMockApi) {
        // Local development - use mock API
        const isInSubdir = window.location.pathname.includes('/pages/');
        const mockApiPath = isInSubdir ? '../mock_api.js' : 'mock_api.js';
        url = `${mockApiPath}?endpoint=get_anime_details&anime_id=${animeId}`;
    } else {
        // Production - use real API
        url = `https://cdn.glorioustradehub.com/get_anime_details.php?anime_id=${animeId}`;
    }
    
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

### Tailwind CDN Warning

The current implementation uses Tailwind CSS via CDN, which is not recommended for production. For a production deployment:

1. Install Tailwind CSS as a PostCSS plugin: `npm install -D tailwindcss postcss autoprefixer`
2. Initialize Tailwind: `npx tailwindcss init`
3. Configure your build process to compile the CSS
4. Replace the CDN link with your compiled CSS file

See the [Tailwind CSS Installation Guide](https://tailwindcss.com/docs/installation) for more details.