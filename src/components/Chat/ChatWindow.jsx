import React, { useState, useRef, useEffect } from "react";
import { sendCommandToAI } from "../../services/api";
import { Button, Form, InputGroup } from "react-bootstrap";
import {
  FaBars,
  FaPaperPlane,
  FaPaperclip,
  FaMicrophone,
  FaRobot,
} from "react-icons/fa";

import "../../assets/css/chat.css";

const ChatWindow = ({ language, toggleSidebar }) => {
  // Manages input state, chat history, and loading state to control request lifecycle and prevent duplicate API calls
  const [input, setInput] = useState("");
  const [messages, setMessages] = useState([]);
  const [isLoading, setIsLoading] = useState(false);

  // Reference to the last message element to ensure auto-scroll when new messages arrive from backend
  const messagesEndRef = useRef(null);
  const scrollToBottom = () =>
    messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
  useEffect(() => scrollToBottom(), [messages]);

  // Lightweight UI-level localization without affecting backend payload or API communication
  const t = {
    greeting: language === "ar" ? "مساء الخير،" : "Good Evening,",
    subtitle:
      language === "ar"
        ? "أنا هنا لمساعدتك في مراقبة البنية التحتية، وتحليل السجلات، وتحسين أداء النظام. ماذا تريد أن تفعل اليوم؟"
        : "I'm here to help you monitor infrastructure, analyze logs, and optimize system performance. What would you like to do today?",
    placeholder:
      language === "ar" ? "اكتب رسالتك هنا..." : "Type your message here...",
    prompts:
      language === "ar"
        ? [
            "ملخص أداء قاعدة البيانات",
            "فحص حالة الحاويات (K8s)",
            "تحليل سجلات الأخطاء الأخيرة",
          ]
        : [
            "Database Performance Summary",
            "Check K8s Containers Status",
            "Analyze Recent Error Logs",
          ],
    disclaimer:
      language === "ar"
        ? "يمكن لـ OperationGPT ارتكاب أخطاء. تحقق من المعلومات المهمة."
        : "OperationGPT may make mistakes. Verify critical information.",
  };

  const handleSend = async (text = input) => {
    if (!text.trim() || isLoading) return;

    // Optimistic UI update to immediately reflect user message before backend processing completes
    setMessages((prev) => [...prev, { id: Date.now(), role: "user", text }]);
    setInput("");
    setIsLoading(true);

    try {
      // Sends payload to API service layer which handles communication with backend (expects JSON response)
      const response = await sendCommandToAI(text, "user");

      /* 
       Normalizes backend response to support multiple response contracts
       (reply / message) and reduce tight coupling with API structure
      */
      const botReply =
        response.reply ||
        response.message ||
        (language === "ar" ? "تمت العملية بنجاح" : "Operation successful");

      // Appends backend response to chat history while preserving chronological UI flow
      setMessages((prev) => [
        ...prev,
        { id: Date.now(), role: "bot", text: botReply },
      ]);
    } catch (error) {
      // Handles network/API errors gracefully without breaking UI, with fallback messaging
      const errorMsg =
        language === "ar"
          ? "عذراً، حدث خطأ في الاتصال بالخادم."
          : "Sorry, a server connection error occurred";
      setMessages((prev) => [
        ...prev,
        { id: Date.now(), role: "bot", text: error.message || errorMsg },
      ]);
    } finally {
      // Resets loading state to allow new requests
      setIsLoading(false);
    }
  };

  // Determines chat bubble styling based on message source and language direction (RTL/LTR)
  const getBubbleClass = (role) => {
    if (role === "user")
      return language === "ar" ? "bubble-user-ar" : "bubble-user-en";
    return language === "ar" ? "bubble-bot-ar" : "bubble-bot-en";
  };

  return (
    <div className="d-flex flex-column h-100 position-relative bg-body">
      <div className="d-flex align-items-center p-3 border-bottom d-md-none">
        <Button
          variant="link"
          className="text-body p-0 me-3"
          onClick={toggleSidebar}
        >
          <FaBars size={24} />
        </Button>
        <h5 className="mb-0 fw-bold text-primary">OperationGPT</h5>
      </div>

      {/* Message container: renders chat history or empty state depending on data availability */}
      <div className="flex-grow-1 overflow-auto p-3 p-md-5 d-flex flex-column chat-scroll-area">
        {messages.length === 0 ? (
          /* Empty state UI: provides predefined prompts to guide user input and trigger backend requests */
          <div className="m-auto text-center welcome-container">
            <h2 className="fw-bold mb-3">{t.greeting}</h2>
            <p className="text-muted mb-5 fs-6">{t.subtitle}</p>

            <div className="d-flex flex-wrap justify-content-center gap-2">
              {t.prompts.map((prompt, idx) => (
                <Button
                  key={idx}
                  variant="outline-secondary"
                  className="rounded-pill px-3 py-2 text-body border-opacity-25 bg-body-tertiary shadow-sm custom-hover quick-prompt-btn"
                  onClick={() => handleSend(prompt)}
                >
                  {prompt}
                </Button>
              ))}
            </div>
          </div>
        ) : (
          /* Chat history rendering based on state-driven message array synced with backend interaction */
          <div className="w-100 mx-auto pb-4 messages-container">
            {messages.map((msg) => (
              <div
                key={msg.id}
                className={`d-flex mb-4 w-100 ${msg.role === "user" ? "justify-content-end" : "justify-content-start"}`}
              >
                {msg.role === "bot" && (
                  <div
                    className={`mt-auto mb-auto ${language === "ar" ? "ms-2" : "me-2"}`}
                  >
                    {/* Visual indicator for AI/system responses to distinguish from user messages */}
                    <div className="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm bot-avatar">
                      <FaRobot size={16} />
                    </div>
                  </div>
                )}

                {/* Binds UI representation with message state to reflect role-based styling */}
                <div
                  className={`p-3 shadow-sm chat-bubble ${getBubbleClass(msg.role)} ${
                    msg.role === "user"
                      ? "bg-primary text-white"
                      : "bg-body border border-secondary border-opacity-10 text-body"
                  }`}
                >
                  {msg.text}
                </div>
              </div>
            ))}

            {isLoading && (
              <div className="d-flex mb-4 justify-content-start align-items-center text-muted">
                <div
                  className={`mt-auto mb-auto ${language === "ar" ? "ms-2" : "me-2"}`}
                >
                  <div className="bg-secondary bg-opacity-25 text-primary rounded-circle d-flex align-items-center justify-content-center bot-avatar">
                    <FaRobot size={14} />
                  </div>
                </div>
                {/* Loading indicator tied to isLoading state to reflect pending backend response */}
                <div className="p-2 rounded-4 bg-body border border-secondary border-opacity-10 shadow-sm typing-container">
                  <span
                    className="spinner-grow spinner-grow-sm text-primary mx-1"
                    role="status"
                    aria-hidden="true"
                  ></span>
                  <span
                    className="spinner-grow spinner-grow-sm text-primary mx-1 dot-delay-1"
                    role="status"
                    aria-hidden="true"
                  ></span>
                  <span
                    className="spinner-grow spinner-grow-sm text-primary mx-1 dot-delay-2"
                    role="status"
                    aria-hidden="true"
                  ></span>
                </div>
              </div>
            )}
            <div ref={messagesEndRef} />
          </div>
        )}
      </div>

      {/* Input layer: primary integration point where user request is constructed and sent to backend */}
      <div className="p-3 bg-body mx-auto w-100 input-container">
        <InputGroup
          size="lg"
          className="shadow-sm rounded-pill bg-body-tertiary border border-secondary border-opacity-25 px-2 py-1 align-items-center"
        >
          <Button
            variant="link"
            className="text-secondary p-2 rounded-circle custom-hover"
          >
            <FaPaperclip size={18} />
          </Button>
          <Button
            variant="link"
            className="text-secondary p-2 rounded-circle custom-hover"
          >
            <FaMicrophone size={18} />
          </Button>

          <Form.Control
            placeholder={t.placeholder}
            value={input}
            onChange={(e) => setInput(e.target.value)}
            onKeyPress={(e) => e.key === "Enter" && handleSend()}
            disabled={isLoading}
            className="bg-transparent border-0 shadow-none fs-6"
          />

          {/* Prevents invalid or concurrent submissions during request processing */}
          <Button
            variant="primary"
            onClick={() => handleSend()}
            disabled={isLoading || !input.trim()}
            className="rounded-circle d-flex align-items-center justify-content-center p-0 ms-1 me-1 shadow-sm send-btn"
          >
            <FaPaperPlane
              size={16}
              className={language === "ar" ? "ms-1" : "me-1"}
            />
          </Button>
        </InputGroup>
        <div className="text-muted mt-2 disclaimer-text text-center">
          {t.disclaimer}
        </div>
      </div>
    </div>
  );
};

export default ChatWindow;
