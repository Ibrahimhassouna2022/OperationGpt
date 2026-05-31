import axios from 'axios';

// Base URL for OperationGPT backend API endpoints
const API_BASE_URL = '/operation-gpt';
/**
 * Sends a user command to the backend AI processing endpoint.
 * @param {string} command - Natural language input from the user.
 * @param {string} language - UI language (used by backend for response localization).
 * @returns {Promise<object>} - Normalized response payload from backend.
 */
export const sendCommandToAI = async (command, language = 'en') => {
  try {
    // 1. Fetch the Laravel CSRF token from the meta tag if available in the DOM
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    // 2. Retrieve the auth token from LocalStorage (useful for JWT/Sanctum API setups)
    const apiToken = localStorage.getItem('token');

    const response = await axios.post(
      `${API_BASE_URL}/chat`,
      {
        message: command,
        language: language // Pass the dynamic UI language to the backend for localization
      },
      {
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          // Attach the CSRF token for Blade-based environments
          ...(csrfToken && { 'X-CSRF-TOKEN': csrfToken }),
          // Attach the API token if the user is authenticated via local storage
          ...(apiToken && { 'Authorization': `Bearer ${apiToken}` })
        }
      }
    );

    // Return the raw response payload (expected to contain type, message, data, etc.)
    return response.data; 

  } catch (error) {
    console.error("OperationGPT API Error:", error);
    
    // Fallback error message based on UI language if the backend does not provide one
    const fallbackErrorMsg = language === 'ar' 
        ? "عذراً، حدث خطأ في الاتصال بالخادم. يرجى المحاولة لاحقاً." 
        : "Sorry, a connection error occurred. Please try again later.";
        
    // Normalize the error output for UI consumption
    throw new Error(error.response?.data?.message || fallbackErrorMsg);
  }
};