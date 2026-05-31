/**
 * Tsoka Chatbot Widget - Embed on vendor websites
 * Usage: <script src="https://portal.tsokatravel.com/chatbot-widget.js"></script>
 *        window.TsokaChatbot.init({ apiKey: 'sk_live_...', color: '#FF6B35' })
 */

(function() {
  'use strict';

  const TsokaChatbot = {
    conversationId: null,
    config: {},
    messages: [],
    typing: false,

    /**
     * Initialize chatbot
     */
    init(options = {}) {
      this.config = {
        apiKey: options.apiKey || '',
        baseUrl: options.baseUrl || 'https://portal.tsokatravel.com',
        position: options.position || 'bottom-right',
        title: options.title || 'Tanova',
        subtitle: options.subtitle || 'AI travel expert',
        color: options.color || '#FF6B35',
        buttonColor: options.buttonColor || '#FF6B35',
        textColor: options.textColor || '#FFFFFF',
        fontSize: options.fontSize || '14px',
        fontFamily: options.fontFamily || 'system-ui, sans-serif',
        guestEmail: options.guestEmail || null,
        channel: options.channel || 'web',
        onConversationStart: options.onConversationStart || (() => {}),
        onMessage: options.onMessage || (() => {}),
        onBookingTriggered: options.onBookingTriggered || (() => {}),
        autoOpen: options.autoOpen || false,
        autoOpenDelay: options.autoOpenDelay || 5000,
      };

      if (!this.config.apiKey) {
        console.error('TsokaChatbot: apiKey is required');
        return;
      }

      this.injectStyles();
      this.createWidget();
      this.attachEventListeners();

      if (this.config.autoOpen) {
        setTimeout(() => this.openWidget(), this.config.autoOpenDelay);
      }
    },

    /**
     * Inject CSS styles
     */
    injectStyles() {
      const style = document.createElement('style');
      style.textContent = `
        .tsoka-chatbot {
          --primary-color: ${this.config.color};
          --button-color: ${this.config.buttonColor};
          --text-color: ${this.config.textColor};
          --font-size: ${this.config.fontSize};
          --font-family: ${this.config.fontFamily};
        }

        .tsoka-fab {
          position: fixed;
          ${this.config.position.includes('bottom') ? 'bottom: 20px;' : 'top: 20px;'}
          ${this.config.position.includes('right') ? 'right: 20px;' : 'left: 20px;'}
          width: 56px;
          height: 56px;
          border-radius: 50%;
          background: var(--button-color);
          color: var(--text-color);
          border: none;
          cursor: pointer;
          display: flex;
          align-items: center;
          justify-content: center;
          box-shadow: 0 4px 12px rgba(0,0,0,0.15);
          z-index: 99998;
          transition: transform 0.2s, box-shadow 0.2s;
          font-size: 24px;
        }

        .tsoka-fab:hover {
          transform: scale(1.1);
          box-shadow: 0 6px 16px rgba(0,0,0,0.2);
        }

        .tsoka-widget {
          position: fixed;
          ${this.config.position.includes('bottom') ? 'bottom: 20px;' : 'top: 20px;'}
          ${this.config.position.includes('right') ? 'right: 20px;' : 'left: 20px;'}
          width: 380px;
          height: 600px;
          background: white;
          border-radius: 12px;
          box-shadow: 0 5px 40px rgba(0,0,0,0.16);
          z-index: 99999;
          display: flex;
          flex-direction: column;
          opacity: 0;
          transform: scale(0.95);
          pointer-events: none;
          transition: opacity 0.3s, transform 0.3s;
        }

        .tsoka-widget.open {
          opacity: 1;
          transform: scale(1);
          pointer-events: auto;
        }

        @media (max-width: 480px) {
          .tsoka-widget {
            width: 100vw;
            height: 100vh;
            max-width: 100%;
            max-height: 100%;
            border-radius: 0;
            bottom: 0;
            ${this.config.position.includes('right') ? 'right: 0;' : 'left: 0;'}
            ${this.config.position.includes('bottom') ? 'top: auto;' : 'top: 0;'}
          }
        }

        .tsoka-header {
          background: var(--primary-color);
          color: white;
          padding: 16px;
          border-radius: 12px 12px 0 0;
          display: flex;
          justify-content: space-between;
          align-items: center;
        }

        .tsoka-header-title {
          font-weight: 600;
          font-size: 16px;
          margin: 0;
        }

        .tsoka-header-subtitle {
          font-size: 12px;
          opacity: 0.8;
          margin: 2px 0 0 0;
        }

        .tsoka-close {
          background: none;
          border: none;
          color: white;
          cursor: pointer;
          font-size: 20px;
          padding: 0;
          width: 24px;
          height: 24px;
          display: flex;
          align-items: center;
          justify-content: center;
        }

        .tsoka-messages {
          flex: 1;
          overflow-y: auto;
          padding: 16px;
          display: flex;
          flex-direction: column;
          gap: 12px;
          font-size: var(--font-size);
          font-family: var(--font-family);
        }

        .tsoka-welcome {
          text-align: center;
          padding: 20px;
          color: #666;
        }

        .tsoka-welcome-title {
          font-weight: 600;
          font-size: 16px;
          color: #333;
          margin-bottom: 8px;
        }

        .tsoka-welcome-text {
          font-size: 13px;
          line-height: 1.5;
          margin-bottom: 16px;
        }

        .tsoka-chips {
          display: flex;
          flex-direction: column;
          gap: 8px;
        }

        .tsoka-chip {
          background: #f0f0f0;
          border: 1px solid #ddd;
          padding: 10px 12px;
          border-radius: 6px;
          cursor: pointer;
          font-size: 12px;
          text-align: left;
          transition: all 0.2s;
        }

        .tsoka-chip:hover {
          background: var(--primary-color);
          color: white;
          border-color: var(--primary-color);
        }

        .tsoka-message {
          display: flex;
          gap: 8px;
          animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
          from {
            opacity: 0;
            transform: translateY(10px);
          }
          to {
            opacity: 1;
            transform: translateY(0);
          }
        }

        .tsoka-message.user {
          justify-content: flex-end;
        }

        .tsoka-message.ai {
          justify-content: flex-start;
        }

        .tsoka-bubble {
          max-width: 70%;
          padding: 12px;
          border-radius: 8px;
          word-wrap: break-word;
          line-height: 1.4;
        }

        .tsoka-message.user .tsoka-bubble {
          background: var(--primary-color);
          color: white;
          border-radius: 8px 2px 8px 8px;
        }

        .tsoka-message.ai .tsoka-bubble {
          background: #f0f0f0;
          color: #333;
          border-radius: 2px 8px 8px 8px;
        }

        .tsoka-typing {
          display: flex;
          gap: 4px;
          align-items: center;
          padding: 12px;
          background: #f0f0f0;
          border-radius: 8px;
        }

        .tsoka-typing span {
          width: 8px;
          height: 8px;
          background: #999;
          border-radius: 50%;
          animation: bounce 1.4s infinite;
        }

        .tsoka-typing span:nth-child(2) {
          animation-delay: 0.2s;
        }

        .tsoka-typing span:nth-child(3) {
          animation-delay: 0.4s;
        }

        @keyframes bounce {
          0%, 80%, 100% { transform: scale(1); opacity: 0.5; }
          40% { transform: scale(1.2); opacity: 1; }
        }

        .tsoka-buttons {
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 8px;
          margin-top: 8px;
        }

        .tsoka-button {
          background: var(--primary-color);
          color: white;
          border: none;
          padding: 10px;
          border-radius: 6px;
          cursor: pointer;
          font-size: 12px;
          font-weight: 500;
          transition: all 0.2s;
        }

        .tsoka-button:hover {
          opacity: 0.9;
          transform: translateY(-2px);
        }

        .tsoka-button.ghost {
          background: white;
          color: var(--primary-color);
          border: 1px solid var(--primary-color);
        }

        .tsoka-picks {
          display: flex;
          flex-wrap: wrap;
          gap: 6px;
          margin-top: 8px;
        }

        .tsoka-pick {
          background: white;
          border: 1px solid var(--primary-color);
          color: var(--primary-color);
          padding: 6px 10px;
          border-radius: 4px;
          cursor: pointer;
          font-size: 11px;
          transition: all 0.2s;
        }

        .tsoka-pick:hover {
          background: var(--primary-color);
          color: white;
        }

        .tsoka-input-area {
          padding: 12px;
          border-top: 1px solid #eee;
          display: flex;
          gap: 8px;
        }

        .tsoka-input {
          flex: 1;
          border: 1px solid #ddd;
          border-radius: 6px;
          padding: 10px;
          font-family: var(--font-family);
          font-size: var(--font-size);
          outline: none;
          transition: border-color 0.2s;
        }

        .tsoka-input:focus {
          border-color: var(--primary-color);
        }

        .tsoka-send {
          background: var(--primary-color);
          color: white;
          border: none;
          border-radius: 6px;
          width: 40px;
          cursor: pointer;
          display: flex;
          align-items: center;
          justify-content: center;
          transition: all 0.2s;
        }

        .tsoka-send:hover {
          opacity: 0.9;
        }

        .tsoka-send:disabled {
          opacity: 0.5;
          cursor: not-allowed;
        }
      `;
      document.head.appendChild(style);
    },

    /**
     * Create widget HTML
     */
    createWidget() {
      // FAB Button
      const fab = document.createElement('button');
      fab.className = 'tsoka-fab';
      fab.innerHTML = '💬';
      fab.setAttribute('aria-label', 'Open Tanova chatbot');
      document.body.appendChild(fab);

      // Widget Container
      const widget = document.createElement('div');
      widget.className = 'tsoka-widget';
      widget.innerHTML = `
        <div class="tsoka-header">
          <div>
            <p class="tsoka-header-title">${this.config.title}</p>
            <p class="tsoka-header-subtitle">${this.config.subtitle}</p>
          </div>
          <button class="tsoka-close">✕</button>
        </div>
        <div class="tsoka-messages"></div>
        <div class="tsoka-input-area">
          <input class="tsoka-input" type="text" placeholder="Ask me anything..." maxlength="500">
          <button class="tsoka-send">→</button>
        </div>
      `;
      document.body.appendChild(widget);

      this.fab = fab;
      this.widget = widget;
      this.messagesContainer = widget.querySelector('.tsoka-messages');
      this.input = widget.querySelector('.tsoka-input');
      this.sendBtn = widget.querySelector('.tsoka-send');
    },

    /**
     * Attach event listeners
     */
    attachEventListeners() {
      this.fab.addEventListener('click', () => this.toggleWidget());
      this.widget.querySelector('.tsoka-close').addEventListener('click', () => this.closeWidget());
      this.input.addEventListener('keypress', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
          e.preventDefault();
          this.sendMessage();
        }
      });
      this.sendBtn.addEventListener('click', () => this.sendMessage());
    },

    /**
     * Toggle widget
     */
    toggleWidget() {
      if (this.widget.classList.contains('open')) {
        this.closeWidget();
      } else {
        this.openWidget();
      }
    },

    /**
     * Open widget
     */
    openWidget() {
      this.widget.classList.add('open');
      this.input.focus();

      if (!this.conversationId) {
        this.showWelcome();
      }
    },

    /**
     * Close widget
     */
    closeWidget() {
      this.widget.classList.remove('open');
    },

    /**
     * Show welcome message
     */
    async showWelcome() {
      this.messagesContainer.innerHTML = `
        <div class="tsoka-welcome">
          <div class="tsoka-welcome-title">Hi, I'm Tanova</div>
          <p class="tsoka-welcome-text">
            What's costing your business the most right now — OTA commissions,
            manual work, or revenue you're not capturing? Tell me and I'll show
            you exactly what Tsoka fixes. Or if you're a traveller, tell me where
            you want to go.
          </p>
          <div class="tsoka-chips"></div>
        </div>
      `;

      // Fetch welcome message with chips
      try {
        const response = await this.apiCall('POST', '/concierge/message', {
          message: 'Hello',
          channel: this.config.channel,
          guest_email: this.config.guestEmail,
        });

        if (response.conversation_id) {
          this.conversationId = response.conversation_id;
          this.config.onConversationStart(this.conversationId);
        }

        // Load chips (predefined prompts)
        this.loadChips();
      } catch (error) {
        console.error('Failed to initialize conversation:', error);
      }
    },

    /**
     * Load chips (suggested prompts)
     */
    loadChips() {
      const chips = [
        { label: 'Plan my Tanzania safari', prompt: 'I want to plan a Tanzania safari — Serengeti, Ngorongoro, maybe Zanzibar after. Help me figure out the best route, best time to go, and roughly what to budget.' },
        { label: 'Booking.com is eating our margins', prompt: "We're doing most of our bookings through Booking.com and Expedia and it's costing us 18–20% every time. How does Tsoka actually fix this?" },
        { label: 'My front desk drowns in WhatsApp', prompt: 'Our front desk team spends half their day answering the same WhatsApp questions from guests. How does the Tsoka AI Concierge take this off their plate?' },
        { label: 'We had double bookings last week', prompt: 'We manage rooms across multiple platforms and we keep getting overbookings. How does Tsoka eliminate this?' },
        { label: 'Best Indian Ocean beach?', prompt: 'I\'m choosing between Zanzibar, Mauritius, and Mozambique for a beach holiday. What are the differences and which is right for me?' },
      ];

      const chipsContainer = this.messagesContainer.querySelector('.tsoka-chips');
      if (chipsContainer) {
        chipsContainer.innerHTML = chips.map(chip => `
          <button class="tsoka-chip" data-prompt="${this.escapeHtml(chip.prompt)}">
            ${chip.label}
          </button>
        `).join('');

        chipsContainer.addEventListener('click', (e) => {
          if (e.target.classList.contains('tsoka-chip')) {
            const prompt = e.target.getAttribute('data-prompt');
            this.sendMessage(prompt);
          }
        });
      }
    },

    /**
     * Send message
     */
    async sendMessage(text = null) {
      const message = text || this.input.value.trim();
      if (!message) return;

      this.input.value = '';
      this.typing = true;

      // Display user message
      this.addMessage(message, 'user');
      this.config.onMessage({ role: 'user', content: message });

      try {
        const response = await this.apiCall('POST', '/concierge/message', {
          conversation_id: this.conversationId,
          message: message,
          channel: this.config.channel,
          guest_email: this.config.guestEmail,
        });

        if (!this.conversationId && response.conversation_id) {
          this.conversationId = response.conversation_id;
          this.config.onConversationStart(this.conversationId);
        }

        this.typing = false;

        // Display assistant message
        this.addMessage(response.content, 'ai', response);
        this.config.onMessage({ role: 'assistant', content: response.content });

        // Handle special actions
        if (response.action === 'triggerGenerate') {
          this.config.onBookingTriggered(response.searchData);
        }
      } catch (error) {
        this.typing = false;
        console.error('Failed to send message:', error);
        this.addMessage('Sorry, something went wrong. Please try again.', 'ai');
      }
    },

    /**
     * Add message to chat
     */
    addMessage(text, role, metadata = {}) {
      if (!this.messagesContainer) return;

      // Clear welcome message
      const welcome = this.messagesContainer.querySelector('.tsoka-welcome');
      if (welcome) welcome.remove();

      // Show typing indicator
      if (this.typing && role === 'ai') {
        const typingDiv = document.createElement('div');
        typingDiv.className = 'tsoka-message ai';
        typingDiv.innerHTML = `
          <div class="tsoka-typing">
            <span></span><span></span><span></span>
          </div>
        `;
        this.messagesContainer.appendChild(typingDiv);
      }

      const msgDiv = document.createElement('div');
      msgDiv.className = `tsoka-message ${role}`;

      const bubble = document.createElement('div');
      bubble.className = 'tsoka-bubble';
      bubble.innerHTML = this.sanitizeHtml(text);

      msgDiv.appendChild(bubble);

      // Add buttons if provided
      if (metadata.buttons) {
        const buttonsDiv = document.createElement('div');
        buttonsDiv.className = 'tsoka-buttons';
        metadata.buttons.forEach(btn => {
          const btn_elem = document.createElement('button');
          btn_elem.className = 'tsoka-button';
          btn_elem.textContent = btn.label;
          btn_elem.addEventListener('click', () => this.sendMessage(btn.label));
          buttonsDiv.appendChild(btn_elem);
        });
        msgDiv.appendChild(buttonsDiv);
      }

      // Add picks (destination buttons) if provided
      if (metadata.picks) {
        const picksDiv = document.createElement('div');
        picksDiv.className = 'tsoka-picks';
        metadata.picks.forEach(pick => {
          const pickBtn = document.createElement('button');
          pickBtn.className = 'tsoka-pick';
          pickBtn.textContent = pick.name + ', ' + pick.country_name;
          pickBtn.addEventListener('click', () => this.sendMessage(pick.name + ', ' + pick.country_name));
          picksDiv.appendChild(pickBtn);
        });
        msgDiv.appendChild(picksDiv);
      }

      this.messagesContainer.appendChild(msgDiv);
      this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;

      this.messages.push({ text, role, metadata });
    },

    /**
     * API call helper
     */
    async apiCall(method, endpoint, data = {}) {
      const url = `${this.config.baseUrl}/api/v${endpoint}`;

      const options = {
        method: method,
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${this.config.apiKey}`,
        },
      };

      if (method !== 'GET') {
        options.body = JSON.stringify(data);
      }

      const response = await fetch(url, options);

      if (!response.ok) {
        throw new Error(`API error: ${response.status}`);
      }

      return await response.json();
    },

    /**
     * Sanitize HTML to prevent XSS
     */
    sanitizeHtml(html) {
      const div = document.createElement('div');
      div.textContent = html;
      return div.innerHTML;
    },

    /**
     * Escape HTML for data attributes
     */
    escapeHtml(text) {
      const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
      };
      return text.replace(/[&<>"']/g, m => map[m]);
    },
  };

  // Export to window
  window.TsokaChatbot = TsokaChatbot;

  // Auto-initialize if apiKey is in data attribute
  if (document.currentScript?.dataset.apiKey) {
    TsokaChatbot.init({
      apiKey: document.currentScript.dataset.apiKey,
    });
  }
})();
