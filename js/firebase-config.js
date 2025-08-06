// Firebase configuration
const firebaseConfig = {
  apiKey: "AIzaSyAKub2UWzj08vywPYuQ1SHNruMNUa4mVAw",
  authDomain: "dbs1-b8402.firebaseapp.com",
  databaseURL: "https://dbs1-b8402-default-rtdb.asia-southeast1.firebasedatabase.app",
  projectId: "dbs1-b8402",
  storageBucket: "dbs1-b8402.firebasestorage.app",
  messagingSenderId: "149677040378",
  appId: "1:149677040378:web:07b14d473f0559021cdf43",
  measurementId: "G-ZZ2ZEPPJE5"
};

// Initialize Firebase
firebase.initializeApp(firebaseConfig);

// Auth reference
const auth = firebase.auth();
const db = firebase.firestore();

// Export for use in other files
const getCurrentUser = () => {
  return auth.currentUser;
};

const isLoggedIn = () => {
  return !!auth.currentUser;
}; 