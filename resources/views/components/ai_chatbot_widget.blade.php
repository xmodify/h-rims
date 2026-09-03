<!-- Floating AI Chatbot Button & Widget -->
<div id="aiChatbotWrapper">
    <!-- Floating Action Button (FAB) -->
    <button type="button" id="aiChatbotFab" class="ai-fab-btn" title="ผู้ช่วย AI อัจฉริยะ (RiMS Copilot)" onclick="toggleAiChatbot()">
        <span class="ai-fab-pulse"></span>
        <span class="ai-fab-icon" id="aiFabIcon">
            <i class="bi bi-robot"></i>
        </span>
        <span class="ai-fab-badge">AI</span>
    </button>

    <!-- Chatbot Window -->
    <div id="aiChatbotWindow" class="ai-chat-window shadow-lg">
        <!-- Header -->
        <div class="ai-chat-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <div class="ai-avatar-badge">
                    <i class="bi bi-robot fs-5"></i>
                    <span class="ai-status-dot"></span>
                </div>
                <div>
                    <h6 class="mb-0 fw-bold text-white fs-6">RiMS Copilot</h6>
                    <small class="text-white-50" style="font-size: 0.72rem;">ผู้ช่วย AI: มอนิเตอร์การเงินการคลัง • ตรวจสอบ HOSxP • เบิกจ่ายกองทุนต่าง ๆ</small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-1">
                <button type="button" class="btn btn-sm btn-link text-white-50 p-1 text-decoration-none" title="ล้างบทสนทนา" onclick="clearAiChatHistory()">
                    <i class="bi bi-trash3"></i>
                </button>
                @if(auth()->check() && auth()->user()->status === 'admin')
                <a href="{{ route('admin.rag.index') }}" class="btn btn-sm btn-link text-white-50 p-1 text-decoration-none" title="ไปยังคลังความรู้">
                    <i class="bi bi-box-arrow-up-right"></i>
                </a>
                @endif
                <button type="button" class="btn btn-sm btn-link text-white p-1 text-decoration-none" title="ปิดหน้าต่าง" onclick="toggleAiChatbot()">
                    <i class="bi bi-x-lg fs-6"></i>
                </button>
            </div>
        </div>

        <!-- Messages Container -->
        <div class="ai-chat-body" id="aiChatMessages">
            <!-- Welcome Message -->
            <div class="ai-msg-row ai-msg-incoming">
                <div class="ai-msg-avatar"><i class="bi bi-robot"></i></div>
                <div class="ai-msg-bubble">
                    <p class="mb-1">สวัสดีครับ! ผมคือ <strong>RiMS Copilot</strong> 🩺✨</p>
                    <p class="mb-0 small text-muted">ผู้ช่วย AI ประจำระบบมอนิเตอร์สถานะการเงินการคลังโรงพยาบาล พร้อมช่วยวิเคราะห์งบทดลอง ตรวจสอบการตั้งค่า HOSxP และหลักเกณฑ์การเบิกจ่ายกองทุนต่าง ๆ สอบถามได้เลยครับ</p>
                </div>
            </div>

            <!-- (คำถามที่พบบ่อยถูกนำออกตามที่ผู้ใช้ต้องการ) -->
        </div>

        <!-- Typing Indicator (Hidden by default) -->
        <div id="aiTypingIndicator" class="ai-typing-indicator d-none">
            <div class="ai-msg-avatar"><i class="bi bi-robot"></i></div>
            <div class="ai-typing-bubble">
                <span class="ai-dot"></span>
                <span class="ai-dot"></span>
                <span class="ai-dot"></span>
                <small class="text-muted ms-2" style="font-size: 0.75rem;">AI กำลังค้นหาข้อมูล...</small>
            </div>
        </div>

        <!-- Footer / Input Box -->
        <div class="ai-chat-footer">
            <form id="aiChatForm" onsubmit="handleSendChat(event)" class="d-flex align-items-center gap-2 m-0">
                <input type="text" id="aiChatInput" class="form-control ai-chat-input" placeholder="พิมพ์คำถามที่นี่... (กด Enter เพื่อส่ง)" autocomplete="off" required>
                <button type="submit" id="aiBtnSend" class="btn btn-success ai-send-btn shadow-sm" title="ส่งข้อความ">
                    <i class="bi bi-send-fill"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<style>
    /* Floating Action Button (FAB) */
    .ai-fab-btn {
        position: fixed;
        bottom: 24px;
        right: 24px;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, #0a4d2c 0%, #16a34a 100%);
        color: #ffffff;
        border: none;
        box-shadow: 0 8px 24px rgba(10, 77, 44, 0.4);
        cursor: pointer;
        z-index: 99998;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .ai-fab-btn:hover {
        transform: scale(1.1) rotate(5deg);
        box-shadow: 0 12px 30px rgba(10, 77, 44, 0.55);
        color: #ffffff;
    }

    .ai-fab-btn:active {
        transform: scale(0.95);
    }

    .ai-fab-icon {
        font-size: 1.8rem;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.3s ease;
    }

    .ai-fab-badge {
        position: absolute;
        top: -2px;
        right: -2px;
        background: #ef4444;
        color: #fff;
        font-size: 0.65rem;
        font-weight: 800;
        padding: 2px 6px;
        border-radius: 10px;
        border: 2px solid #fff;
        letter-spacing: 0.5px;
    }

    .ai-fab-pulse {
        position: absolute;
        width: 100%;
        height: 100%;
        border-radius: 50%;
        background: rgba(22, 163, 74, 0.5);
        animation: aiPulse 2.4s infinite;
        z-index: -1;
    }

    @keyframes aiPulse {
        0% { transform: scale(1); opacity: 0.8; }
        70% { transform: scale(1.4); opacity: 0; }
        100% { transform: scale(1.4); opacity: 0; }
    }

    /* Chat Window */
    .ai-chat-window {
        position: fixed;
        bottom: 96px;
        right: 24px;
        width: 410px;
        max-width: calc(100vw - 32px);
        height: 580px;
        max-height: calc(100vh - 120px);
        background: #ffffff;
        border-radius: 20px;
        border: 1px solid rgba(0, 0, 0, 0.08);
        z-index: 99999;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        opacity: 0;
        transform: scale(0.85) translateY(30px);
        pointer-events: none;
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        box-shadow: 0 16px 48px rgba(0, 0, 0, 0.22) !important;
    }

    .ai-chat-window.active {
        opacity: 1;
        transform: scale(1) translateY(0);
        pointer-events: auto;
    }

    /* Header */
    .ai-chat-header {
        background: linear-gradient(135deg, #0a4d2c 0%, #126e41 100%);
        padding: 14px 18px;
        color: #ffffff;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .ai-avatar-badge {
        position: relative;
        width: 38px;
        height: 38px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
    }

    .ai-status-dot {
        position: absolute;
        bottom: 0;
        right: 0;
        width: 10px;
        height: 10px;
        background: #22c55e;
        border-radius: 50%;
        border: 2px solid #0a4d2c;
    }

    /* Body & Messages */
    .ai-chat-body {
        flex: 1;
        padding: 16px;
        overflow-y: auto;
        background: #f8fafc;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .ai-msg-row {
        display: flex;
        gap: 8px;
        align-items: flex-end;
    }

    .ai-msg-incoming {
        justify-content: flex-start;
    }

    .ai-msg-outgoing {
        justify-content: flex-end;
    }

    .ai-msg-avatar {
        width: 28px;
        height: 28px;
        background: #e2e8f0;
        color: #0a4d2c;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        flex-shrink: 0;
    }

    .ai-msg-bubble {
        max-width: 82%;
        padding: 10px 14px;
        border-radius: 16px;
        font-size: 0.85rem;
        line-height: 1.55;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
        word-break: break-word;
    }

    .ai-msg-incoming .ai-msg-bubble {
        background: #ffffff;
        color: #1e293b;
        border-bottom-left-radius: 4px;
        border: 1px solid #e2e8f0;
    }

    .ai-msg-outgoing .ai-msg-bubble {
        background: linear-gradient(135deg, #0a4d2c 0%, #16a34a 100%);
        color: #ffffff;
        border-bottom-right-radius: 4px;
    }

    /* Source Citation inside chat bubble */
    .ai-msg-source {
        margin-top: 8px;
        padding-top: 6px;
        border-top: 1px dashed rgba(0, 0, 0, 0.1);
        font-size: 0.72rem;
        color: #059669;
    }

    /* Quick Suggestions */
    .ai-suggestion-pill {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        color: #334155;
        border-radius: 14px;
        padding: 4px 10px;
        font-size: 0.75rem;
        cursor: pointer;
        margin-right: 4px;
        margin-bottom: 5px;
        transition: all 0.2s ease;
    }

    .ai-suggestion-pill:hover {
        background: #e2e8f0;
        color: #0a4d2c;
        border-color: #0a4d2c;
        transform: translateY(-1px);
    }

    /* Typing Indicator */
    .ai-typing-indicator {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 4px 16px 10px;
        background: #f8fafc;
    }

    .ai-typing-bubble {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        padding: 6px 12px;
        border-radius: 16px;
        display: flex;
        align-items: center;
    }

    .ai-dot {
        width: 6px;
        height: 6px;
        margin: 0 2px;
        background: #16a34a;
        border-radius: 50%;
        display: inline-block;
        animation: aiBounce 1.4s infinite ease-in-out both;
    }

    .ai-dot:nth-child(1) { animation-delay: -0.32s; }
    .ai-dot:nth-child(2) { animation-delay: -0.16s; }

    @keyframes aiBounce {
        0%, 80%, 100% { transform: scale(0); }
        40% { transform: scale(1); }
    }

    /* Footer & Input */
    .ai-chat-footer {
        padding: 10px 14px;
        background: #ffffff;
        border-top: 1px solid #e2e8f0;
    }

    .ai-chat-input {
        border-radius: 20px !important;
        font-size: 0.85rem !important;
        padding: 8px 14px !important;
        border: 1px solid #cbd5e1 !important;
        background: #f8fafc !important;
    }

    .ai-chat-input:focus {
        background: #ffffff !important;
        border-color: #16a34a !important;
        box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.15) !important;
    }

    .ai-send-btn {
        width: 38px;
        height: 38px;
        border-radius: 50% !important;
        padding: 0 !important;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #0a4d2c !important;
        border: none !important;
        color: #fff !important;
        flex-shrink: 0;
        transition: all 0.2s;
    }

    .ai-send-btn:hover {
        background: #16a34a !important;
        transform: scale(1.05);
    }
</style>

<script>
    const AI_STORAGE_KEY = 'hrims_ai_chat_history';

    // ป้องกัน Bootstrap modal ไม่ให้ดักจับ Focus ออกจาก RiMS Copilot ทำให้พิมพ์ได้ตลอดเวลาแม้มี Modal เปิดอยู่
    document.addEventListener('focusin', function(e) {
        if (e.target && e.target.closest && e.target.closest('#aiChatbotWindow')) {
            e.stopImmediatePropagation();
        }
    }, true);

    // Toggle Chatbot Window
    function toggleAiChatbot() {
        const win = document.getElementById('aiChatbotWindow');
        const fab = document.getElementById('aiChatbotFab');
        const icon = document.getElementById('aiFabIcon');

        win.classList.toggle('active');

        if (win.classList.contains('active')) {
            icon.innerHTML = '<i class="bi bi-x-lg"></i>';
            setTimeout(() => {
                const input = document.getElementById('aiChatInput');
                if (input) input.focus();
            }, 100);
            scrollChatToBottom();
        } else {
            icon.innerHTML = '<i class="bi bi-robot"></i>';
        }
    }

    // Alias สำหรับฟังก์ชันเรียกเปิดปิด
    window.toggleAiChat = toggleAiChatbot;
    window.toggleAiChatbot = toggleAiChatbot;

    // Helper สำหรับส่ง Prompt ต่อเนื่องจากหน้าอื่น
    window.openAiChatWithPrompt = function(promptText) {
        const win = document.getElementById('aiChatbotWindow');
        if (win && !win.classList.contains('active')) {
            toggleAiChatbot();
        }
        const input = document.getElementById('aiChatInput');
        if (input && promptText) {
            input.value = promptText;
            setTimeout(() => input.focus(), 250);
        }
    };

    // Scroll messages to bottom
    function scrollChatToBottom() {
        const box = document.getElementById('aiChatMessages');
        box.scrollTop = box.scrollHeight;
    }

    // Quick Prompt click
    function sendQuickPrompt(text) {
        document.getElementById('aiChatInput').value = text;
        handleSendChat(new Event('submit'));
    }

    let aiConversationHistory = [];

    // Send chat message
    function handleSendChat(event) {
        if (event) event.preventDefault();
        const input = document.getElementById('aiChatInput');
        const text = input.value.trim();
        if (!text) return;

        // Append User Message
        appendMessage('outgoing', text);
        aiConversationHistory.push({ role: 'user', content: text });
        input.value = '';

        // Hide suggestions once conversation starts
        const sug = document.getElementById('aiQuickSuggestions');
        if (sug) sug.style.display = 'none';

        // Show typing indicator
        const indicator = document.getElementById('aiTypingIndicator');
        indicator.classList.remove('d-none');
        scrollChatToBottom();

        // Call RAG API with history
        fetch('{{ route("admin.rag.ask") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ 
                question: text,
                history: aiConversationHistory.slice(-6)
            })
        })
        .then(res => res.json())
        .then(data => {
            indicator.classList.add('d-none');
            if (data.success) {
                aiConversationHistory.push({ role: 'assistant', content: data.answer });
                appendMessage('incoming', data.answer, data.sources);
            } else {
                appendMessage('incoming', 'ขออภัยครับ เกิดข้อผิดพลาด: ' + (data.message || 'ไม่สามารถติดต่อ AI ได้'));
            }
        })
        .catch(err => {
            indicator.classList.add('d-none');
            appendMessage('incoming', 'ไม่สามารถเชื่อมต่อกับระบบ AI ได้: ' + err);
        });
    }

    // Append Message to UI
    function appendMessage(direction, text, sources = []) {
        const container = document.getElementById('aiChatMessages');
        const row = document.createElement('div');
        row.className = `ai-msg-row ai-msg-${direction}`;

        if (direction === 'incoming') {
            let sourcesHtml = '';
            if (sources && sources.length > 0) {
                sourcesHtml = '<div class="ai-msg-source mt-2 pt-2 border-top"><i class="bi bi-shield-check text-success me-1"></i><strong>แหล่งที่มาข้อมูล:</strong> ' +
                    sources.map(s => `<span class="badge bg-light text-dark border me-1 my-1">${s.title}</span>`).join('') +
                    '</div>';
            }

            // Simple markdown parsing for bold, bullets, headers
            let formattedText = escapeHtml(text)
                .replace(/^### (.*$)/gim, '<strong class="d-block text-dark mt-2 mb-1">$1</strong>')
                .replace(/^## (.*$)/gim, '<strong class="d-block text-primary mt-2 mb-1">$1</strong>')
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.*?)\*/g, '<em>$1</em>')
                .replace(/^\* (.*$)/gim, '<div class="ms-2">• $1</div>')
                .replace(/^- (.*$)/gim, '<div class="ms-2">• $1</div>')
                .replace(/\n/g, '<br>');

            row.innerHTML = `
                <div class="ai-msg-avatar"><i class="bi bi-robot"></i></div>
                <div class="ai-msg-bubble">
                    <div>${formattedText}</div>
                    ${sourcesHtml}
                </div>
            `;
        } else {
            row.innerHTML = `
                <div class="ai-msg-bubble">
                    ${escapeHtml(text).replace(/\n/g, '<br>')}
                </div>
            `;
        }

        container.appendChild(row);
        scrollChatToBottom();
    }

    // Clear Chat History
    function clearAiChatHistory() {
        aiConversationHistory = [];
        const container = document.getElementById('aiChatMessages');
        container.innerHTML = `
            <div class="ai-msg-row ai-msg-incoming">
                <div class="ai-msg-avatar"><i class="bi bi-robot"></i></div>
                <div class="ai-msg-bubble">
                    <p class="mb-1">ล้างบทสนทนาเรียบร้อยแล้วครับ ✨</p>
                    <p class="mb-0 small text-muted">ต้องการสอบถามข้อมูลอะไรเพิ่มเติม พิมพ์ถามได้เลยครับ</p>
                </div>
            </div>
        `;
    }

    // Escape HTML helper
    function escapeHtml(string) {
        const pre = document.createElement('pre');
        const text = document.createTextNode(string);
        pre.appendChild(text);
        return pre.innerHTML;
    }
</script>
