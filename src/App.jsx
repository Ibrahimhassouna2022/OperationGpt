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

  // State management for chat sessions (Lifting State Up)
  const [messages, setMessages] = useState([]);
  const [chatHistory, setChatHistory] = useState([]);

  // Triggers new chat session by saving current messages to history and clearing the chat window
  const handleNewChat = () => {
    if (messages.length > 0) {
      const chatTitle = messages[0].text.substring(0, 20) + "...";
      setChatHistory((prev) => [
        { id: Date.now(), title: chatTitle, data: messages },
        ...prev,
      ]);
      setMessages([]);
    }
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
