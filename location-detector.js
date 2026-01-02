/**
 * Location Detector for nyamanhippo - ENHANCED WITH DEBUGGING
 * Fixed version with better error handling
 */

class LocationDetector {
    constructor() {
        this.userLocation = null;
        this.locationStatus = 'idle';
        this.callbacks = [];
        this.debugMode = true; // Enable debug logging
    }

    log(message, type = 'info') {
        if (!this.debugMode) return;
        
        const emoji = {
            info: 'ℹ️',
            success: '✅',
            error: '❌',
            warning: '⚠️',
            debug: '🔍'
        };
        
        console.log(`${emoji[type] || '📍'} LocationDetector: ${message}`);
    }

    init(onSuccess = null, onError = null) {
        this.log('Initializing location detector...', 'debug');
        
        if (onSuccess) this.callbacks.push({ success: onSuccess, error: onError });
        
        // Check geolocation support
        if (!navigator.geolocation) {
            this.log('Geolocation NOT supported by browser', 'error');
            this.handleError('Geolocation is not supported by your browser');
            return;
        }
        
        this.log('Geolocation API available', 'success');
        
        // Check for cached location
        const cachedLocation = this.getCachedLocation();
        if (cachedLocation && this.isCacheValid(cachedLocation)) {
            this.log(`Using cached location: ${cachedLocation.city}`, 'info');
            this.userLocation = cachedLocation;
            this.locationStatus = 'success';
            this.triggerCallbacks('success');
            return;
        }
        
        if (cachedLocation) {
            this.log('Cached location expired, detecting fresh location...', 'warning');
        } else {
            this.log('No cached location found, detecting...', 'info');
        }
        
        // Detect fresh location
        this.detectLocation();
    }

    detectLocation() {
        this.locationStatus = 'loading';
        this.log('Starting geolocation detection...', 'debug');
        
        // Try high accuracy first with longer timeout
        const options = {
            enableHighAccuracy: true,
            timeout: 15000, // 15 seconds
            maximumAge: 0 // Don't accept cached position
        };
        
        this.log(`Requesting position with options: ${JSON.stringify(options)}`, 'debug');
        
        navigator.geolocation.getCurrentPosition(
            (position) => {
                this.log(`Position obtained! Lat: ${position.coords.latitude}, Lon: ${position.coords.longitude}`, 'success');
                this.handleSuccess(position);
            },
            (error) => {
                this.log(`High accuracy failed: ${error.message} (Code: ${error.code})`, 'warning');
                // Try low accuracy on timeout
                if (error.code === error.TIMEOUT) {
                    this.log('Retrying with low accuracy...', 'info');
                    this.detectLocationLowAccuracy();
                } else {
                    this.handleGeolocationError(error);
                }
            },
            options
        );
    }

    detectLocationLowAccuracy() {
        const options = {
            enableHighAccuracy: false, // Faster but less accurate
            timeout: 10000, // 10 seconds
            maximumAge: 300000 // Accept cached position up to 5 minutes
        };
        
        this.log(`Trying low accuracy with options: ${JSON.stringify(options)}`, 'debug');
        
        navigator.geolocation.getCurrentPosition(
            (position) => {
                this.log(`Low accuracy position obtained! Lat: ${position.coords.latitude}, Lon: ${position.coords.longitude}`, 'success');
                this.handleSuccess(position);
            },
            (error) => {
                this.log(`Low accuracy also failed: ${error.message}`, 'error');
                this.handleGeolocationError(error);
            },
            options
        );
    }

    async handleSuccess(position) {
        const { latitude, longitude, accuracy } = position.coords;
        
        this.log(`Accuracy: ${accuracy} meters`, 'info');
        this.log('Starting reverse geocoding...', 'debug');
        
        try {
            // Use OpenStreetMap Nominatim API
            const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}&zoom=18&addressdetails=1`;
            this.log(`Fetching: ${url}`, 'debug');
            
            const response = await fetch(url, {
                headers: {
                    'User-Agent': 'nyamanhippo/1.0'
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            this.log(`Geocoding response: ${JSON.stringify(data.address)}`, 'debug');
            
            // Extract location details
            const address = data.address || {};
            this.userLocation = {
                latitude,
                longitude,
                accuracy,
                address: this.formatAddress(address),
                city: address.city || address.town || address.village || address.county || 'Your Location',
                state: address.state || 'Malaysia',
                country: address.country || 'Malaysia',
                postcode: address.postcode || '',
                displayName: data.display_name || '',
                timestamp: Date.now(),
                isDefault: false
            };

            this.log(`Location set: ${this.userLocation.city}, ${this.userLocation.state}`, 'success');
            
            // Cache the location
            this.cacheLocation(this.userLocation);
            
            this.locationStatus = 'success';
            this.triggerCallbacks('success');
            
        } catch (error) {
            this.log(`Geocoding error: ${error.message}`, 'error');
            
            // Use coordinates without city name
            this.userLocation = {
                latitude,
                longitude,
                accuracy,
                address: `${latitude.toFixed(4)}, ${longitude.toFixed(4)}`,
                city: 'Your Location',
                state: 'Malaysia',
                country: 'Malaysia',
                postcode: '',
                displayName: `${latitude.toFixed(4)}, ${longitude.toFixed(4)}`,
                timestamp: Date.now(),
                isDefault: false
            };
            
            this.cacheLocation(this.userLocation);
            this.locationStatus = 'success';
            this.triggerCallbacks('success');
        }
    }

    handleGeolocationError(error) {
        let errorMessage = '';
        let suggestion = '';
        
        this.log(`Geolocation error code: ${error.code}`, 'error');
        
        switch(error.code) {
            case 1: // PERMISSION_DENIED
                errorMessage = 'Location permission denied';
                suggestion = 'Please allow location access in your browser';
                this.log('User denied location permission', 'error');
                break;
            case 2: // POSITION_UNAVAILABLE
                errorMessage = 'Location unavailable';
                suggestion = 'Location information is not available';
                this.log('Position unavailable from device', 'error');
                break;
            case 3: // TIMEOUT
                errorMessage = 'Location request timed out';
                suggestion = 'Could not detect location in time';
                this.log('Request timed out', 'error');
                break;
            default:
                errorMessage = 'Unknown location error';
                suggestion = 'An unknown error occurred';
                this.log(`Unknown error: ${error.message}`, 'error');
        }
        
        this.log(`Using default location (Kuala Lumpur)`, 'warning');
        this.useDefaultLocation();
    }

    useDefaultLocation() {
        this.userLocation = {
            latitude: 3.139,
            longitude: 101.6869,
            accuracy: 0,
            address: 'Kuala Lumpur City Centre',
            city: 'Kuala Lumpur',
            state: 'Federal Territory',
            country: 'Malaysia',
            postcode: '50088',
            displayName: 'Kuala Lumpur, Malaysia',
            timestamp: Date.now(),
            isDefault: true
        };

        this.cacheLocation(this.userLocation);
        this.locationStatus = 'success';
        this.triggerCallbacks('success');
    }

    handleError(message) {
        this.locationStatus = 'error';
        this.log(message, 'error');
        this.triggerCallbacks('error', message);
    }

    formatAddress(address) {
        const parts = [];
        
        if (address.road) parts.push(address.road);
        if (address.suburb || address.neighbourhood) {
            parts.push(address.suburb || address.neighbourhood);
        }
        if (address.city || address.town) {
            parts.push(address.city || address.town);
        }
        if (address.postcode) parts.push(address.postcode);
        
        return parts.join(', ') || 'Address not available';
    }

    cacheLocation(location) {
        try {
            localStorage.setItem('nyamanhippo_location', JSON.stringify(location));
            this.log(`Location cached: ${location.city}`, 'success');
        } catch (e) {
            this.log(`Failed to cache location: ${e.message}`, 'warning');
        }
    }

    getCachedLocation() {
        try {
            const cached = localStorage.getItem('nyamanhippo_location');
            if (cached) {
                const location = JSON.parse(cached);
                this.log(`Found cached location: ${location.city}`, 'debug');
                return location;
            }
            return null;
        } catch (e) {
            this.log(`Failed to read cache: ${e.message}`, 'warning');
            return null;
        }
    }

    isCacheValid(location) {
        if (!location || !location.timestamp) {
            this.log('Cache has no timestamp', 'debug');
            return false;
        }
        
        const oneHour = 60 * 60 * 1000;
        const age = Date.now() - location.timestamp;
        const isValid = age < oneHour;
        
        this.log(`Cache age: ${Math.round(age / 1000 / 60)} minutes (Valid: ${isValid})`, 'debug');
        return isValid;
    }

    triggerCallbacks(type, data = null) {
        this.log(`Triggering ${type} callbacks (${this.callbacks.length} registered)`, 'debug');
        
        this.callbacks.forEach(callback => {
            if (type === 'success' && callback.success) {
                callback.success(this.userLocation);
            } else if (type === 'error' && callback.error) {
                callback.error(data || 'Location detection failed');
            }
        });
    }

    getLocation() {
        return this.userLocation;
    }

    getStatus() {
        return this.locationStatus;
    }

    clearCache() {
        try {
            localStorage.removeItem('nyamanhippo_location');
            this.log('Cache cleared', 'info');
        } catch (e) {
            this.log(`Failed to clear cache: ${e.message}`, 'warning');
        }
    }

    requestPermission(onSuccess, onError) {
        this.log('Manual permission request', 'info');
        
        // Clear old cache to force fresh detection
        this.clearCache();
        
        this.callbacks = []; // Clear old callbacks
        this.init(onSuccess, onError);
    }
}

// Create global instance
const locationDetector = new LocationDetector();

// Initialize function
function initLocationDetector(options = {}) {
    const {
        showNotification = true,
        autoDetect = true,
        onSuccess = null,
        onError = null
    } = options;

    console.log('🔍 initLocationDetector called with options:', options);

    if (autoDetect) {
        locationDetector.init(
            (location) => {
                console.log('✅ Location detected:', location);
                if (onSuccess) onSuccess(location);
            },
            (error) => {
                console.error('❌ Location error:', error);
                if (onError) onError(error);
            }
        );
    }

    return locationDetector;
}

// Make available globally
window.locationDetector = locationDetector;
window.initLocationDetector = initLocationDetector;

console.log('✅ Location Detector script loaded and ready');