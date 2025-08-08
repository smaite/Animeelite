// Mock API responses
const mockResponses = {
    // Get anime details response
    'get_anime_details': {
        success: true,
        anime: {
            id: 1,
            title: 'Demon Slayer',
            description: 'A family is attacked by demons and only two members survive - Tanjiro and his sister Nezuko, who is turning into a demon slowly. Tanjiro sets out to become a demon slayer to avenge his family and cure his sister.',
            cover_image: 'https://m.media-amazon.com/images/M/MV5BNmQ5Zjg2ZTYtMGZmNC00M2Y3LTgwZGQtYmQ3NWI5MDdhZWNjXkEyXkFqcGc@._V1_.jpg',
            release_year: '2019',
            genres: 'Action, Fantasy',
            status: 'ongoing'
        },
        seasons: [
            {
                id: 1,
                season_number: 1,
                title: 'Demon Slayer: Kimetsu no Yaiba',
                description: 'First season of Demon Slayer',
                cover_image: '',
                release_year: '2019',
                episodes: [
                    {
                        id: 1,
                        episode_number: 1,
                        title: 'Cruelty',
                        description: 'Tanjiro Kamado is a kind-hearted and intelligent boy who lives with his family in the mountains. He became his family\'s breadwinner after his father\'s death.',
                        thumbnail: '',
                        video_url: 'https://www.youtube.com/embed/VQGCKyvzIM4',
                        duration: '24',
                        is_premium: 0
                    },
                    {
                        id: 2,
                        episode_number: 2,
                        title: 'Trainer Sakonji Urokodaki',
                        description: 'Tanjiro encounters a demon slayer named Giyu Tomioka, who is impressed by Tanjiro\'s resolve and tells him to find a man named Sakonji Urokodaki.',
                        thumbnail: '',
                        video_url: 'https://www.youtube.com/embed/VQGCKyvzIM4',
                        duration: '24',
                        is_premium: 0
                    }
                ]
            },
            {
                id: 2,
                season_number: 2,
                title: 'Demon Slayer: Entertainment District Arc',
                description: 'Second season of Demon Slayer',
                cover_image: '',
                release_year: '2021',
                episodes: [
                    {
                        id: 3,
                        episode_number: 1,
                        title: 'Sound Hashira Tengen Uzui',
                        description: 'Tanjiro and his friends accompany the Sound Hashira Tengen Uzui to investigate disappearances in the Entertainment District.',
                        thumbnail: '',
                        video_url: 'https://www.youtube.com/embed/VQGCKyvzIM4',
                        duration: '24',
                        is_premium: 1
                    }
                ]
            }
        ],
        current_episode: {
            id: 1,
            episode_number: 1,
            title: 'Cruelty',
            description: 'Tanjiro Kamado is a kind-hearted and intelligent boy who lives with his family in the mountains. He became his family\'s breadwinner after his father\'s death.',
            thumbnail: '',
            video_url: 'https://www.youtube.com/embed/VQGCKyvzIM4',
            duration: '24',
            is_premium: 0,
            season_id: 1,
            season_number: 1,
            anime_id: 1,
            anime_title: 'Demon Slayer'
        }
    },
    
    // Subscription status response
    'subscription_status': {
        success: true,
        subscription: {
            status: 'active',
            plan: 'premium',
            expiresAt: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString(),
            startedAt: new Date().toISOString()
        }
    },
    
    // Validate coupon response
    'validate_coupon': {
        success: true,
        discount: 25,
        message: 'Coupon applied successfully!'
    },
    
    // Special coupon response
    'validate_coupon_xsse3': {
        success: true,
        discount: 100,
        message: 'Special coupon applied! You now have free access to all premium content.'
    },
    
    // Featured anime response
    'get_featured_anime': {
        success: true,
        anime: [
            {
                id: 1,
                title: 'Demon Slayer',
                cover_image: 'https://m.media-amazon.com/images/M/MV5BNmQ5Zjg2ZTYtMGZmNC00M2Y3LTgwZGQtYmQ3NWI5MDdhZWNjXkEyXkFqcGc@._V1_.jpg',
                release_year: '2019',
                genres: 'Action, Fantasy',
                is_premium: false
            },
            {
                id: 2,
                title: 'Attack on Titan',
                cover_image: 'https://flxt.tmsimg.com/assets/p10701949_b_v8_ah.jpg',
                release_year: '2013',
                genres: 'Action, Drama',
                is_premium: true
            },
            {
                id: 3,
                title: 'My Hero Academia',
                cover_image: 'https://m.media-amazon.com/images/M/MV5BOGZmYjdjN2UtNjAwZi00YmEyLWFhNTEtNjM1MTY0MmY4NDNlXkEyXkFqcGdeQXVyMTA1NjQyNjkw._V1_FMjpg_UX1000_.jpg',
                release_year: '2016',
                genres: 'Action, Comedy',
                is_premium: false
            },
            {
                id: 4,
                title: 'Jujutsu Kaisen',
                cover_image: 'https://m.media-amazon.com/images/M/MV5BMTMwMDM3NjQtODFjNi00ZGRhLTg5Y2YtYWIzMzM1N2E3YzMwXkEyXkFqcGdeQXVyNjAwNDUxODI@._V1_.jpg',
                release_year: '2020',
                genres: 'Action, Supernatural',
                is_premium: true
            }
        ]
    },
    
    // Latest episodes response
    'get_latest_episodes': {
        success: true,
        episodes: [
            {
                id: 1,
                anime_id: 1,
                season_id: 1,
                anime_title: 'Demon Slayer',
                season_number: 1,
                episode_number: 1,
                title: 'Cruelty',
                thumbnail: '',
                duration: '24',
                is_premium: false
            },
            {
                id: 2,
                anime_id: 1,
                season_id: 1,
                anime_title: 'Demon Slayer',
                season_number: 1,
                episode_number: 2,
                title: 'Trainer Sakonji Urokodaki',
                thumbnail: '',
                duration: '24',
                is_premium: false
            },
            {
                id: 3,
                anime_id: 2,
                season_id: 1,
                anime_title: 'Attack on Titan',
                season_number: 1,
                episode_number: 1,
                title: 'To You, 2,000 Years From Now',
                thumbnail: '',
                duration: '24',
                is_premium: true
            },
            {
                id: 4,
                anime_id: 3,
                season_id: 1,
                anime_title: 'My Hero Academia',
                season_number: 1,
                episode_number: 1,
                title: 'Izuku Midoriya: Origin',
                thumbnail: '',
                duration: '24',
                is_premium: false
            }
        ]
    }
};

// Get query parameters
const urlParams = new URLSearchParams(window.location.search);
const endpoint = urlParams.get('endpoint');
const couponCode = urlParams.get('couponCode') || '';

// Handle special coupon case
if (endpoint === 'validate_coupon' && couponCode === 'xsse3') {
    document.write(JSON.stringify(mockResponses['validate_coupon_xsse3']));
} 
// Return mock response for the requested endpoint
else if (mockResponses[endpoint]) {
    document.write(JSON.stringify(mockResponses[endpoint]));
} 
// Return error for unknown endpoint
else {
    document.write(JSON.stringify({
        success: false,
        message: 'Unknown endpoint'
    }));
} 