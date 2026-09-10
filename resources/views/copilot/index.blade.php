<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" href="{{ asset('images/favicon_darkgreen.ico?v=2') }}" type="image/x-icon">
    <link rel="icon" href="{{ asset('images/favicon_darkgreen.ico?v=2') }}" type="image/x-icon">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>RiMS Copilot - ผู้ช่วย AI อัจฉริยะ (Text-to-SQL & Knowledge)</title>
    <!-- Google Fonts: Prompt & Sarabun -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- SheetJS for Excel Export -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

    <style>
        :root {
            --primary-brand: #0d6efd;
            --primary-dark: #0a58ca;
            --sidebar-bg: #f8fafc;
            --chat-bg: #f1f5f9;
            --bubble-user: linear-gradient(135deg, #0d6efd 0%, #0056b3 100%);
            --header-height: 60px;
        }

        body {
            font-family: 'Prompt', 'Sarabun', sans-serif;
            background-color: #ffffff;
            color: #1e293b;
            height: 100vh;
            margin: 0;
            overflow: hidden;
        }

        /* Top Navbar */
        .top-navbar {
            height: var(--header-height);
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            z-index: 100;
        }

        .brand-logo {
            font-weight: 700;
            font-size: 1.15rem;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .badge-copilot-ai {
            background: linear-gradient(135deg, #ef4444 0%, #f97316 100%);
            color: #ffffff;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 20px;
            letter-spacing: 0.5px;
        }

        /* App Container Layout */
        .app-layout {
            display: flex;
            height: calc(100vh - var(--header-height));
            overflow: hidden;
            position: relative;
        }

        /* Left Sidebar */
        .chat-sidebar {
            width: 290px;
            background-color: var(--sidebar-bg);
            border-right: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
            flex-shrink: 0;
        }

        .chat-sidebar.collapsed {
            margin-left: -290px;
        }

        .sidebar-header {
            padding: 16px;
            border-bottom: 1px solid #e2e8f0;
        }

        .btn-new-chat {
            background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
            color: #ffffff;
            font-weight: 600;
            border-radius: 12px;
            padding: 10px 16px;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: none;
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.25);
            transition: all 0.2s ease;
        }

        .btn-new-chat:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(13, 110, 253, 0.35);
            color: #ffffff;
        }

        .sidebar-section-title {
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            padding: 12px 16px 6px;
            letter-spacing: 0.5px;
        }

        .session-list {
            flex: 1;
            overflow-y: auto;
            padding: 8px 12px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .session-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 9px 12px;
            border-radius: 10px;
            color: #334155;
            font-size: 0.85rem;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
            border: 1px solid transparent;
        }

        .session-item:hover {
            background-color: #e2e8f0;
            color: #0f172a;
        }

        .session-item.active {
            background-color: #e0e7ff;
            color: #3730a3;
            font-weight: 600;
            border-color: #c7d2fe;
        }

        .session-title-text {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            flex: 1;
        }

        .session-del-btn {
            color: #94a3b8;
            opacity: 0;
            background: none;
            border: none;
            padding: 2px 4px;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .session-item:hover .session-del-btn {
            opacity: 1;
        }

        .session-del-btn:hover {
            color: #ef4444;
            background-color: #fee2e2;
        }

        .sidebar-footer {
            padding: 12px 16px;
            border-top: 1px solid #e2e8f0;
            background: #ffffff;
        }

        .btn-clear-all {
            color: #ef4444;
            border: 1px solid #fca5a5;
            background: #fff;
            border-radius: 10px;
            width: 100%;
            padding: 8px;
            font-size: 0.82rem;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-clear-all:hover {
            background: #fef2f2;
            border-color: #ef4444;
            color: #b91c1c;
        }

        /* Main Workspace */
        .chat-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            background-color: #ffffff;
            overflow: hidden;
            position: relative;
        }

        /* Sub-Header inside Workspace */
        .workspace-header {
            padding: 12px 24px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #ffffff;
        }

        .bot-status-pill {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .bot-avatar-circle {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 1.15rem;
            box-shadow: 0 4px 10px rgba(13, 110, 253, 0.2);
        }

        .bot-title-group h5 {
            font-size: 1rem;
            font-weight: 700;
            margin: 0;
            color: #0f172a;
        }

        .status-online {
            font-size: 0.72rem;
            color: #16a34a;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .pulse-green {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background-color: #22c55e;
            box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.3);
        }

        .scope-selector-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-select-scope {
            border-radius: 10px;
            font-size: 0.85rem;
            padding: 6px 30px 6px 12px;
            border: 1px solid #cbd5e1;
            font-weight: 600;
            color: #334155;
            background-color: #f8fafc;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            cursor: pointer;
        }

        .form-select-scope:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15);
        }

        /* Message Stream Area */
        .chat-body {
            flex: 1;
            overflow-y: auto;
            padding: 24px 32px;
            background-color: #f8fafc;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .msg-row {
            display: flex;
            gap: 12px;
            width: 100%;
        }

        .msg-user {
            justify-content: flex-end;
        }

        .msg-bot {
            justify-content: flex-start;
        }

        .user-bubble {
            background: var(--bubble-user);
            color: #ffffff;
            padding: 12px 18px;
            border-radius: 18px 18px 4px 18px;
            max-width: 70%;
            font-size: 0.92rem;
            line-height: 1.55;
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2);
            word-break: break-word;
        }

        .bot-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 18px 18px 18px 4px;
            padding: 18px 22px;
            max-width: 88%;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .bot-avatar-sm {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #e0e7ff;
            color: #4f46e5;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 0.9rem;
        }

        .summary-text {
            font-size: 0.92rem;
            line-height: 1.65;
            color: #1e293b;
        }

        /* Follow-up suggestions */
        .suggestion-group {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 4px;
        }

        .suggestion-chip {
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            color: #334155;
            font-size: 0.78rem;
            padding: 4px 12px;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .suggestion-chip:hover {
            background-color: #e2e8f0;
            color: #0d6efd;
            border-color: #93c5fd;
            transform: translateY(-1px);
        }

        /* Interactive Data Table */
        .table-card-container {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            background: #ffffff;
            margin-top: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
        }

        .table-toolbar {
            padding: 8px 14px;
            background-color: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .table-responsive-custom {
            max-height: 380px;
            overflow: auto;
        }

        .data-table-copilot {
            width: 100%;
            margin-bottom: 0;
            font-size: 0.82rem;
            border-collapse: separate;
            border-spacing: 0;
        }

        .data-table-copilot thead th {
            position: sticky;
            top: 0;
            background: #e2e8f0;
            color: #1e293b;
            font-weight: 700;
            padding: 10px 14px;
            border-bottom: 2px solid #cbd5e1;
            white-space: nowrap;
            z-index: 2;
        }

        .data-table-copilot tbody td {
            padding: 8px 14px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            white-space: nowrap;
        }

        .data-table-copilot tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .data-table-copilot tbody tr:hover {
            background-color: #eff6ff;
        }

        /* SQL Query Accordion / Badge */
        .sql-meta-badge {
            font-size: 0.72rem;
            color: #64748b;
            background: #f1f5f9;
            padding: 3px 8px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            cursor: pointer;
            border: 1px solid #e2e8f0;
        }

        .sql-meta-badge:hover {
            background: #e2e8f0;
            color: #0f172a;
        }

        /* Bottom Input Bar */
        .chat-input-area {
            padding: 16px 24px 12px;
            background-color: #ffffff;
            border-top: 1px solid #e2e8f0;
        }

        .input-box-wrapper {
            display: flex;
            align-items: flex-end;
            gap: 12px;
            background: #ffffff;
            border: 1.5px solid #cbd5e1;
            border-radius: 16px;
            padding: 8px 14px;
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
        }

        .input-box-wrapper:focus-within {
            border-color: #0d6efd;
            box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.12);
        }

        .chat-textarea {
            flex: 1;
            border: none;
            outline: none;
            resize: none;
            max-height: 120px;
            font-size: 0.92rem;
            font-family: inherit;
            color: #1e293b;
            line-height: 1.5;
            background: transparent;
        }

        .btn-send {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
            border: none;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            box-shadow: 0 4px 10px rgba(13, 110, 253, 0.3);
            transition: all 0.2s ease;
            flex-shrink: 0;
        }

        .btn-send:hover {
            transform: scale(1.06);
            color: #ffffff;
        }

        .input-footer-info {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 6px;
            font-size: 0.72rem;
            color: #64748b;
        }

        .security-badge {
            color: #059669;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* Typing Animation Indicator */
        .typing-bubble {
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 10px 16px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            width: fit-content;
        }

        .dot-pulse {
            width: 7px;
            height: 7px;
            background-color: #0d6efd;
            border-radius: 50%;
            animation: dotBounce 1.4s infinite ease-in-out both;
        }

        .dot-pulse:nth-child(1) { animation-delay: -0.32s; }
        .dot-pulse:nth-child(2) { animation-delay: -0.16s; }

        @keyframes dotBounce {
            0%, 80%, 100% { transform: scale(0); }
            40% { transform: scale(1); }
        }
    </style>
</head>
<body>

    <!-- Top Navbar -->
    <header class="top-navbar">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-sm btn-light border p-1 px-2" id="btnToggleSidebar" title="ซ่อน/แสดงแถบข้าง">
                <i class="bi bi-list fs-5"></i>
            </button>
            <a href="{{ route('home') }}" class="brand-logo">
                <img src="{{ asset('images/logo_hrims.png?v=2') }}" alt="RiMS Logo" style="height: 30px; width: auto; object-fit: contain;">
                <span>RiMS Copilot</span>
            </a>
            <span class="badge-copilot-ai">Copilot AI</span>
        </div>

        <div class="d-flex align-items-center gap-2">
            @if(request('in_modal'))
            <!-- Pop-out to New Window / Tab Icon Button (Dual Monitor) -->
            <button type="button" class="btn btn-sm btn-light border rounded-circle shadow-sm" onclick="popOutToNewTab()" title="เปิดหน้าต่างใหม่ (สำหรับ 2 จอ)" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                <i class="bi bi-box-arrow-up-right fs-6 text-secondary"></i>
            </button>
            <!-- Close Modal Icon Button -->
            <button type="button" class="btn btn-sm btn-light border rounded-circle shadow-sm" onclick="closeModalFromInside()" title="ปิดหน้าต่าง" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                <i class="bi bi-x-lg fs-6 text-secondary"></i>
            </button>
            @else
            <!-- User Dropdown (Stand-alone Page Mode only) -->
            <div class="dropdown">
                <button class="btn btn-sm btn-light border dropdown-toggle d-flex align-items-center gap-2 rounded-pill px-3 py-1.5" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle text-secondary fs-5"></i>
                    <span class="fw-semibold small">{{ $user->name ?? 'ผู้ใช้งาน' }}</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3">
                    <li><a class="dropdown-item py-2 small" href="{{ route('home') }}"><i class="bi bi-house me-2"></i>กลับหน้าหลัก</a></li>
                    @if(auth()->user()->status === 'admin')
                    <li><a class="dropdown-item py-2 small" href="{{ route('admin.rag.index') }}"><i class="bi bi-book me-2"></i>คลังความรู้ RAG</a></li>
                    <li><a class="dropdown-item py-2 small" href="{{ route('admin.main_setting') }}"><i class="bi bi-gear me-2"></i>ตั้งค่าระบบ</a></li>
                    @endif
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item py-2 small text-danger"><i class="bi bi-box-arrow-right me-2"></i>ออกจากระบบ</button>
                        </form>
                    </li>
                </ul>
            </div>
            @endif
        </div>
    </header>

    <!-- App Layout Container -->
    <div class="app-layout">
        <!-- Left Sidebar (Chat History) -->
        <aside class="chat-sidebar" id="chatSidebar">
            <div class="sidebar-header">
                <button type="button" class="btn-new-chat" onclick="handleCreateNewChat()">
                    <i class="bi bi-plus-lg"></i>
                    <span>การสนทนาใหม่</span>
                </button>
            </div>

            <div class="sidebar-section-title">ประวัติการสนทนา</div>

            <div class="session-list" id="sessionListContainer">
                <!-- Session items will be dynamically loaded here -->
                <div class="text-center text-muted py-4 small">
                    <div class="spinner-border spinner-border-sm text-secondary mb-2" role="status"></div>
                    <div>กำลังโหลดประวัติ...</div>
                </div>
            </div>

            <div class="sidebar-footer">
                <button type="button" class="btn-clear-all" onclick="handleClearAllChats()">
                    <i class="bi bi-trash3 me-1"></i> ล้างประวัติสนทนาทั้งหมด
                </button>
            </div>
        </aside>

        <!-- Main Chat Workspace -->
        <main class="chat-main">
            <!-- Workspace Sub-Header -->
            <div class="workspace-header">
                <div class="bot-status-pill">
                    <div class="bot-avatar-circle">
                        <i class="bi bi-robot"></i>
                    </div>
                    <div class="bot-title-group">
                        <h5 class="d-flex align-items-center gap-2">
                            <span>RiMS Copilot</span>
                        </h5>
                        <div class="status-online">
                            <span class="pulse-green"></span> Online พร้อมใช้งาน
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <!-- Database / Scope Selector -->
                    <div class="scope-selector-group">
                        <label for="scopeSelector" class="small fw-bold text-muted mb-0 d-none d-md-inline"><i class="bi bi-database me-1"></i>ระบบ / ขอบเขต:</label>
                        <select id="scopeSelector" class="form-select form-select-sm form-select-scope" onchange="handleScopeChange(this.value)">
                            <option value="auto" {{ $initialContext === 'auto' ? 'selected' : '' }}>🌐 ตรวจหาอัตโนมัติ</option>
                            <option value="hosfin" {{ $initialContext === 'hosfin' ? 'selected' : '' }}>🏢 HosFin</option>
                            <option value="hosxp" {{ $initialContext === 'hosxp' ? 'selected' : '' }}>🏥 HOSxP Setting</option>
                            <option value="rag" {{ $initialContext === 'rag' ? 'selected' : '' }}>📚 Knowledge Base</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Chat Message Stream -->
            <div class="chat-body" id="chatMessagesBox">
                <!-- Welcome greeting message -->
                <div class="msg-row msg-bot" id="welcomeGreetingCard">
                    <div class="bot-avatar-sm"><i class="bi bi-robot"></i></div>
                    <div class="bot-card">
                        <div>
                            <h6 class="fw-bold mb-1" id="welcomeScopeTitle">สวัสดีครับ! ผมคือ RiMS Copilot 🩺✨</h6>
                            <p class="text-secondary small mb-0" id="welcomeScopeDesc">
                                ผู้ช่วย AI อัจฉริยะด้านการวิเคราะห์ฐานข้อมูลโรงพยาบาล สามารถสืบค้นข้อมูลด้วยภาษาไทยผ่าน <strong>Text-to-SQL</strong> 
                                ทั้งฐานข้อมูล <strong>HRiMS (งานบริหาร/การเงิน/พัสดุ)</strong> และ <strong>HOSxP (เวชระเบียน/สถิติการรักษา)</strong> 
                                รวมถึงค้นหาเอกสารและระเบียบในคลังความรู้ RAG ได้อย่างแม่นยำและปลอดภัยครับ
                            </p>
                        </div>

                        <div>
                            <small class="text-muted fw-bold d-block mb-1"><i class="bi bi-lightbulb text-warning me-1"></i> ตัวอย่างคำถามแนะนำ:</small>
                            <div class="suggestion-group" id="welcomeSuggestionChips">
                                <span class="suggestion-chip" onclick="handleQuickPrompt('ขอยอดสรุปเจ้าหนี้การค้าแยกตามบริษัท')">📌 สรุปยอดหนี้เจ้าหนี้การค้า</span>
                                <span class="suggestion-chip" onclick="handleQuickPrompt('ขอยอดผู้ป่วยนอกวันนี้ แยกตามแผนก')">🏥 ยอดผู้ป่วยนอกวันนี้แยกแผนก</span>
                                <span class="suggestion-chip" onclick="handleQuickPrompt('ลูกหนี้ค่ารักษาพยาบาลค้างชำระแยกตามสิทธิ')">👛 ลูกหนี้ค้างชำระแยกตามสิทธิ</span>
                                <span class="suggestion-chip" onclick="handleQuickPrompt('ตรวจรายการค่าบริการที่ยังไม่ผูกรหัส ADP')">💊 ค่าบริการที่ยังไม่ผูก ADP</span>
                                <span class="suggestion-chip" onclick="handleQuickPrompt('สรุปยอดหนี้องค์การเภสัชกรรม GPO')">🏢 ยอดหนี้ อภ. (GPO)</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Typing Indicator (Hidden by default) -->
            <div id="typingIndicatorRow" class="px-4 py-2 d-none">
                <div class="d-flex align-items-center gap-2">
                    <div class="bot-avatar-sm"><i class="bi bi-robot"></i></div>
                    <div class="typing-bubble">
                        <span class="dot-pulse"></span>
                        <span class="dot-pulse"></span>
                        <span class="dot-pulse"></span>
                        <small class="text-muted ms-2" style="font-size: 0.78rem;">AI กำลังค้นหาข้อมูลและประมวลผลคำสั่ง SQL...</small>
                    </div>
                </div>
            </div>

            <!-- Bottom Input Bar -->
            <div class="chat-input-area">
                <form id="chatForm" onsubmit="handleSendMessage(event)">
                    <div class="input-box-wrapper">
                        <textarea id="chatInputText" class="chat-textarea" rows="1" placeholder="พิมพ์คำถามภาษาไทย เช่น ขอยอดผู้ป่วยนอกวันนี้, รายชื่อเจ้าหน้าที่, หรือแนวทาง CPG... (กด Enter เพื่อส่ง, Shift+Enter ขึ้นบรรทัดใหม่)" required></textarea>
                        <button type="submit" id="btnSendSubmit" class="btn-send" title="ส่งคำถาม">
                            <i class="bi bi-send-fill"></i>
                        </button>
                    </div>
                    <div class="input-footer-info">
                        <span class="security-badge">
                            <i class="bi bi-shield-check"></i> ปลอดภัย 100%: รันเฉพาะ SELECT และจำกัด 100 รายการสูงสุด
                        </span>
                        <span>RiMS Copilot AI Hospital Assistant</span>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // State Management
        let currentSessionId = null;
        let currentSessionScope = 'auto';
        let allUserSessions = [];
        let isProcessing = false;
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const isUserAdmin = {{ (auth()->check() && auth()->user()->status === 'admin') ? 'true' : 'false' }};

        // Comprehensive Dictionary: Database Column Names -> User-friendly Thai Business Headers
        const COLUMN_TITLE_MAP = {
            // Overview & Summary Aliases
            'total_ap_bills': 'จำนวนบิลเจ้าหนี้',
            'total_ap_unpaid': 'ยอดหนี้ค้างจ่าย (บาท)',
            'total_ar_debtors': 'จำนวนลูกหนี้',
            'total_ar_outstanding': 'ยอดหนี้คงค้าง (บาท)',
            'total_tb_rows': 'จำนวนรายการงบทดลอง',
            'latest_tb_year': 'ปีงบประมาณล่าสุด',
            'latest_cash_balance': 'เงินสดคงเหลือสะสม (บาท)',
            'total_vouchers': 'จำนวนใบสำคัญบันทึกบัญชี',
            'total_voucher': 'จำนวนใบสำคัญบันทึกบัญชี',
            'collection_rate_percent': 'อัตราการได้รับชดเชย (%)',
            'total_remaining_debt': 'ยอดหนี้คงเหลือรวม (บาท)',
            'total_outstanding': 'ยอดหนี้คงค้างรวม (บาท)',
            'debtor_count': 'จำนวนลูกหนี้/หน่วยงาน',
            'bill_count': 'จำนวนบิล',
            'total_bills': 'จำนวนบิลทั้งหมด',

            // AP Bills (hosfin_gl_ap_bills)
            'id': 'ลำดับ',
            'vendor_name': 'ชื่อบริษัท/เจ้าหนี้',
            'category': 'หมวดหมู่',
            'bill_no': 'เลขที่บิล/ใบแจ้งหนี้',
            'bill_date': 'วันที่ในบิล',
            'account_code': 'รหัสบัญชี',
            'account_name': 'ชื่อบัญชี',
            'total_credit': 'ยอดหนี้รวม (บาท)',
            'total_debit': 'ยอดชำระแล้ว (บาท)',
            'remaining_debt': 'ยอดหนี้คงเหลือ (บาท)',
            'fiscal_year': 'ปีงบประมาณ',
            'is_paid': 'สถานะการชำระ',

            // AR Debtors (hosfin_gl_ar_debtors)
            'debtor_type': 'ประเภทลูกหนี้/สิทธิ',
            'total_billed': 'ยอดเรียกเก็บทั้งหมด (บาท)',
            'total_collected': 'ยอดที่ชดเชยแล้ว (บาท)',
            'outstanding_balance': 'ยอดหนี้คงค้าง (บาท)',
            'fiscal_month': 'เดือนงบประมาณ',

            // Trial Balance (hosfin_trial_balance)
            'acc_year': 'ปีบัญชี',
            'acc_month': 'เดือนบัญชี',
            'acc_period': 'งวดบัญชี',
            'main_account_code': 'รหัสบัญชีหลัก',
            'debit_bf': 'เดบิตยกมา (บาท)',
            'credit_bf': 'เครดิตยกมา (บาท)',
            'debit_month': 'เดบิตงวดนี้ (บาท)',
            'credit_month': 'เครดิตงวดนี้ (บาท)',
            'debit_net': 'เดบิตสุทธิยกไป (บาท)',
            'credit_net': 'เครดิตสุทธิยกไป (บาท)',
            'import_filename': 'ชื่อไฟล์นำเข้า',

            // Daily Summaries (hosfin_gl_daily_summaries)
            'summary_date': 'วันที่สรุปข้อมูล',
            'total_income': 'รายรับรวม (บาท)',
            'total_expense': 'รายจ่ายรวม (บาท)',
            'net_cash_flow': 'กระแสเงินสดสุทธิ (บาท)',
            'cash_balance': 'เงินสดคงเหลือสะสม (บาท)',
            'voucher_count': 'จำนวนใบสำคัญ',

            // Cost Summaries (hosfin_gl_cost_summaries)
            'lc_amount': 'ค่าแรงบุคลากร LC (บาท)',
            'mc_amount': 'ค่าวัสดุและยา MC (บาท)',
            'cc_amount': 'ค่าลงทุน/เสื่อม CC (บาท)',
            'other_cost': 'ต้นทุนอื่นๆ (บาท)',
            'total_cost': 'ต้นทุนรวมทั้งหมด (บาท)',

            // Monthly Balances (hosfin_gl_monthly_balances)
            'beginning_debit': 'เดบิตยกมาต้นงวด (บาท)',
            'beginning_credit': 'เครดิตยกมาต้นงวด (บาท)',
            'period_debit': 'เดบิตประจำงวด (บาท)',
            'period_credit': 'เครดิตประจำงวด (บาท)',
            'ending_debit': 'เดบิตสุทธิปลายงวด (บาท)',
            'ending_credit': 'เครดิตสุทธิปลายงวด (บาท)',

            // Journals & Items (hosfin_gl_journals, hosfin_gl_journal_items)
            'voucher_no': 'เลขที่ใบสำคัญ',
            'voucher_date': 'วันที่ลงบัญชี',
            'journal_type': 'ประเภทสมุดรายวัน',
            'description': 'คำอธิบายรายการ',
            'posted_status': 'สถานะผ่านรายการ',
            'apar': 'ข้อมูลเจ้าหนี้/ลูกหนี้',
            'item_no': 'ลำดับรายการ',
            'debit': 'เดบิต (บาท)',
            'credit': 'เครดิต (บาท)',
            'department': 'แผนก/ศูนย์ต้นทุน',

            // Accounts (hosfin_gl_accounts)
            'account_type': 'หมวดบัญชี',
            'account_category': 'หมวดหมู่บัญชีย่อย',
            'normal_balance': 'ด้านปกติ',
            'is_active': 'สถานะเปิดใช้งาน',
            'cost_type': 'ประเภทต้นทุน',
            'service_type': 'ประเภทบริการ',

            // Subledgers (hosfin_gl_subledgers)
            'subledger_code': 'รหัสบัญชีย่อย',
            'raw_note': 'หมายเหตุ',

            // DTL & Sync Logs
            'group_code': 'รหัสกลุ่ม',
            'group_name': 'ชื่อกลุ่ม',
            'sync_type': 'ประเภทการซิงค์',
            'records_count': 'จำนวนรายการที่ประมวลผล',
            'status': 'สถานะ',
            'message': 'ข้อความผลการทำงาน',
            'duration_seconds': 'เวลาที่ใช้ (วินาที)',

            // HOSxP Master
            'icode': 'รหัสค่าบริการ',
            'name': 'ชื่อรายการ',
            'price': 'ราคาปกติ (บาท)',
            'price2': 'ราคา 2 (บาท)',
            'price3': 'ราคา 3 (บาท)',
            'income': 'หมวดค่ารักษา',
            'nhso_adp_code': 'รหัสมาตรฐาน ADP สปสช.',
            'billcode': 'รหัสเบิกกรมบัญชีกลาง',
            'unit': 'หน่วยนับ',
            'istat': 'สถานะการใช้งาน',
            'istatus': 'สถานะการใช้งาน',
            'pttype': 'รหัสสิทธิการรักษา',
            'pcode': 'กลุ่มสิทธิมาตรฐาน',
            'hipdata_code': 'รหัสส่งออก 16 แฟ้ม',
            'max_debt_money': 'เพดานหนี้สูงสุด (บาท)',
            'paidst': 'สถานะการชำระเงิน',
            'isuse': 'สถานะเปิดใช้งาน',
            'code': 'รหัสแพทย์',
            'licenseno': 'เลขที่ใบประกอบวิชาชีพ',
            'council_code': 'รหัสสภาวิชาชีพ',
            'position_id': 'รหัสตำแหน่ง',
            'spclty': 'รหัสสาขาความเชี่ยวชาญ',
            'clinic': 'รหัสคลินิก',
            'cid': 'เลขประจำตัวประชาชน',
            'active': 'สถานะปฏิบัติงาน',

            // HOSxP Pricing by Rights (pttype_items_price) & Opitemrece
            'pttype_items_price_id': 'รหัสราคาตามสิทธิ',
            'items_table_name': 'ประเภทตารางรายการ',
            'items_table_code': 'รหัสรายการ (icode)',
            'pttype_price_group_id': 'รหัสกลุ่มราคาตามสิทธิ',
            'discount_percent': 'ส่วนลดตามสิทธิ (%)',
            'unitprice': 'ราคาต่อหน่วย (บาท)',
            'sum_price': 'ยอดเงินรวม (บาท)',
            'qty': 'จำนวน',
            'vn': 'เลขที่รับบริการ (VN)',
            'an': 'เลขที่ผู้ป่วยใน (AN)',
            'hn': 'เลขประจำตัวผู้ป่วย (HN)',

            // General aggregations
            'count': 'จำนวนรายการ',
            'cnt': 'จำนวนรายการ',
            'total': 'ยอดรวม',
            'sum': 'ยอดรวม (บาท)',
            'sum_amount': 'ยอดเงินรวม (บาท)',
            'total_amount': 'ยอดเงินรวม (บาท)',
            'avg_amount': 'ยอดเงินเฉลี่ย (บาท)'
        };

        // Format raw column to friendly Thai label
        function formatColumnHeader(col) {
            if (!col) return '';
            const clean = String(col).trim();
            if (COLUMN_TITLE_MAP[clean]) return COLUMN_TITLE_MAP[clean];
            const lower = clean.toLowerCase();
            if (COLUMN_TITLE_MAP[lower]) return COLUMN_TITLE_MAP[lower];

            let label = clean
                .replace(/^total_/i, 'ยอดรวม ')
                .replace(/^sum_/i, 'ยอดรวม ')
                .replace(/^count_/i, 'จำนวน ')
                .replace(/^latest_/i, 'ล่าสุด ')
                .replace(/_/g, ' ');
            return label;
        }

        // Format cell value: commas for numbers, currency decimal, dash for null
        function formatCellValue(val, col) {
            if (val === null || val === undefined || val === '') return '-';
            const colLower = String(col || '').toLowerCase();
            // Don't format year, code, id, phone, cid with commas
            if (colLower.includes('year') || colLower.includes('code') || colLower.includes('cid') || colLower === 'id' || colLower.includes('no') || colLower.includes('phone')) {
                return escapeHtml(val);
            }
            if (typeof val === 'number' || (!isNaN(val) && !isNaN(parseFloat(val)) && isFinite(val) && String(val).trim() !== '')) {
                const num = parseFloat(val);
                if (String(val).includes('.') || colLower.includes('amount') || colLower.includes('debt') || colLower.includes('balance') || colLower.includes('credit') || colLower.includes('debit') || colLower.includes('income') || colLower.includes('expense') || colLower.includes('cost') || colLower.includes('price') || colLower.includes('billed') || colLower.includes('collected') || colLower.includes('outstanding') || colLower.includes('unpaid') || colLower.includes('rate') || colLower.includes('percent')) {
                    return num.toLocaleString('th-TH', { minimumFractionDigits: (num % 1 !== 0) ? 2 : 0, maximumFractionDigits: 2 });
                }
                return num.toLocaleString('th-TH');
            }
            return escapeHtml(val);
        }

        // Scope Definitions: Titles, Descriptions, Placeholders & Example Chips
        const SCOPE_CONFIG = {
            hosfin: {
                title: 'RiMS Copilot 🏢 ระบบการเงิน HosFin',
                desc: 'สืบค้นข้อมูลบัญชี การเงิน เจ้าหนี้ ลูกหนี้ ผังบัญชี และงบทดลอง พร้อมค้นหาเทียบเคียงระเบียบการเงินจากคลังความรู้ RAG อย่างแม่นยำและปลอดภัย',
                placeholder: 'พิมพ์คำถามการเงิน เช่น สรุปยอดหนี้เจ้าหนี้การค้า, ลูกหนี้ค้างชำระตามสิทธิ, ขอยอดงบทดลองล่าสุด...',
                chips: [
                    '📌 สรุปยอดหนี้เจ้าหนี้การค้าแยกตามบริษัท',
                    '🏢 ยอดหนี้ค้างจ่ายองค์การเภสัชกรรม GPO',
                    '👛 ลูกหนี้ค่ารักษาพยาบาลค้างชำระแยกตามสิทธิ',
                    '📊 ขอยอดงบทดลอง (Trial Balance) ล่าสุด',
                    '📑 สรุปการลงสมุดรายวันทั่วไป (Journal Voucher)'
                ]
            },
            hosxp: {
                title: 'RiMS Copilot 🏥 ข้อมูลพื้นฐาน HOSxP Setting',
                desc: 'ตรวจสอบการตั้งค่าข้อมูลพื้นฐาน HOSxP ของโรงพยาบาลพร้อมค้นหาเทียบเคียงระเบียบและมาตรฐานจากคลังความรู้ RAG',
                placeholder: 'พิมพ์คำถามตั้งค่า เช่น ตรวจสอบค่าบริการที่ยังไม่ผูกรหัส ADP, รายชื่อแพทย์ที่ไม่มีเลข ว., การตั้งค่าสิทธิ pttype...',
                chips: [
                    '💊 ตรวจสอบรายการค่าบริการที่ยังไม่ผูกรหัส ADP',
                    '📋 ตรวจสอบการตั้งค่าสิทธิการรักษาที่ใช้งาน',
                    '👨‍⚕️ รายชื่อแพทย์และผู้ตรวจรักษาที่ไม่มีเลขที่ใบประกอบวิชาชีพ',
                    '🩺 รายการหัตถการและค่าบริการทางการแพทย์',
                    '🔍 ตรวจสอบรหัสมาตรฐาน 16 แฟ้มในรายการค่าบริการ'
                ]
            },
            rag: {
                title: 'RiMS Copilot 📚 Knowledge Base',
                desc: 'ค้นหาข้อมูล กฎระเบียบ คู่มือปฏิบัติงาน และแนวทางเวชปฏิบัติ CPG จากเอกสารในคลังความรู้ Knowledge Base ด้วยระบบ Semantic Vector Search โดยตรง',
                placeholder: 'พิมพ์คำถามเพื่อค้นหาในเอกสาร เช่น ระเบียบเงินบำรุง, แนวทาง CPG Sepsis, หลักเกณฑ์การเบิกจ่าย...',
                chips: [
                    '📖 ระเบียบเงินบำรุงโรงพยาบาลฉบับล่าสุด',
                    '🩺 แนวทางการวินิจฉัยและบันทึกรหัสโรค Sepsis CPG',
                    '📋 แนวทางการบันทึกและตรวจสอบรหัส C-Code',
                    '💉 แนวทางการเบิกจ่ายกรณีฟอกไต (Hemodialysis)',
                    '📁 โครงสร้างข้อมูลมาตรฐาน 16 แฟ้ม (e-Claim)'
                ]
            },
            auto: {
                title: 'RiMS Copilot 🌐 ตรวจหาอัตโนมัติ',
                desc: 'ผู้ช่วย AI อัจฉริยะวิเคราะห์คำถามและเลือกสืบค้นจาก HosFin, HOSxP Setting หรือ Knowledge Base ให้อัตโนมัติตามบริบท',
                placeholder: 'พิมพ์คำถามภาษาไทย เช่น ขอยอดหนี้เจ้าหนี้การค้า, ค่าบริการที่ยังไม่ผูก ADP, หรือระเบียบเงินบำรุง...',
                chips: [
                    '📌 สรุปยอดหนี้เจ้าหนี้การค้าแยกตามบริษัท',
                    '💊 ตรวจสอบรายการค่าบริการที่ยังไม่ผูกรหัส ADP',
                    '📖 ระเบียบเงินบำรุงโรงพยาบาลฉบับล่าสุด',
                    '👛 ลูกหนี้ค่ารักษาพยาบาลค้างชำระตามสิทธิ',
                    '👨‍⚕️ รายชื่อแพทย์และเลขที่ใบประกอบวิชาชีพ'
                ]
            }
        };

        // Update UI when Scope Changes
        function updateScopeContextUi(scope) {
            const config = SCOPE_CONFIG[scope] || SCOPE_CONFIG.auto;

            const titleEl = document.getElementById('welcomeScopeTitle');
            if (titleEl) titleEl.innerHTML = config.title;

            const descEl = document.getElementById('welcomeScopeDesc');
            if (descEl) descEl.innerHTML = config.desc;

            const chipsContainer = document.getElementById('welcomeSuggestionChips');
            if (chipsContainer) {
                chipsContainer.innerHTML = config.chips.map(chip => 
                    `<span class="suggestion-chip" onclick="handleQuickPrompt('${escapeHtml(chip)}')">${escapeHtml(chip)}</span>`
                ).join('');
            }

            const inputEl = document.getElementById('chatInputText');
            if (inputEl) {
                inputEl.placeholder = config.placeholder + ' (กด Enter เพื่อส่ง, Shift+Enter ขึ้นบรรทัดใหม่)';
            }
        }

        // Scope Dropdown Change Handler
        function handleScopeChange(scope) {
            currentSessionScope = scope;
            updateScopeContextUi(scope);
            filterSessionListByScope(scope);

            // If currently viewing a session from a different scope, reset to fresh chat for this scope
            const welcome = document.getElementById('welcomeGreetingCard');
            if (welcome && welcome.style.display !== 'none') {
                // Already in welcome mode, just keep it
            } else if (currentSessionId) {
                // If the active chat was for another scope, offer clear view
                handleCreateNewChat();
            }
        }

        // Toggle Sidebar
        document.getElementById('btnToggleSidebar').addEventListener('click', function() {
            document.getElementById('chatSidebar').classList.toggle('collapsed');
        });

        // Auto-resize Textarea
        const chatInput = document.getElementById('chatInputText');
        chatInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });

        // Keydown Handler: Enter to send, Shift+Enter for newline
        chatInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                handleSendMessage();
            }
        });

        // Pop out from Modal to a New Browser Tab (Dual Monitor support)
        function popOutToNewTab() {
            const scope = document.getElementById('scopeSelector') ? document.getElementById('scopeSelector').value : 'auto';
            let url = '{{ route("copilot.index") }}?context=' + encodeURIComponent(scope);
            if (currentSessionId) {
                url += '&session_id=' + encodeURIComponent(currentSessionId);
            }
            window.open(url, '_blank');
            if (window.parent && typeof window.parent.closeCopilotModal === 'function') {
                window.parent.closeCopilotModal(currentSessionId);
            }
        }

        // Close Modal and return to parent page
        function closeModalFromInside() {
            if (window.parent && typeof window.parent.closeCopilotModal === 'function') {
                window.parent.closeCopilotModal(currentSessionId);
            }
        }

        // Load sessions on init
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const contextParam = urlParams.get('context') || (document.getElementById('scopeSelector') ? document.getElementById('scopeSelector').value : 'auto');

            const scopeSelect = document.getElementById('scopeSelector');
            if (scopeSelect && contextParam) {
                scopeSelect.value = contextParam;
                currentSessionScope = contextParam;
            }

            // Dynamically apply scope titles & chips
            updateScopeContextUi(contextParam);

            const initialSessionId = urlParams.get('session_id');
            loadChatSessions(initialSessionId);
        });

        // Render Session Items in Sidebar
        function renderSessions(sessionsToRender) {
            const container = document.getElementById('sessionListContainer');
            container.innerHTML = '';

            if (!sessionsToRender || sessionsToRender.length === 0) {
                container.innerHTML = '<div class="text-center text-muted py-4 small">ยังไม่มีประวัติการสนทนาในขอบเขตนี้<br>พิมพ์คำถามเพื่อเริ่มการสนทนาใหม่</div>';
                return;
            }

            sessionsToRender.forEach(sess => {
                const item = document.createElement('div');
                item.className = `session-item ${currentSessionId === sess.id ? 'active' : ''}`;
                item.id = `session-item-${sess.id}`;
                item.onclick = (e) => {
                    if (!e.target.closest('.session-del-btn')) {
                        selectSession(sess.id);
                    }
                };

                let scopeBadge = '';
                if (sess.context_scope === 'hosfin') {
                    scopeBadge = '<span class="badge bg-success-subtle text-success border border-success-subtle ms-auto me-1" style="font-size: 0.65rem; padding: 2px 6px;">HosFin</span>';
                } else if (sess.context_scope === 'hosxp') {
                    scopeBadge = '<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle ms-auto me-1" style="font-size: 0.65rem; padding: 2px 6px;">HOSxP</span>';
                } else if (sess.context_scope === 'rag') {
                    scopeBadge = '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle ms-auto me-1" style="font-size: 0.65rem; padding: 2px 6px;">Knowledge Base</span>';
                }

                item.innerHTML = `
                    <i class="bi bi-chat-left-text me-2 text-primary" style="font-size: 0.8rem;"></i>
                    <span class="session-title-text" title="${escapeHtml(sess.title)}">${escapeHtml(sess.title)}</span>
                    ${scopeBadge}
                    <button type="button" class="session-del-btn" title="ลบการสนทนานี้" onclick="handleDeleteSession(${sess.id}, event)">
                        <i class="bi bi-trash3"></i>
                    </button>
                `;
                container.appendChild(item);
            });
        }

        // Filter Session List by Scope
        function filterSessionListByScope(scope) {
            if (!scope || scope === 'auto') {
                renderSessions(allUserSessions);
            } else {
                const filtered = allUserSessions.filter(s => !s.context_scope || s.context_scope === scope || s.context_scope === 'auto');
                renderSessions(filtered);
            }
        }

        // Fetch Sessions
        function loadChatSessions(targetSelectId = null) {
            fetch('{{ route("copilot.sessions") }}', {
                headers: { 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                allUserSessions = data.sessions || [];
                const currentScope = document.getElementById('scopeSelector') ? document.getElementById('scopeSelector').value : 'auto';
                filterSessionListByScope(currentScope);

                // Auto-select session if targetSelectId is passed
                if (targetSelectId) {
                    selectSession(parseInt(targetSelectId));
                }
            })
            .catch(err => {
                console.error('Failed to load sessions:', err);
            });
        }

        // Select and Load Session Messages
        function selectSession(sessionId) {
            if (isProcessing) return;
            currentSessionId = sessionId;

            // Highlight active in sidebar
            document.querySelectorAll('.session-item').forEach(el => el.classList.remove('active'));
            const activeEl = document.getElementById(`session-item-${sessionId}`);
            if (activeEl) activeEl.classList.add('active');

            // Hide welcome card
            const welcome = document.getElementById('welcomeGreetingCard');
            if (welcome) welcome.style.display = 'none';

            // Clear chat body and show spinner
            const box = document.getElementById('chatMessagesBox');
            box.innerHTML = '<div class="text-center text-muted py-5"><div class="spinner-border text-primary"></div><div class="mt-2 small">กำลังโหลดข้อความ...</div></div>';

            fetch(`{{ url('copilot/sessions') }}/${sessionId}`, {
                headers: { 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                box.innerHTML = '';
                if (data.session && data.session.context_scope) {
                    currentSessionScope = data.session.context_scope;
                    const scopeSelect = document.getElementById('scopeSelector');
                    if (scopeSelect && scopeSelect.value !== data.session.context_scope) {
                        scopeSelect.value = data.session.context_scope;
                        updateScopeContextUi(data.session.context_scope);
                    }
                }

                if (!data.messages || data.messages.length === 0) {
                    if (welcome) {
                        box.appendChild(welcome);
                        welcome.style.display = 'flex';
                    }
                    return;
                }

                data.messages.forEach(msg => {
                    renderMessageBubble(msg);
                });
                scrollChatToBottom();
            })
            .catch(err => {
                box.innerHTML = `<div class="alert alert-danger mx-auto mt-4">เกิดข้อผิดพลาดในการโหลดข้อความ: ${err}</div>`;
            });
        }

        // Create New Chat
        function handleCreateNewChat() {
            if (isProcessing) return;
            const scope = document.getElementById('scopeSelector') ? document.getElementById('scopeSelector').value : 'auto';

            fetch('{{ route("copilot.sessions.create") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ context_scope: scope })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    currentSessionId = data.session.id;
                    currentSessionScope = data.session.context_scope || scope;
                    loadChatSessions(currentSessionId);

                    // Reset messages box to welcome
                    const box = document.getElementById('chatMessagesBox');
                    box.innerHTML = '';
                    const welcome = document.getElementById('welcomeGreetingCard');
                    if (welcome) {
                        updateScopeContextUi(scope);
                        box.appendChild(welcome);
                        welcome.style.display = 'flex';
                    }
                    chatInput.focus();
                }
            });
        }

        // Delete Session
        function handleDeleteSession(sessionId, e) {
            if (e) e.stopPropagation();

            Swal.fire({
                title: 'ยืนยันการลบ?',
                text: 'ต้องการลบประวัติการสนทนานี้หรือไม่?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'ลบข้อมูล',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`{{ url('copilot/sessions') }}/${sessionId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            if (currentSessionId === sessionId) {
                                currentSessionId = null;
                                handleCreateNewChat();
                            } else {
                                loadChatSessions();
                            }
                        }
                    });
                }
            });
        }

        // Clear All Sessions
        function handleClearAllChats() {
            Swal.fire({
                title: 'ล้างประวัติทั้งหมด?',
                text: 'ประวัติการสนทนาทั้งหมดของคุณจะถูกลบถาวร ไม่สามารถกู้คืนได้',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'ล้างประวัติทั้งหมด',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('{{ route("copilot.sessions.clear_all") }}', {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        currentSessionId = null;
                        loadChatSessions();
                        handleCreateNewChat();
                    });
                }
            });
        }

        // Quick Prompt Handler
        function handleQuickPrompt(text) {
            chatInput.value = text;
            handleSendMessage();
        }

        // Send Message Handler
        function handleSendMessage(e) {
            if (e) e.preventDefault();
            const text = chatInput.value.trim();
            if (!text || isProcessing) return;

            const scope = document.getElementById('scopeSelector').value;

            // Render User Bubble immediately
            renderMessageBubble({
                role: 'user',
                content: text
            });

            chatInput.value = '';
            chatInput.style.height = 'auto';
            scrollChatToBottom();

            // Show typing indicator
            isProcessing = true;
            document.getElementById('typingIndicatorRow').classList.remove('d-none');
            document.getElementById('btnSendSubmit').disabled = true;

            // Hide initial welcome
            const welcome = document.getElementById('welcomeGreetingCard');
            if (welcome) welcome.style.display = 'none';

            fetch('{{ route("copilot.ask") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    question: text,
                    session_id: currentSessionId,
                    scope: scope
                })
            })
            .then(res => res.json())
            .then(data => {
                isProcessing = false;
                document.getElementById('typingIndicatorRow').classList.add('d-none');
                document.getElementById('btnSendSubmit').disabled = false;

                if (data.session_id) {
                    currentSessionId = data.session_id;
                    loadChatSessions();
                }

                if (data.mode === 'rag') {
                    renderMessageBubble({
                        role: 'assistant',
                        content: data.answer || 'ไม่พบคำตอบในคลังความรู้',
                        sources: data.sources || []
                    });
                } else {
                    // Text-to-SQL Mode
                    if (data.success) {
                        renderMessageBubble({
                            role: 'assistant',
                            content: data.summary,
                            sql_query: data.sql,
                            rows: data.rows || [],
                            columns: data.columns || [],
                            total_rows: data.total_rows || 0,
                            db_target: data.db_target,
                            execution_time_ms: data.execution_time_ms,
                            suggestions: data.suggestions || [],
                            sources: data.sources || []
                        });
                    } else {
                        renderMessageBubble({
                            role: 'assistant',
                            content: 'ขออภัยครับ: ' + (data.message || 'ไม่สามารถค้นหาข้อมูลได้'),
                            sql_query: data.sql
                        });
                    }
                }
                scrollChatToBottom();
            })
            .catch(err => {
                isProcessing = false;
                document.getElementById('typingIndicatorRow').classList.add('d-none');
                document.getElementById('btnSendSubmit').disabled = false;

                renderMessageBubble({
                    role: 'assistant',
                    content: 'เกิดข้อผิดพลาดในการเชื่อมต่อ: ' + err
                });
                scrollChatToBottom();
            });
        }

        // Render Message Bubble
        function renderMessageBubble(msg) {
            const box = document.getElementById('chatMessagesBox');
            const row = document.createElement('div');
            row.className = `msg-row ${msg.role === 'user' ? 'msg-user' : 'msg-bot'}`;

            if (msg.role === 'user') {
                row.innerHTML = `<div class="user-bubble">${escapeHtml(msg.content)}</div>`;
            } else {
                let tableHtml = '';
                if (msg.rows && msg.rows.length > 0 && msg.columns && msg.columns.length > 0) {
                    const tableId = 'table-' + Math.random().toString(36).substring(2, 9);
                    window[tableId + '_data'] = msg.rows;

                    let headers = msg.columns.map(col => {
                        const label = (msg.column_labels && msg.column_labels[col]) ? msg.column_labels[col] : formatColumnHeader(col);
                        return `<th class="text-nowrap">${escapeHtml(label)}</th>`;
                    }).join('');

                    let rowsHtml = msg.rows.slice(0, 100).map(r => {
                        let cells = msg.columns.map(col => `<td>${formatCellValue(r[col], col)}</td>`).join('');
                        return `<tr>${cells}</tr>`;
                    }).join('');

                    tableHtml = `
                        <div class="table-card-container">
                            <div class="table-toolbar">
                                <span class="small fw-bold text-dark"><i class="bi bi-table text-primary me-1"></i> ตารางผลลัพธ์ (${(msg.total_rows || msg.rows.length).toLocaleString('th-TH')} รายการ)</span>
                                <button type="button" class="btn btn-sm btn-outline-success py-0 px-2 small" onclick="exportTableToExcel('${tableId}', '${escapeHtml(msg.db_target || 'data')}')">
                                    <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
                                </button>
                            </div>
                            <div class="table-responsive-custom">
                                <table class="data-table-copilot" id="${tableId}">
                                    <thead><tr>${headers}</tr></thead>
                                    <tbody>${rowsHtml}</tbody>
                                </table>
                            </div>
                        </div>
                    `;
                }

                let sqlHtml = '';
                if (msg.sql_query && isUserAdmin) {
                    const sqlId = 'sql-' + Math.random().toString(36).substring(2, 9);
                    sqlHtml = `
                        <div>
                            <span class="sql-meta-badge" onclick="document.getElementById('${sqlId}').classList.toggle('d-none')">
                                <i class="bi bi-shield-lock text-secondary"></i> ข้อมูลทางเทคนิค (เฉพาะ Admin) ${msg.execution_time_ms ? `(${msg.execution_time_ms} ms)` : ''}
                            </span>
                            <div id="${sqlId}" class="d-none mt-2 p-2 bg-dark text-light rounded font-monospace small" style="font-size: 0.75rem; white-space: pre-wrap; word-break: break-all;">
${escapeHtml(msg.sql_query)}
                            </div>
                        </div>
                    `;
                }

                let suggestionsHtml = '';
                if (msg.suggestions && msg.suggestions.length > 0) {
                    suggestionsHtml = `
                        <div class="mt-2 pt-2 border-top">
                            <small class="text-muted fw-bold d-block mb-1" style="font-size: 0.75rem;"><i class="bi bi-lightbulb text-warning me-1"></i> คำถามแนะนำเจาะลึกต่อ:</small>
                            <div class="suggestion-group">
                                ${msg.suggestions.map(s => `<span class="suggestion-chip" onclick="handleQuickPrompt('${escapeHtml(s)}')">${escapeHtml(s)}</span>`).join('')}
                            </div>
                        </div>
                    `;
                }

                let sourcesHtml = '';
                if (msg.sources && msg.sources.length > 0) {
                    sourcesHtml = `
                        <div class="mt-2 pt-2 border-top small text-success">
                            <strong><i class="bi bi-shield-check me-1"></i> อ้างอิงจากคลังความรู้:</strong>
                            <div class="d-flex flex-wrap gap-1 mt-1">
                                ${msg.sources.map(src => `<span class="badge bg-light text-dark border">${escapeHtml(src.title)}</span>`).join('')}
                            </div>
                        </div>
                    `;
                }

                row.innerHTML = `
                    <div class="bot-avatar-sm"><i class="bi bi-robot"></i></div>
                    <div class="bot-card">
                        <div class="summary-text">${formatMarkdownText(msg.content)}</div>
                        ${sqlHtml}
                        ${tableHtml}
                        ${sourcesHtml}
                        ${suggestionsHtml}
                    </div>
                `;
            }

            box.appendChild(row);
        }

        // Export Data to Excel (using rendered table for Thai headers and formatted numbers)
        function exportTableToExcel(tableId, targetName) {
            const table = document.getElementById(tableId);
            if (!table) {
                Swal.fire('แจ้งเตือน', 'ไม่พบข้อมูลสำหรับส่งออก', 'info');
                return;
            }

            try {
                const ws = XLSX.utils.table_to_sheet(table);
                const wb = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(wb, ws, "Result");
                const filename = `RiMS_Copilot_${targetName}_${new Date().toISOString().slice(0,10)}.xlsx`;
                XLSX.writeFile(wb, filename);
            } catch (err) {
                Swal.fire('ข้อผิดพลาด', 'ไม่สามารถส่งออกไฟล์ได้: ' + err, 'error');
            }
        }

        // Scroll chat to bottom
        function scrollChatToBottom() {
            const box = document.getElementById('chatMessagesBox');
            box.scrollTop = box.scrollHeight;
        }

        // Escape HTML
        function escapeHtml(text) {
            if (text === null || text === undefined) return '';
            return String(text)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // Simple Markdown / Newline Formatter
        function formatMarkdownText(text) {
            if (!text) return '';
            let formatted = escapeHtml(text);
            // Bold
            formatted = formatted.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            // Bullet points
            formatted = formatted.replace(/(?:^|\n)-\s+(.*?)(?=\n|$)/g, '<br>• $1');
            // Line breaks
            formatted = formatted.replace(/\n/g, '<br>');
            return formatted;
        }
    </script>
</body>
</html>
