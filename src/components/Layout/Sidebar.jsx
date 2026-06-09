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
  FaSignOutAlt,
} from "react-icons/fa";

import "../../assets/css/sidebar.css";

const Sidebar = ({
  isDarkMode,
  toggleTheme,
  language = "en",
  toggleLanguage,
  onNewChat,
  chatHistory,
  onSelectChat,
}) => {
  // Extract user details dynamically from Laravel's global scope
  const userName = window.AppUser?.name || "User Name";
  const userRole =
    window.AppUser?.role || (language === "ar" ? "مستخدم" : "User");

  // UI Text Dictionary for localization
  const t = {
    title: "OperationGPT",
    subtitle: language === "ar" ? "مساعدك الذكي" : "AI Assistant",
    newChat: language === "ar" ? "محادثة جديدة" : "New Chat",
    recent: language === "ar" ? "الأخيرة" : "Recent",
    settings: language === "ar" ? "الإعدادات" : "Settings",
    darkMode: language === "ar" ? "الوضع الليلي" : "Dark Mode",
    lightMode: language === "ar" ? "الوضع النهاري" : "Light Mode",
    langToggle: language === "ar" ? "English (LTR)" : "العربية (RTL)",
    // Updated text to reflect "Exit Chat" instead of full "Logout"
    exitChat: language === "ar" ? "الخروج من الدردشة" : "Exit Chat",
  };

  /**
   * Exits the chat interface and navigates the user back to the previous page
   * they were on before entering the OperationGPT route.
   */
  const handleExitChat = () => {
    if (window.history.length > 1 || document.referrer) {
      // Returns the user to the exact previous page seamlessly
      window.history.back();
    } else {
      // Fallback: If they navigated directly to the chat URL, send them to the root/dashboard
      window.location.href = "/";
    }
  };

  return (
    <div
      className={`d-flex flex-column h-100 p-3 ${
        isDarkMode ? "bg-dark text-white" : "bg-body-tertiary text-body"
      }`}
    >
      {/* Sidebar Header */}
      <div className="d-flex align-items-center mb-4 px-2 mt-2 gap-2">
        <FaRobot className="text-primary fs-3" />
        <div>
          <h5 className="mb-0 fw-bold">{t.title}</h5>
          <small className="text-muted sidebar-subtitle">{t.subtitle}</small>
        </div>
      </div>

      {/* New Chat Button */}
      <Button
        variant="primary"
        onClick={onNewChat}
        className="w-100 mb-4 d-flex align-items-center justify-content-center gap-2 rounded-3 shadow-sm py-2"
      >
        <FaPlus /> <span className="fw-bold">{t.newChat}</span>
      </Button>

      {/* Recent Chats Section */}
      <div className="mb-2 px-2 text-secondary fw-bold sidebar-section-title">
        {t.recent}
      </div>

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
              className={`d-flex align-items-center justify-content-start gap-2 rounded mb-1 px-3 py-2 sidebar-link-faded ${
                isDarkMode ? "text-light" : "text-dark"
              }`}
            >
              <FaRegCommentDots className="text-muted" />
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

      {/* Bottom Action Menu */}
      <div className="mt-auto">
        {/* Settings Button */}
        <Button
          variant="link"
          className={`w-100 d-flex align-items-center justify-content-start gap-3 mb-3 text-decoration-none px-3 ${
            isDarkMode ? "text-light" : "text-dark"
          }`}
        >
          <FaCog className="text-secondary" />
          <span>{t.settings}</span>
        </Button>

        <div className="pt-3 border-top border-secondary border-opacity-25">
          {/* Language Toggle */}
          <Button
            variant="link"
            className={`w-100 d-flex align-items-center justify-content-start gap-3 mb-2 text-decoration-none px-3 ${
              isDarkMode ? "text-light" : "text-dark"
            }`}
            onClick={toggleLanguage}
          >
            <FaGlobe className="text-secondary" />
            <span>{t.langToggle}</span>
          </Button>

          {/* Theme Toggle */}
          <Button
            variant="link"
            className={`w-100 d-flex align-items-center justify-content-start gap-3 mb-2 text-decoration-none px-3 ${
              isDarkMode ? "text-light" : "text-dark"
            }`}
            onClick={toggleTheme}
          >
            {isDarkMode ? (
              <FaSun className="text-secondary" />
            ) : (
              <FaMoon className="text-secondary" />
            )}
            <span>{isDarkMode ? t.lightMode : t.darkMode}</span>
          </Button>

          {/* Exit Chat Button */}
          <Button
            variant="link"
            className="w-100 d-flex align-items-center justify-content-start gap-3 mb-2 text-decoration-none px-3 text-danger"
            onClick={handleExitChat}
          >
            <FaSignOutAlt />
            <span>{t.exitChat}</span>
          </Button>

          {/* User Profile Summary */}
          <div className="d-flex align-items-center justify-content-start gap-2 bg-secondary bg-opacity-10 p-2 rounded-3 mt-2">
            <div className="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center user-avatar">
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
