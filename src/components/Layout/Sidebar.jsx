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

const Sidebar = ({ isDarkMode, toggleTheme, language, toggleLanguage }) => {
  const t = {
    title: "OperationGPT",
    subtitle: language === "ar" ? "مساعدك الذكي" : "AI Assistant",
    newChat: language === "ar" ? "محادثة جديدة" : "New Chat",
    recent: language === "ar" ? "الأخيرة" : "Recent",
    settings: language === "ar" ? "الإعدادات" : "Settings",
    darkMode: language === "ar" ? "الوضع الليلي" : "Dark Mode",
    lightMode: language === "ar" ? "الوضع النهاري" : "Light Mode",
    langToggle: language === "ar" ? "English (LTR)" : "العربية (RTL)",
    role: language === "ar" ? "مشرف النظام" : "System Admin",
  };

  return (
    <div
      className={`d-flex flex-column h-100 p-3 ${
        isDarkMode ? "bg-dark text-white" : "bg-body-tertiary text-body"
      }`}
    >
      {/* Displays system identity; can be dynamically driven by backend branding/config data */}
      <div className="d-flex align-items-center mb-4 px-2 mt-2">
        <FaRobot className="text-primary fs-3 me-2" />
        <div>
          <h5 className="mb-0 fw-bold">{t.title}</h5>
          <small className="text-muted sidebar-subtitle">{t.subtitle}</small>
        </div>
      </div>

      {/* Triggers creation of a new chat session; expected to integrate with backend session/context reset endpoint */}
      <Button
        variant="primary"
        className="w-100 mb-4 d-flex align-items-center justify-content-center rounded-3 shadow-sm py-2"
      >
        <FaPlus className="me-2" />{" "}
        <span className="fw-bold">{t.newChat}</span>
      </Button>

      {/* Section header for chat history; currently static, intended to be populated via API */}
      <div className="mb-2 px-2 text-secondary fw-bold sidebar-section-title">
        {t.recent}
      </div>

      {/* Represents chat sessions list; designed to bind with backend-provided session IDs */}
      <Nav className="flex-column mb-auto overflow-auto">
        <Nav.Link
          href="#"
          className={`d-flex align-items-center rounded mb-1 px-3 py-2 bg-secondary bg-opacity-10 fw-semibold ${
            isDarkMode ? "text-light" : "text-dark"
          }`}
        >
          <FaRegCommentDots className="me-2 text-muted" />
          {language === "ar"
            ? "تحليل سجلات النظام"
            : "System Logs Analysis"}
        </Nav.Link>
        <Nav.Link
          href="#"
          className={`d-flex align-items-center rounded mb-1 px-3 py-2 sidebar-link-faded ${
            isDarkMode ? "text-light" : "text-dark"
          }`}
        >
          <FaRegCommentDots className="me-2 text-muted" />
          {language === "ar"
            ? "تحسين استعلامات SQL"
            : "SQL Queries Optimization"}
        </Nav.Link>
      </Nav>

      <div className="mt-auto">
        {/* Entry point for system settings; expected to connect to settings page or modal backed by API */}
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
          {/* UI-level language toggle; does not affect backend payload or API behavior */}
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

          {/* Theme toggle (Dark/Light); affects presentation layer only */}
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

          {/* Represents current user context; can be dynamically populated via authentication/user profile API */}
          <div className="d-flex align-items-center bg-secondary bg-opacity-10 p-2 rounded-3 mt-2">
            <div className="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center me-2 user-avatar">
              HQ
            </div>
            <div>
              <strong className="d-block user-name">محمد صيام</strong>
              <small className="text-muted user-role">{t.role}</small>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Sidebar;