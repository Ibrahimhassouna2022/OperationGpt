import React, { useState, useRef, useEffect } from "react";
import { sendCommandToAI } from "../../services/api";
import { Button, Form, InputGroup, Table } from "react-bootstrap";
import {
  FaBars,
  FaPaperPlane,
  FaPaperclip,
  FaMicrophone,
  FaRobot,
} from "react-icons/fa";

import "../../assets/css/chat.css";

const ChatWindow = ({
  language = "en",
  toggleSidebar,
  messages,
  setMessages,
}) => {
  const [input, setInput] = useState("");
  const [isLoading, setIsLoading] = useState(false);

  // Injected dynamically via Laravel Auth
  const userName = window.AppUser?.name || "System Admin";

  const messagesEndRef = useRef(null);
  const scrollToBottom = () =>
    messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
  useEffect(() => scrollToBottom(), [messages]);

  const t = {
    greeting:
      language === "ar"
        ? `مساء الخير، ${userName}`
        : `Good Evening, ${userName}`,
    subtitle:
      language === "ar"
        ? "أنا هنا لمساعدتك في مراقبة البنية التحتية، وتحليل السجلات، وتحسين أداء النظام."
        : "I'm here to help you monitor infrastructure, analyze logs, and optimize system performance.",
    placeholder:
      language === "ar" ? "اكتب رسالتك هنا..." : "Type your message here...",
    disclaimer:
      language === "ar"
        ? "يمكن لـ OperationGPT ارتكاب أخطاء."
        : "OperationGPT may make mistakes.",
  };

  // Helper function to render data array into a clean HTML table structure dynamically
  const renderDataToTable = (dataArray) => {
    if (!dataArray || !Array.isArray(dataArray) || dataArray.length === 0)
      return null;

    const headers = Object.keys(dataArray[0]);

    return (
      <div className="table-responsive my-2 border rounded shadow-sm bg-body">
        <Table
          striped
          bordered
          hover
          size="sm"
          className="mb-0 text-center align-middle"
        >
          <thead className="table-primary text-nowrap">
            <tr>
              {headers.map((h) => (
                <th key={h} className="text-capitalize px-3 py-2">
                  {h}
                </th>
              ))}
            </tr>
          </thead>
          <tbody>
            {dataArray.map((row, idx) => (
              <tr key={idx}>
                {headers.map((h) => {
                  const value = row[h];
                  return (
                    <td key={h} className="px-3 py-2">
                      {typeof value === "object"
                        ? JSON.stringify(value)
                        : String(value)}
                    </td>
                  );
                })}
              </tr>
            ))}
          </tbody>
        </Table>
      </div>
    );
  };

  const handleSend = async (text = input) => {
    if (!text.trim() || isLoading) return;

    setMessages((prev) => [...prev, { id: Date.now(), role: "user", text }]);
    setInput("");
    setIsLoading(true);

    try {
      const response = await sendCommandToAI(text, "user");
      let rawData = null;
      let textReply = "";

      // Smart response parser: store raw data arrays directly in the message object
      if (
        response &&
        (Array.isArray(response.data) || Array.isArray(response.result))
      ) {
        rawData = response.data || response.result;
      } else if (Array.isArray(response)) {
        rawData = response;
      } else {
        if (response.reply) textReply = response.reply;
        else if (response.message) textReply = response.message;
        else if (response.data !== undefined && response.data !== null) {
          textReply =
            typeof response.data === "object"
              ? JSON.stringify(response.data)
              : String(response.data);
        } else if (
          typeof response === "string" ||
          typeof response === "number"
        ) {
          textReply = String(response);
        } else {
          textReply =
            language === "ar" ? "تمت العملية بنجاح" : "Operation successful";
        }
      }

      setMessages((prev) => [
        ...prev,
        { id: Date.now(), role: "bot", text: textReply, tableData: rawData },
      ]);
    } catch (error) {
      const errorMsg =
        language === "ar"
          ? "عذراً، حدث خطأ في الاتصال بالخادم."
          : "Server connection error occurred";
      setMessages((prev) => [
        ...prev,
        { id: Date.now(), role: "bot", text: error.message || errorMsg },
      ]);
    } finally {
      setIsLoading(false);
    }
  };

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

      <div className="flex-grow-1 overflow-auto p-3 p-md-5 d-flex flex-column chat-scroll-area">
        {messages.length === 0 ? (
          <div className="m-auto text-center welcome-container">
            <h2 className="fw-bold mb-3">{t.greeting}</h2>
            <p className="text-muted mb-5 fs-6">{t.subtitle}</p>
          </div>
        ) : (
          <div className="w-100 mx-auto pb-4 messages-container">
            {messages.map((msg) => {
              if (!msg) return null;

              // Check if message text contains raw HTML tables from legacy backend responses
              const isHtml =
                msg.role === "bot" &&
                msg.text &&
                typeof msg.text === "string" &&
                msg.text.trim().startsWith("<");

              return (
                <div
                  key={msg.id || Date.now() + Math.random()}
                  className={`d-flex mb-4 w-100 ${msg.role === "user" ? "justify-content-end" : "justify-content-start"}`}
                >
                  {msg.role === "bot" && (
                    <div
                      className={
                        language === "ar"
                          ? "ms-2 mt-auto mb-auto"
                          : "me-2 mt-auto mb-auto"
                      }
                    >
                      <div className="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm bot-avatar">
                        <FaRobot size={16} />
                      </div>
                    </div>
                  )}
                  <div
                    className={`p-3 shadow-sm chat-bubble ${getBubbleClass(msg.role)} ${msg.role === "user" ? "bg-primary text-white" : "bg-body border border-secondary border-opacity-10 text-body"}`}
                  >
                    {msg.tableData ? (
                      renderDataToTable(msg.tableData)
                    ) : isHtml ? (
                      <div
                        dangerouslySetInnerHTML={{ __html: msg.text }}
                        className="html-table-container table-responsive"
                      />
                    ) : (
                      <span>{msg.text}</span>
                    )}
                  </div>
                </div>
              );
            })}
            {isLoading && (
              <div className="d-flex mb-4 justify-content-start align-items-center text-muted">
                <div
                  className={
                    language === "ar"
                      ? "ms-2 mt-auto mb-auto"
                      : "me-2 mt-auto mb-auto"
                  }
                >
                  <div className="bg-secondary bg-opacity-25 text-primary rounded-circle d-flex align-items-center justify-content-center bot-avatar">
                    <FaRobot size={14} />
                  </div>
                </div>
                <div className="p-2 rounded-4 bg-body border border-secondary border-opacity-10 shadow-sm typing-container">
                  <span className="spinner-grow spinner-grow-sm text-primary mx-1"></span>
                  <span className="spinner-grow spinner-grow-sm text-primary mx-1 dot-delay-1"></span>
                  <span className="spinner-grow spinner-grow-sm text-primary mx-1 dot-delay-2"></span>
                </div>
              </div>
            )}
            <div ref={messagesEndRef} />
          </div>
        )}
      </div>

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
