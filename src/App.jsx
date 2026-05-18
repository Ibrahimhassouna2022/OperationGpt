import React, { useState, useEffect } from "react";
import { Container, Offcanvas } from "react-bootstrap";

// Components
import Sidebar from "./components/Layout/Sidebar";
import ChatWindow from "./components/Chat/ChatWindow";

// Styles
import "./assets/css/main.css";

function App() {
  // Global UI state controlling theme, language, and sidebar visibility
  const [isDarkMode, setIsDarkMode] = useState(false);
  const [language, setLanguage] = useState("en");
  const [showSidebar, setShowSidebar] = useState(false);

  // State management for current messages and active chat session tracking
  const [messages, setMessages] = useState([]);
  const [activeChatId, setActiveChatId] = useState(null);

  // Initializing chat history state from local storage if available
  const [chatHistory, setChatHistory] = useState(() => {
    const saved = localStorage.getItem("chat_history");
    return saved ? JSON.parse(saved) : [];
  });

  // Syncing chat history state with local storage on changes
  useEffect(() => {
    localStorage.setItem("chat_history", JSON.stringify(chatHistory));
  }, [chatHistory]);

  // Triggers new chat session and handles saving/updating logic to prevent duplicates
  const handleNewChat = () => {
    if (messages.length > 0) {
      if (activeChatId) {
        // Updates existing historical chat session with any new messages added
        setChatHistory((prev) =>
          prev.map((chat) =>
            chat.id === activeChatId ? { ...chat, data: messages } : chat,
          ),
        );
      } else {
        // Creates a completely new chat session entry
        const chatTitle = messages[0].text.substring(0, 20) + "...";
        setChatHistory((prev) => [
          { id: Date.now(), title: chatTitle, data: messages },
          ...prev,
        ]);
      }
    }
    // Resets the chat window and session ID for a fresh start
    setMessages([]);
    setActiveChatId(null);
  };

  // Loads a selected historical chat session into the active messages view
  const handleSelectChat = (chat) => {
    // Auto-saves the current active session before switching, to prevent data loss
    if (messages.length > 0 && !activeChatId) {
      const chatTitle = messages[0].text.substring(0, 20) + "...";
      setChatHistory((prev) => [
        { id: Date.now(), title: chatTitle, data: messages },
        ...prev,
      ]);
    } else if (messages.length > 0 && activeChatId) {
      setChatHistory((prev) =>
        prev.map((c) => (c.id === activeChatId ? { ...c, data: messages } : c)),
      );
    }

    // Loads the selected chat data
    setMessages(chat.data);
    setActiveChatId(chat.id);
    setShowSidebar(false); // Closes mobile sidebar if open during selection
  };

  // Determines layout direction (RTL/LTR) applied across the entire DOM
  const direction = language === "ar" ? "rtl" : "ltr";

  useEffect(() => {
    /*
      Sync UI configuration with root HTML element:
      - data-bs-theme: enables dynamic Bootstrap theming
      - dir: controls layout direction for RTL/LTR support
      - lang: improves accessibility and browser behavior
    */
    document.documentElement.setAttribute(
      "data-bs-theme",
      isDarkMode ? "dark" : "light",
    );
    document.documentElement.setAttribute("dir", direction);
    document.documentElement.setAttribute("lang", language);
  }, [isDarkMode, direction, language]);

  // Centralized state handlers passed to child components
  const toggleTheme = () => setIsDarkMode(!isDarkMode);
  const toggleLanguage = () => setLanguage(language === "ar" ? "en" : "ar");
  const handleSidebarClose = () => setShowSidebar(false);
  const handleSidebarShow = () => setShowSidebar(true);

  return (
    // Root layout container; relies on CSS classes for maintainable layout structure
    <div className="vh-100 bg-body text-body app-container">
      <Container fluid className="h-100 p-0 d-flex">
        {/* Desktop sidebar: statically rendered and controlled via CSS layout rules */}
        <div className="d-none d-md-block h-100 border-end border-secondary border-opacity-25 sidebar-desktop">
          <Sidebar
            isDarkMode={isDarkMode}
            toggleTheme={toggleTheme}
            language={language}
            toggleLanguage={toggleLanguage}
            onNewChat={handleNewChat}
            chatHistory={chatHistory}
            onSelectChat={handleSelectChat}
          />
        </div>

        {/* Mobile sidebar: rendered as Offcanvas with dynamic placement based on language direction */}
        <Offcanvas
          show={showSidebar}
          onHide={handleSidebarClose}
          placement={language === "ar" ? "end" : "start"}
          className="bg-body text-body border-0 shadow-lg sidebar-mobile"
        >
          <Offcanvas.Body className="p-0">
            <Sidebar
              isDarkMode={isDarkMode}
              toggleTheme={toggleTheme}
              language={language}
              toggleLanguage={toggleLanguage}
              isMobile={true}
              closeSidebar={handleSidebarClose}
              onNewChat={handleNewChat}
              chatHistory={chatHistory}
              onSelectChat={handleSelectChat}
            />
          </Offcanvas.Body>
        </Offcanvas>

        {/* Main content area: hosts ChatWindow and serves as primary integration point with backend interactions */}
        <div className="flex-grow-1 d-flex flex-column h-100">
          <ChatWindow
            language={language}
            toggleSidebar={handleSidebarShow}
            messages={messages}
            setMessages={setMessages}
          />
        </div>
      </Container>
    </div>
  );
}

export default App;
