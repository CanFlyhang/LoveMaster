const api = {
    // 指向 api 目录
    baseUrl: 'api',

    async request(endpoint, method = 'GET', body = null) {
        const options = {
            method,
            headers: {
                'Content-Type': 'application/json'
            }
        };

        if (body) {
            options.body = JSON.stringify(body);
        }

        // Endpoint conversion: /guest_login -> /guest_login.php
        // Remove leading slash if present
        let cleanEndpoint = endpoint.startsWith('/') ? endpoint.substring(1) : endpoint;
        
        // Handle query parameters
        let queryParams = '';
        if (cleanEndpoint.includes('?')) {
            const parts = cleanEndpoint.split('?');
            cleanEndpoint = parts[0];
            queryParams = '?' + parts[1];
        }

        // Map abstract endpoints to PHP files
        // e.g. "game/start" -> "game_start.php"
        // e.g. "user/me" -> "user_me.php"
        let phpFile = cleanEndpoint;
        if (!phpFile.endsWith('.php')) {
            phpFile = phpFile.replace('/', '_') + '.php';
        }
        
        const url = `${this.baseUrl}/${phpFile}${queryParams}`;
        
        console.log(`API Call: ${method} ${url}`);

        const response = await fetch(url, options);
        
        // Handle non-JSON response (e.g. PHP error output)
        const contentType = response.headers.get("content-type");
        if (!contentType || !contentType.includes("application/json")) {
             const text = await response.text();
             console.error('API Error (Non-JSON):', text);
             throw new Error('Server returned invalid response');
        }

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || 'Request failed');
        }

        return data;
    },

    get(endpoint) {
        return this.request(endpoint, 'GET');
    },

    post(endpoint, body) {
        return this.request(endpoint, 'POST', body);
    }
};
