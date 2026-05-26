import React from "react";
import { Button, Nav } from "react-bootstrap";
import {
  FaPlus,
  FaRegCommentDots,
  FaMoon,
  FaSun,
  FaGlobe,
  FaRobot,
  FaCog,
} from "react-icons/fa";

import "../../assets/css/sidebar.css";

// Receives onNewChat, chatHistory, and onSelectChat props from the App component
const Sidebar = ({
  isDarkMode,
  toggleTheme,
  language,
  toggleLanguage,
  onNewChat,
  chatHistory,
  onSelectChat,
}) => {
  // Extracts the username and role from the Laravel Blade environment
  const userName = window.AppUser?.name || "User Name";
  const userRole =
    window.AppUser?.role || (language === "ar" ? "مستخدم" : "User");
  const t = {
    title: "OperationGPT",
    subtitle: language === "ar" ? "مساعدك الذكي" : "AI Assistant",
    newChat: language === "ar" ? "محادثة جديدة" : "New Chat",
    recent: language === "ar" ? "الأخيرة" : "Recent",
    settings: language === "ar" ? "الإعدادات" : "Settings",
    darkMode: language === "ar" ? "الوضع الليلي" : "Dark Mode",
    lightMode: language === "ar" ? "الوضع النهاري" : "Light Mode",
    langToggle: language === "ar" ? "English (LTR)" : "العربية (RTL)",
  };

  return (
    <div
      className={`d-flex flex-column h-100 p-3 ${
        isDarkMode ? "bg-dark text-white" : "bg-body-tertiary text-body"
      }`}
    >
      <div className="d-flex align-items-center mb-4 px-2 mt-2">
        <FaRobot className="text-primary fs-3 me-2" />
        <div>
          <h5 className="mb-0 fw-bold">{t.title}</h5>
          <small className="text-muted sidebar-subtitle">{t.subtitle}</small>
        </div>
      </div>

      {/* Binds the new chat button to the handler function */}
      <Button
        variant="primary"
        onClick={onNewChat}
        className="w-100 mb-4 d-flex align-items-center justify-content-center rounded-3 shadow-sm py-2"
      >
        <FaPlus className="me-2" /> <span className="fw-bold">{t.newChat}</span>
      </Button>

      <div className="mb-2 px-2 text-secondary fw-bold sidebar-section-title">
        {t.recent}
      </div>

      {/* Renders the list of chat sessions with click event handlers to view history */}
      <Nav className="flex-column mb-auto overflow-auto">
        {chatHistory && chatHistory.length > 0 ? (
          chatHistory.map((chat) => (
            <Nav.Link
              key={chat.id}
              href="#"
              onClick={(e) => {
                e.preventDefault();
                onSelectChat(chat);
              }}
              className={`d-flex align-items-center rounded mb-1 px-3 py-2 sidebar-link-faded ${
                isDarkMode ? "text-light" : "text-dark"
              }`}
            >
              <FaRegCommentDots className="me-2 text-muted" />
              <span className="text-truncate" style={{ maxWidth: "180px" }}>
                {chat.title}
              </span>
            </Nav.Link>
          ))
        ) : (
          <div className="text-muted small px-3 py-2 text-center">
            {language === "ar" ? "لا توجد محادثات سابقة" : "No recent chats"}
          </div>
        )}
      </Nav>

      <div className="mt-auto">
        <Button
          variant="link"
          className={`w-100 d-flex align-items-center mb-3 text-decoration-none px-3 ${
            isDarkMode ? "text-light" : "text-dark"
          }`}
        >
          <FaCog className="me-3 text-secondary" />
          {t.settings}
        </Button>

        <div className="pt-3 border-top border-secondary border-opacity-25">
          <Button
            variant="link"
            className={`w-100 d-flex align-items-center mb-1 text-decoration-none px-3 ${
              isDarkMode ? "text-light" : "text-dark"
            }`}
            onClick={toggleLanguage}
          >
            <FaGlobe className="me-3 text-secondary" />
            {t.langToggle}
          </Button>

          <Button
            variant="link"
            className={`w-100 d-flex align-items-center mb-3 text-decoration-none px-3 ${
              isDarkMode ? "text-light" : "text-dark"
            }`}
            onClick={toggleTheme}
          >
            {isDarkMode ? (
              <FaSun className="me-3 text-secondary" />
            ) : (
              <FaMoon className="me-3 text-secondary" />
            )}
            {isDarkMode ? t.lightMode : t.darkMode}
          </Button>

          {/* Displays the dynamic username and role */}
          <div className="d-flex align-items-center bg-secondary bg-opacity-10 p-2 rounded-3 mt-2">
            <div className="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center me-2 user-avatar">
              {userName.substring(0, 2).toUpperCase()}
            </div>
            <div>
              <strong
                className="d-block user-name text-truncate"
                style={{ maxWidth: "120px" }}
              >
                {userName}
              </strong>
              <small className="text-muted user-role">{userRole}</small>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Sidebar;
