import axios from 'axios';

// Base URL for OperationGPT backend API endpoints
const API_BASE_URL ='/api/operation-gpt'; 

/**
 * Sends a user command to the backend AI processing endpoint.
 * @param {string} command - Natural language input from the user.
 * @param {string} language - UI language (used by backend for response localization).
 * @returns {Promise<object>} - Normalized response payload from backend.
 */
export const sendCommandToAI = async (command, language = 'en') => {
  try {
    const response = await axios.post(
      `${API_BASE_URL}/chat`,
      {
        message: command,
        language: language // Passed to backend for response localization handling
      },
      {
        headers: {
          'Content-Type': 'application/json',
          // JWT token retrieved from localStorage for authenticated API access
          'Authorization': `Bearer ${localStorage.getItem('token')}` 
        }
      }
    );

    // Return raw response payload (expected to contain reply/message field)
    return response.data; 

  } catch (error) {
    console.error("OperationGPT API Error:", error);
    
    // Fallback error message based on UI language if backend does not provide one
    const fallbackErrorMsg = language === 'ar' 
        ? "عذراً، حدث خطأ في الاتصال بالخادم. يرجى المحاولة لاحقاً." 
        : "Sorry, a connection error occurred. Please try again later.";
        
    // Normalize error output for UI consumption
    throw new Error(error.response?.data?.message || fallbackErrorMsg);
  }
};