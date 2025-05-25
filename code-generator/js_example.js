async function callAPI({ id }, data = {}) {
    const url = new URL(`http://example.com/users/${id}`);
    url.pathname = url.pathname.replace(`{id}`, encodeURIComponent(id));

    const options = {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    };

    // Add query parameters
    const params = new URLSearchParams(data);
    url.search = params.toString();

    try {
        const response = await fetch(url, options);
        const data = await response.json();
        console.log('Response:', data);
        return data;
    } catch (error) {
        console.error('Error:', error);
        throw error;
    }
}