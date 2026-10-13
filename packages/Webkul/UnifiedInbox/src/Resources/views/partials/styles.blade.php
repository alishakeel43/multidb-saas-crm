@pushOnce('styles')
<style>
    .ui-workspace {
        --ui-bg:#fff;
        --ui-soft:#f6f7fb;
        --ui-line:#e7eaf2;
        --ui-ink:#101c38;
        --ui-muted:#74809a;
        --ui-accent:#444cf4;
        color:var(--ui-ink);
        background:var(--ui-bg);
        border:1px solid var(--ui-line);
        border-radius:16px;
        overflow:hidden;
        min-width:0;
    }
    .ui-workspace * {
        box-sizing:border-box;
    }
    .ui-workspace h1 {
        font-size:28px;
        font-weight:700;
        letter-spacing:-.8px;
    }
    .ui-workspace h2 {
        font-size:16px;
        font-weight:600;
    }
    .ui-workspace p {
        overflow-wrap:anywhere;
    }
    .ui-header {
        padding:24px;
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:16px;
        flex-wrap:wrap;
    }
    .ui-actions {
        display:flex;
        align-items:center;
        gap:10px;
        flex-wrap:wrap;
    }
    .ui-workspace .primary-button {
        background:var(--ui-accent);
        border-color:var(--ui-accent);
        color:white;
        border-radius:9px;
    }
    .ui-workspace .secondary-button {
        background:var(--ui-bg);
        border:1px solid var(--ui-line);
        color:var(--ui-ink);
        border-radius:9px;
    }
    .ui-workspace button,.ui-workspace a,.ui-workspace summary {
        transition:background .15s,box-shadow .15s;
    }
    .ui-workspace button:hover:not(:disabled),.ui-workspace .secondary-button:hover {
        box-shadow:0 3px 12px #444cf41a;
    }
    .ui-workspace :focus-visible {
        outline:3px solid #9499ff;
        outline-offset:3px;
    }
    .ui-workspace button:disabled {
        opacity:.45;
        cursor:not-allowed;
    }
    .ui-muted {
        color:var(--ui-muted);
        font-size:13px;
    }
    .ui-filters {
        padding:0 24px 20px;
        display:flex;
        gap:12px;
        align-items:center;
        flex-wrap:wrap;
    }
    .ui-workspace input:not([type=checkbox]):not([type=hidden]),.ui-workspace select,.ui-workspace textarea {
        border:1px solid var(--ui-line);
        background:var(--ui-soft);
        color:var(--ui-ink);
        border-radius:9px;
        padding:10px 12px;
        max-width:100%;
        min-width:0;
    }
    .ui-search {
        flex:1;
        min-width:180px;
    }
    .ui-search input {
        width:100%;
    }
    .ui-tabs {
        display:flex;
        gap:10px;
        padding:0 24px 20px;
        overflow-x:auto;
        border-bottom:1px solid var(--ui-line);
    }
    .ui-tab {
        display:flex;
        align-items:center;
        gap:9px;
        white-space:nowrap;
        padding:10px 14px;
        border:1px solid var(--ui-line);
        border-radius:12px;
        background:var(--ui-soft);
        font-size:13px;
        font-weight:600;
    }
    .ui-tab[aria-current=page] {
        color:var(--ui-accent);
        background:#eeefff;
        border-color:#bfc3ff;
    }
    .ui-channel {
        display:inline-grid;
        place-items:center;
        width:28px;
        height:28px;
        border-radius:9px;
        background:#e9edff;
        color:#4251ef;
        font-size:12px;
        font-weight:800;
        flex-shrink:0;
    }
    .ui-channel-logo {
        display:block;
        width:18px;
        height:18px;
    }
    .ui-channel-label {
        display:inline-flex;
        align-items:center;
        gap:5px;
    }
    .ui-channel-whatsapp {
        background:#dff7e9;
        color:#139c54;
    }
    .ui-channel-messenger {
        background:#e4efff;
        color:#087cff;
    }
    .ui-channel-instagram {
        background:linear-gradient(135deg,#f9e2ff,#ffe4db);
        color:#cb3580;
    }
    .ui-columns {
        display:grid;
        grid-template-columns:300px minmax(0,1fr);
        min-height:580px;
    }
    .ui-list {
        border-inline-end:1px solid var(--ui-line);
        min-width:0;
    }
    .ui-list-heading {
        padding:20px;
        display:flex;
        align-items:center;
        justify-content:space-between;
    }
    .ui-conversations {
        max-height:680px;
        overflow:auto;
        padding:0 10px 12px;
    }
    .ui-conversation {
        display:flex;
        gap:12px;
        padding:16px 10px;
        border-radius:10px;
        border-bottom:1px solid var(--ui-line);
        color:var(--ui-ink);
    }
    .ui-conversation:hover,.ui-conversation[aria-current=page] {
        background:#eeefff;
    }
    .ui-avatar {
        display:grid;
        place-items:center;
        width:44px;
        height:44px;
        border-radius:50%;
        background:linear-gradient(135deg,#dfe5ff,#f0e5fa);
        color:#505590;
        font-weight:700;
        flex-shrink:0;
    }
    .ui-conversation-copy {
        flex:1;
        min-width:0;
    }
    .ui-conversation-copy strong,.ui-preview {
        display:block;
        overflow:hidden;
        text-overflow:ellipsis;
        white-space:nowrap;
    }
    .ui-preview {
        color:var(--ui-muted);
        font-size:13px;
        margin-top:6px;
    }
    .ui-preview.ui-channel-label {
        display:flex;
        align-items:center;
    }
    .ui-channel-label-text {
        overflow:hidden;
        text-overflow:ellipsis;
        white-space:nowrap;
    }
    .ui-count {
        background:var(--ui-accent);
        color:white;
        border-radius:20px;
        padding:2px 6px;
        font-size:11px;
    }
    .ui-empty {
        display:flex;
        flex-direction:column;
        align-items:center;
        justify-content:center;
        text-align:center;
        gap:16px;
        padding:40px;
        min-height:400px;
        background:radial-gradient(ellipse at center,#f3f3ff,transparent 65%);
    }
    .ui-empty .ui-avatar {
        width:76px;
        height:76px;
        font-size:30px;
    }
    .ui-pagination {
        padding:12px;
        overflow:auto;
    }
    .ui-chat-layout {
        display:grid;
        grid-template-columns:minmax(0,1fr) 320px;
        min-width:0;
    }
    .ui-chat {
        min-width:0;
        display:flex;
        flex-direction:column;
    }
    .ui-chat-header {
        padding:20px;
        border-bottom:1px solid var(--ui-line);
        display:flex;
        gap:12px;
        align-items:center;
        flex-wrap:wrap;
    }
    .ui-chat-header h1 {
        font-size:20px;
    }
    .ui-history {
        padding:20px;
        display:flex;
        flex-direction:column;
        gap:18px;
        max-height:570px;
        overflow:auto;
        background:linear-gradient(#fff,var(--ui-soft));
    }
    .ui-message {
        align-self:flex-start;
        max-width:88%;
        padding:13px 16px;
        border-radius:14px 14px 14px 3px;
        background:#eceef4;
        overflow-wrap:anywhere;
    }
    .ui-message-outgoing {
        align-self:flex-end;
        background:var(--ui-accent);
        color:white;
        border-radius:14px 14px 3px 14px;
    }
    .ui-message-meta {
        font-size:11px;
        opacity:.75;
        display:flex;
        flex-wrap:wrap;
        gap:8px;
        margin-bottom:7px;
    }
    .ui-message-outgoing a {
        color:white;
    }
    .ui-composer {
        padding:18px;
        border-top:1px solid var(--ui-line);
        margin-top:auto;
    }
    .ui-composer textarea {
        width:100%;
        min-height:85px;
    }
    .ui-profile {
        padding:16px;
        border-inline-start:1px solid var(--ui-line);
        min-width:0;
        display:flex;
        flex-direction:column;
        gap:14px;
        max-height:760px;
        background:linear-gradient(180deg,var(--ui-bg),var(--ui-soft));
    }
    .ui-profile-head {
        display:flex;
        gap:12px;
        align-items:center;
        padding-bottom:4px;
    }
    .ui-profile-head-copy {
        min-width:0;
        flex:1;
    }
    .ui-profile-head-copy h2 {
        font-size:16px;
        line-height:1.3;
        overflow:hidden;
        text-overflow:ellipsis;
        white-space:nowrap;
    }
    .ui-side-tabs-root {
        display:flex;
        flex-direction:column;
        gap:14px;
        min-height:0;
        flex:1;
    }
    .ui-side-radio {
        position:absolute;
        width:1px;
        height:1px;
        margin:-1px;
        padding:0;
        overflow:hidden;
        clip:rect(0,0,0,0);
        white-space:nowrap;
        border:0;
        opacity:0;
        pointer-events:none;
    }
    .ui-side-tablist {
        display:grid;
        grid-template-columns:repeat(4,minmax(0,1fr));
        gap:6px;
    }
    .ui-side-tab {
        appearance:none;
        display:flex;
        align-items:center;
        justify-content:center;
        border:1px solid var(--ui-line);
        background:var(--ui-bg);
        color:var(--ui-muted);
        border-radius:10px;
        padding:8px 6px;
        font-size:12px;
        font-weight:700;
        cursor:pointer;
        min-width:0;
        white-space:nowrap;
        overflow:hidden;
        text-overflow:ellipsis;
        text-align:center;
        line-height:1.2;
        user-select:none;
    }
    .ui-side-tabs-root:has(.ui-side-radio[data-side-tab=overview]:checked) .ui-side-tab[data-side-tab=overview],
    .ui-side-tabs-root:has(.ui-side-radio[data-side-tab=crm]:checked) .ui-side-tab[data-side-tab=crm],
    .ui-side-tabs-root:has(.ui-side-radio[data-side-tab=work]:checked) .ui-side-tab[data-side-tab=work],
    .ui-side-tabs-root:has(.ui-side-radio[data-side-tab=assist]:checked) .ui-side-tab[data-side-tab=assist] {
        color:var(--ui-accent);
        background:#eeefff;
        border-color:#bfc3ff;
        box-shadow:0 2px 8px #444cf41a;
    }
    .ui-side-panels {
        min-height:0;
        flex:1;
        overflow:auto;
        padding-right:2px;
    }
    .ui-side-panel {
        display:none;
    }
    .ui-side-tabs-root:has(.ui-side-radio[data-side-tab=overview]:checked) .ui-side-panel[data-side-panel=overview],
    .ui-side-tabs-root:has(.ui-side-radio[data-side-tab=crm]:checked) .ui-side-panel[data-side-panel=crm],
    .ui-side-tabs-root:has(.ui-side-radio[data-side-tab=work]:checked) .ui-side-panel[data-side-panel=work],
    .ui-side-tabs-root:has(.ui-side-radio[data-side-tab=assist]:checked) .ui-side-panel[data-side-panel=assist] {
        display:block;
    }
    .ui-side-panel section {
        padding:14px 0;
        border-bottom:1px solid var(--ui-line);
    }
    .ui-side-panel section:first-child {
        padding-top:2px;
    }
    .ui-side-panel section:last-child {
        border-bottom:0;
        padding-bottom:4px;
    }
    .ui-side-item {
        padding:10px 0;
        border-bottom:1px solid var(--ui-line);
    }
    .ui-side-item:last-child {
        border-bottom:0;
    }
    .ui-side-suggestion {
        margin-bottom:12px;
    }
    .ui-side-check {
        display:flex!important;
        align-items:center;
        gap:8px;
        flex:unset!important;
        width:100%;
        color:var(--ui-ink)!important;
        font-size:13px!important;
    }
    .ui-profile label:not(.ui-side-tab):not(.ui-side-check) {
        display:block;
        font-size:12px;
        color:var(--ui-muted);
        flex:1 1 140px;
        min-width:0;
    }
    .ui-profile label:has(textarea) {
        flex:1 1 100%;
        width:100%;
    }
    .ui-profile input:not([type=checkbox]):not([type=hidden]):not(.ui-side-radio),
    .ui-profile select,
    .ui-profile textarea {
        width:100%;
        margin-top:6px;
        display:block;
        box-sizing:border-box;
    }
    .ui-profile textarea {
        min-height:84px;
        resize:vertical;
        line-height:1.4;
    }
    .ui-profile form {
        display:flex;
        flex-wrap:wrap;
        gap:12px;
        align-items:end;
        margin-top:12px;
    }
    .ui-side-panel[data-side-panel=assist] form,
    .ui-profile form.ui-side-stack {
        flex-direction:column;
        flex-wrap:nowrap;
        align-items:stretch;
    }
    .ui-side-panel[data-side-panel=assist] form > label:not(.ui-side-check),
    .ui-profile form.ui-side-stack > label:not(.ui-side-check) {
        flex:none;
        width:100%;
    }
    .ui-side-panel[data-side-panel=assist] form > .primary-button,
    .ui-side-panel[data-side-panel=assist] form > .secondary-button,
    .ui-profile form.ui-side-stack > .primary-button,
    .ui-profile form.ui-side-stack > .secondary-button {
        align-self:flex-start;
        width:auto;
    }
    .dark .ui-side-tabs-root:has(.ui-side-radio[data-side-tab=overview]:checked) .ui-side-tab[data-side-tab=overview],
    .dark .ui-side-tabs-root:has(.ui-side-radio[data-side-tab=crm]:checked) .ui-side-tab[data-side-tab=crm],
    .dark .ui-side-tabs-root:has(.ui-side-radio[data-side-tab=work]:checked) .ui-side-tab[data-side-tab=work],
    .dark .ui-side-tabs-root:has(.ui-side-radio[data-side-tab=assist]:checked) .ui-side-tab[data-side-tab=assist] {
        background:#283254;
        color:#c7cbff;
        border-color:#3d4d74;
    }
    .ui-notice {
        padding:12px 18px;
        background:var(--ui-soft);
        font-size:13px;
        border-bottom:1px solid var(--ui-line);
    }
    .ui-channel-content {
        padding:0 24px 24px;
    }
    .ui-channel-cards {
        display:grid;
        grid-template-columns:repeat(3,minmax(0,1fr));
        gap:18px;
        margin:24px 0;
    }
    .ui-card {
        border:1px solid var(--ui-line);
        border-radius:14px;
        padding:22px;
        display:flex;
        flex-direction:column;
        gap:14px;
        background:var(--ui-bg);
    }
    .ui-card> .ui-channel {
        width:44px;
        height:44px;
        font-size:18px;
    }
    .ui-card> .ui-channel .ui-channel-logo {
        width:26px;
        height:26px;
    }
    .ui-card p {
        color:var(--ui-muted);
        line-height:1.7;
        font-size:13px;
    }
    .ui-card>form,.ui-card>button {
        margin-top:auto;
    }
    .ui-card button {
        justify-content:center;
    }
    .ui-website {
        background:var(--ui-soft);
        margin-bottom:28px;
    }
    .ui-website form {
        display:flex;
        gap:16px;
        flex-wrap:wrap;
        align-items:end;
    }
    .ui-website label {
        flex:1;
        min-width:180px;
        font-size:13px;
    }
    .ui-website input {
        display:block;
        width:100%;
        margin-top:7px;
    }
    .ui-connection {
        margin-top:14px;
    }
    .ui-connection textarea {
        font-family:monospace;
        font-size:12px;
        min-height:80px;
    }
    .ui-setup {
        margin-top:24px;
        padding:18px;
        border:1px solid var(--ui-line);
        border-radius:12px;
        overflow-wrap:anywhere;
    }
    .ui-setup summary {
        cursor:pointer;
        font-weight:600;
    }
    .ui-setup p {
        margin-top:12px;
        font-size:13px;
        color:var(--ui-muted);
    }
    .dark .ui-workspace {
        --ui-bg:#111b2e;
        --ui-soft:#18243a;
        --ui-line:#2c3951;
        --ui-ink:#edf1fa;
        --ui-muted:#a3afc6;
    }
    .dark .ui-history {
        background:var(--ui-bg);
    }
    .dark .ui-message:not(.ui-message-outgoing) {
        background:#25314a;
    }
    .dark .ui-conversation:hover,.dark .ui-conversation[aria-current=page],.dark .ui-tab[aria-current=page] {
        background:#283254;
        color:#c7cbff;
    }
    @media(min-width:1600px) {
        .ui-columns {
            grid-template-columns:330px minmax(0,1fr);
        }
        .ui-chat-layout {
            grid-template-columns:minmax(0,1fr) 340px;
        }
    }
    @media(max-width:1250px) {
        .ui-chat-layout {
            grid-template-columns:minmax(0,1fr);
        }
        .ui-profile {
            border-inline-start:0;
            border-top:1px solid var(--ui-line);
            max-height:none;
        }
        .ui-side-panel section {
            padding:12px 0;
        }
        .ui-channel-cards {
            gap:12px;
        }
        .ui-card {
            padding:18px;
        }
    }
    @media(max-width:767px) {
        .ui-header {
            padding:18px;
        }
        .ui-workspace h1 {
            font-size:24px;
        }
        .ui-filters {
            padding:0 18px 16px;
            gap:10px;
        }
        .ui-search {
            flex-basis:100%;
        }
        .ui-tabs {
            padding:0 18px 16px;
        }
        .ui-columns {
            grid-template-columns:minmax(0,1fr);
            min-height:0;
        }
        .ui-list {
            border-inline-end:0;
        }
        .ui-detail .ui-list {
            display:none;
        }
        .ui-index .ui-empty {
            display:none;
        }
        .ui-conversations {
            max-height:none;
        }
        .ui-channel-cards {
            grid-template-columns:1fr;
        }
        .ui-channel-content {
            padding:0 18px 18px;
        }
        .ui-chat-header {
            padding:16px;
        }
        .ui-history {
            padding:14px;
            max-height:60vh;
            min-height:260px;
        }
        .ui-message {
            max-width:94%;
        }
        .ui-composer {
            padding:14px;
        }
        .ui-website label {
            min-width:100%;
        }
        .ui-workspace {
            border-radius:12px;
        }
        .ui-profile {
            padding:16px;
        }
    }
    @media(prefers-reduced-motion:reduce) {
        .ui-workspace * {
            transition:none!important;
            scroll-behavior:auto!important;
        }
    }


    /* ===== Production polish (shell structure unchanged) ===== */
    .ui-workspace{--ui-accent:#0e90d9;--ui-accent-soft:#e7f4fc;--ui-accent-line:#b6dcf3;font-size:14px;line-height:1.5;box-shadow:0 1px 2px #1018280d}
    .dark .ui-workspace{--ui-accent-soft:#12324a;--ui-accent-line:#1f5378;box-shadow:none}
    .ui-workspace h1{font-size:22px;font-weight:600;letter-spacing:-.3px;line-height:1.25}
    .ui-workspace h2{font-size:15px;line-height:1.35}
    .ui-header{padding:20px 24px;border-bottom:1px solid var(--ui-line)}
    .ui-header .ui-muted{margin-top:2px}
    .ui-filters{padding:14px 24px}
    .ui-workspace .primary-button,.ui-workspace .secondary-button{display:inline-flex;align-items:center;justify-content:center;gap:6px;font-weight:600;font-size:13px;padding:8px 14px;border-radius:8px;line-height:1.2;cursor:pointer}
    .ui-workspace .primary-button:hover:not(:disabled){filter:brightness(.94);box-shadow:none}
    .ui-workspace .secondary-button:hover:not(:disabled){background:var(--ui-soft);border-color:#cfd5e1;box-shadow:none}
    .dark .ui-workspace .secondary-button:hover:not(:disabled){border-color:#3b4a63}
    .ui-workspace :focus-visible{outline:2px solid var(--ui-accent);outline-offset:2px}
    .ui-workspace input:not([type=checkbox]):not([type=hidden]),.ui-workspace select,.ui-workspace textarea{background:var(--ui-bg);border-radius:8px;padding:8px 12px;font-size:13px;transition:border-color .15s,box-shadow .15s}
    .ui-workspace input:focus,.ui-workspace select:focus,.ui-workspace textarea:focus{outline:0;border-color:var(--ui-accent);box-shadow:0 0 0 3px var(--ui-accent-soft)}
    .ui-workspace input[type=checkbox]{accent-color:var(--ui-accent)}
    /* Tabs */
    .ui-tabs{padding:12px 24px;gap:8px}
    .ui-tab{border-radius:999px;padding:6px 12px 6px 6px;background:var(--ui-bg);color:var(--ui-muted);font-weight:500;transition:background .15s,color .15s,border-color .15s}
    .ui-tab:hover{color:var(--ui-ink);border-color:#cfd5e1}
    .ui-tab .ui-channel{width:22px;height:22px;border-radius:50%}
    .ui-tab .ui-channel-logo{width:13px;height:13px}
    .ui-tab[aria-current=page]{color:var(--ui-accent);background:var(--ui-accent-soft);border-color:var(--ui-accent-line)}
    .dark .ui-tab[aria-current=page]{color:#7cc4f0;background:var(--ui-accent-soft)}
    /* Conversation list */
    .ui-list{background:var(--ui-bg)}
    .ui-list-heading{padding:14px 18px 10px}
    .ui-list-heading h2{font-size:12px;text-transform:uppercase;letter-spacing:.6px;color:var(--ui-muted)}
    .ui-conversations{padding:0 8px 8px}
    .ui-conversation{position:relative;padding:12px 10px;gap:12px;border-radius:8px;border-bottom:0;margin-bottom:2px;transition:background .12s}
    .ui-conversation:hover{background:var(--ui-soft)}
    .ui-conversation[aria-current=page]{background:var(--ui-accent-soft)}
    .ui-conversation[aria-current=page]::before{content:"";position:absolute;inset:10px auto 10px 0;width:3px;border-radius:3px;background:var(--ui-accent)}
    .dark .ui-conversation:hover{background:#18243a;color:inherit}
    .dark .ui-conversation[aria-current=page]{background:var(--ui-accent-soft);color:inherit}
    .ui-conversation-copy strong{font-size:14px;font-weight:500}
    .ui-conversation:has(.ui-count) .ui-conversation-copy strong{font-weight:700}
    .ui-conversation:has(.ui-count) .ui-preview:not(.ui-channel-label){color:var(--ui-ink)}
    .ui-preview{margin-top:2px}
    .ui-preview.ui-channel-label{font-size:11.5px;margin-top:4px;gap:5px}
    .ui-preview .ui-channel{width:16px;height:16px;border-radius:5px}
    .ui-preview .ui-channel-logo{width:10px;height:10px}
    .ui-avatar{width:40px;height:40px;font-size:15px;font-weight:600;background:#e2eaf3;color:#3b5068}
    .dark .ui-avatar{background:#25314a;color:#c3cde0}
    .ui-count{background:var(--ui-accent);min-width:20px;height:20px;display:inline-grid;place-items:center;padding:0 6px;font-weight:600}
    .ui-conversations>.p-6{padding:40px 16px}
    .ui-conversations>.p-6 .ui-muted::before{content:"";display:block;width:40px;height:40px;margin:0 auto 10px;opacity:.5;background:var(--ui-muted);-webkit-mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.6' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12Z'/%3E%3C/svg%3E") center/contain no-repeat;mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='1.6' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12Z'/%3E%3C/svg%3E") center/contain no-repeat}
    /* Thread chrome (neutral; channel themes below override colours) */
    .ui-chat-header{padding:14px 20px;gap:12px}
    .ui-chat-header h1{font-size:16px;font-weight:600}
    .ui-chat-header .ui-muted{font-size:12px;gap:6px;margin-top:2px}
    .ui-chat-header>.secondary-button{width:34px;height:34px;padding:0}
    .ui-chat-header select{border-radius:999px!important;font-size:12px;padding:5px 10px!important}
    .ui-history{gap:10px;padding:20px 24px}
    .ui-message{max-width:72%;padding:8px 12px;font-size:14px;line-height:1.45;box-shadow:0 1px 1px #1018280f}
    .ui-message-meta{order:2;margin:4px 0 0;justify-content:flex-end;font-size:11px;gap:6px}
    .ui-message{display:flex;flex-direction:column}
    .ui-message p{margin:0}
    .ui-message-meta strong{display:none}
    .ui-history>.p-8{margin:auto;padding:32px}
    .ui-composer{padding:12px 20px 16px;background:var(--ui-bg)}
    .ui-composer textarea{min-height:64px;resize:vertical;font-size:14px}
    .ui-composer .secondary-button{padding:4px 10px;font-size:12px;border-radius:999px;font-weight:500}
    #send-button{min-width:96px;position:relative}
    #send-button:disabled{opacity:.75;cursor:progress}
    #send-button:disabled span{visibility:hidden}
    #send-button:disabled::after{content:"";position:absolute;width:16px;height:16px;border:2px solid #ffffff66;border-top-color:#fff;border-radius:50%;animation:ui-spin .7s linear infinite}
    @keyframes ui-spin{to{transform:rotate(360deg)}}
    /* Side panel */
    .ui-profile{padding:18px;background:var(--ui-bg)}
    .ui-side-panel h2{font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:var(--ui-muted);margin-bottom:6px}
    .ui-side-tab{border-radius:8px;font-weight:600;transition:color .15s,background .15s}
    .ui-side-tab:hover{color:var(--ui-ink)}
    .ui-side-tabs-root:has(.ui-side-radio[data-side-tab=overview]:checked) .ui-side-tab[data-side-tab=overview],
    .ui-side-tabs-root:has(.ui-side-radio[data-side-tab=crm]:checked) .ui-side-tab[data-side-tab=crm],
    .ui-side-tabs-root:has(.ui-side-radio[data-side-tab=work]:checked) .ui-side-tab[data-side-tab=work]{color:var(--ui-accent);background:var(--ui-accent-soft);border-color:var(--ui-accent-line);box-shadow:none}
    .ui-profile .text-blue-600,.ui-profile .text-blue-500{color:var(--ui-accent)}
    /* Empty state */
    .ui-empty{background:var(--ui-soft);gap:12px}
    .ui-empty>.ui-avatar{width:64px;height:64px;font-size:26px;background:var(--ui-accent-soft);color:var(--ui-accent)}
    .ui-empty>h2{font-size:18px;font-weight:600}
    .ui-empty>p.ui-muted{max-width:420px;line-height:1.6}
    .ui-empty .ui-card{align-items:center;text-align:center;gap:4px}
    .ui-empty code{background:var(--ui-bg);border:1px solid var(--ui-line);padding:1px 6px;border-radius:4px}
    /* Channels page */
    .ui-card{border-radius:12px;transition:border-color .15s,box-shadow .15s}
    .ui-channel-cards .ui-card:hover{border-color:var(--ui-accent-line);box-shadow:0 4px 14px #1018280f}
    .ui-card>.ui-channel{width:40px;height:40px;border-radius:10px}
    .ui-connection>div{gap:12px;flex-wrap:wrap}
    .ui-connection h3{margin-bottom:2px}
    .ui-setup{background:var(--ui-soft);border-radius:12px}
    .ui-setup summary{font-size:14px}
    .ui-setup code{border:1px solid var(--ui-line)}
    @media(prefers-reduced-motion:reduce){#send-button:disabled::after{animation:none}}


    /* ===== Zaris Inbox layout (mock) ===== */
    .ui-workspace{display:flex;flex-direction:column}
    .ui-tabs{gap:4px;padding:8px 16px;border-bottom:1px solid var(--ui-line);background:var(--ui-bg)}
    .ui-tab{border:0;background:transparent;border-radius:8px;padding:7px 12px;gap:8px}
    .ui-tab:hover{background:var(--ui-soft)}
    .ui-tab[aria-current=page]{background:var(--ui-accent-soft);border:0}
    .ui-tab-count{min-width:22px;height:20px;padding:0 6px;border-radius:999px;display:inline-grid;place-items:center;font-size:11px;font-weight:600;background:var(--ui-soft);color:var(--ui-muted)}
    .ui-tab-count.is-unread{background:#ef4444;color:#fff}
    .ui-tab[aria-current=page] .ui-tab-count:not(.is-unread){background:var(--ui-accent);color:#fff}
    .ui-columns{grid-template-columns:320px minmax(0,1fr);height:calc(100vh - 230px);min-height:600px}
    .ui-list{display:flex;flex-direction:column;min-height:0}
    .ui-list-search{padding:12px 12px 8px}
    .ui-list-search .ui-search input{width:100%;padding-left:34px;background:var(--ui-bg) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' fill='none' stroke='%2374809a' stroke-width='2'%3E%3Ccircle cx='6' cy='6' r='4.5'/%3E%3Cpath d='m10 10 3 3'/%3E%3C/svg%3E") no-repeat 12px center}
    .ui-chips,.ui-quick{display:flex;flex-wrap:wrap;gap:6px;padding:0 12px 10px;border-bottom:1px solid var(--ui-line)}
    .ui-quick{padding:0 0 8px;border:0}
    .ui-chip{font-size:12px;padding:4px 10px;border:1px solid var(--ui-line);border-radius:999px;background:var(--ui-bg);color:var(--ui-muted);cursor:pointer;line-height:1.3}
    .ui-chip:hover{color:var(--ui-ink);border-color:#cfd5e1}
    .ui-chip[aria-pressed=true]{background:var(--ui-accent-soft);border-color:var(--ui-accent-line);color:var(--ui-accent);font-weight:600}
    .ui-chip-clear{border-style:dashed}
    .ui-conversations{flex:1;max-height:none;overflow:auto;padding:6px}
    .ui-conversation{align-items:flex-start;padding:10px}
    .ui-avatar-wrap{position:relative;flex-shrink:0}
    .ui-avatar-badge{position:absolute;right:-3px;bottom:-3px;width:18px!important;height:18px!important;border-radius:50%!important;border:2px solid var(--ui-bg)}
    .ui-avatar-badge .ui-channel-logo{width:10px;height:10px}
    .ui-row-top,.ui-row-bottom{display:flex;align-items:center;justify-content:space-between;gap:8px;min-width:0}
    .ui-row-top strong{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .ui-row-mid{display:flex;gap:4px;margin:3px 0}
    .ui-row-bottom .ui-preview{margin:0;flex:1;min-width:0}
    .ui-time{font-size:11px;color:var(--ui-muted);flex-shrink:0}
    .ui-conversation:has(.ui-count) .ui-time{color:var(--ui-accent);font-weight:600}
    .ui-count{background:#ef4444;flex-shrink:0}
    .ui-pill{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:500;padding:1px 7px;border-radius:5px;background:var(--ui-soft);color:var(--ui-muted);white-space:nowrap}
    .ui-pill .ui-channel{width:14px;height:14px;background:transparent}
    .ui-pill .ui-channel-logo{width:12px;height:12px}
    .ui-pill-whatsapp{background:#e7f8ee;color:#128c4b}
    .ui-pill-messenger{background:#e6f0ff;color:#0866ff}
    .ui-pill-instagram{background:#fde8f3;color:#c1307a}
    .ui-pill-status-open{background:#e7f8ee;color:#128c4b}
    .ui-pill-status-pending{background:#fff4dc;color:#a76a00}
    .ui-pill-status-closed{background:var(--ui-soft);color:var(--ui-muted)}
    .dark .ui-pill-whatsapp,.dark .ui-pill-status-open{background:#123524;color:#5ee09a}
    .dark .ui-pill-messenger{background:#132a4d;color:#7fb3ff}
    .dark .ui-pill-instagram{background:#3d1630;color:#f08cc4}
    .dark .ui-pill-status-pending{background:#3a2c0c;color:#ffcc66}
    .ui-list-empty{padding:40px 16px;text-align:center;display:flex;flex-direction:column;gap:10px;align-items:center}
    .ui-list-empty-title{font-weight:600;font-size:14px}
    .ui-list-footer{display:flex;justify-content:space-between;align-items:center;padding:8px 12px;border-top:1px solid var(--ui-line);font-size:12px}
    .ui-pager{display:flex;gap:4px}
    .ui-pager .secondary-button{width:28px;height:28px;padding:0}
    .ui-pager [aria-disabled=true]{opacity:.4;pointer-events:none}
    /* Chat */
    .ui-chat-layout{grid-template-columns:minmax(0,1fr) 330px;min-height:0;height:100%}
    .ui-chat{min-height:0}
    .ui-chat-header{flex-wrap:nowrap}
    .ui-back{display:none!important}
    .ui-chat-title{flex:1;min-width:0}
    .ui-chat-sub{display:flex;align-items:center;gap:8px;flex-wrap:wrap;font-size:12px;color:var(--ui-muted);margin-top:3px}
    .ui-chat-actions{display:flex;align-items:center;gap:8px;flex-shrink:0}
    .ui-assignee{display:flex;flex-direction:column;font-size:12px;line-height:1.25;text-align:right;padding-right:6px;border-right:1px solid var(--ui-line);margin-right:2px}
    .ui-assignee .ui-muted{font-size:11px}
    .ui-icon-btn{width:34px;height:34px;padding:0!important}
    .ui-history{flex:1;max-height:none;min-height:0}
    .ui-day{align-self:center;margin:8px 0}
    .ui-day span{font-size:11px;font-weight:500;color:var(--ui-muted);background:var(--ui-soft);border-radius:6px;padding:3px 10px;box-shadow:0 1px 1px #1018280d}
    .ui-msg-status{letter-spacing:-2px}
    .ui-msg-status-read{color:#53bdeb}
    .ui-msg-status-failed{color:#ef4444;font-weight:700}
    .ui-history-empty{margin:auto;text-align:center;padding:24px}
    .ui-history-empty-title{font-weight:600}
    .ui-composer{border:1px solid var(--ui-line);border-radius:10px;margin:0 16px 16px;padding:0 12px 12px}
    .ui-composer-tabs{display:flex;gap:16px;border-bottom:1px solid var(--ui-line);margin:0 -12px 10px;padding:0 12px}
    .ui-composer-tab{font-size:13px;font-weight:500;padding:9px 0;color:var(--ui-muted);border-bottom:2px solid transparent}
    .ui-composer-tab[aria-selected=true]{color:var(--ui-accent);border-color:var(--ui-accent)}
    .ui-composer-tab[aria-disabled=true]{opacity:.6;cursor:not-allowed}
    .ui-composer-tab small{font-size:10px;background:var(--ui-soft);border-radius:4px;padding:0 4px;margin-left:2px}
    .ui-composer textarea{width:100%;min-height:56px;border:0!important;background:transparent!important;padding:4px 0!important;box-shadow:none!important;resize:none}
    .ui-composer-bar{display:flex;justify-content:space-between;align-items:center;margin-top:6px;font-size:12px}
    /* Right panel */
    .ui-profile{padding:0;gap:0;overflow:auto;max-height:none}
    .ui-side-tablist{display:flex;gap:0;border-bottom:1px solid var(--ui-line);padding:0 12px;position:sticky;top:0;background:var(--ui-bg);z-index:1}
    .ui-side-tab{border:0!important;border-radius:0!important;background:transparent!important;box-shadow:none!important;padding:12px 10px;border-bottom:2px solid transparent!important;flex:none}
    .ui-side-tabs-root:has(.ui-side-radio[data-side-tab=overview]:checked) .ui-side-tab[data-side-tab=overview],
    .ui-side-tabs-root:has(.ui-side-radio[data-side-tab=crm]:checked) .ui-side-tab[data-side-tab=crm],
    .ui-side-tabs-root:has(.ui-side-radio[data-side-tab=work]:checked) .ui-side-tab[data-side-tab=work]{border-bottom-color:var(--ui-accent)!important;color:var(--ui-accent)}
    .ui-side-tabs-root{gap:0}
    .ui-side-panel section{padding:14px 16px}
    .ui-profile-head{gap:12px;align-items:center}
    .ui-avatar-lg{width:56px;height:56px;font-size:22px}
    .ui-profile-head-copy h2{font-size:16px!important;text-transform:none!important;letter-spacing:0!important;color:var(--ui-ink)!important;margin-bottom:4px}
    .ui-dl{display:grid;grid-template-columns:96px minmax(0,1fr);gap:8px 10px;font-size:13px;margin:0}
    .ui-dl dt{color:var(--ui-muted)}
    .ui-dl dd{margin:0;overflow-wrap:anywhere}
    .ui-quick-actions{display:grid;grid-template-columns:1fr 1fr;gap:6px}
    .ui-quick-actions .secondary-button{font-size:12px;padding:7px 8px;font-weight:500}
    .ui-field{display:block;font-size:12px;color:var(--ui-muted);margin-top:8px}
    .ui-field select{display:block;width:100%;margin-top:4px}
    .ui-record{display:grid;grid-template-columns:72px minmax(0,1fr) auto;align-items:center;gap:8px;padding:8px 0;border-bottom:1px solid var(--ui-line);font-size:13px}
    .ui-record:last-child{border:0}
    .ui-record-label{color:var(--ui-muted)}
    .ui-record-value{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--ui-accent)}
    .ui-record .secondary-button{padding:4px 10px;font-size:12px}
    @media(max-width:1250px){.ui-columns,.ui-chat-layout{height:auto}.ui-history{min-height:420px}}
    @media(max-width:767px){.ui-back{display:inline-flex!important}.ui-assignee{display:none}.ui-columns{grid-template-columns:1fr}}


    .ui-connect-gate{display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;gap:10px;min-height:460px;padding:48px 24px;background:var(--ui-soft)}
    .ui-connect-gate>.ui-channel{width:56px;height:56px;border-radius:14px}
    .ui-connect-gate>.ui-channel .ui-channel-logo{width:30px;height:30px}
    .ui-connect-gate h2{font-size:18px;font-weight:600}
    .ui-connect-gate p{max-width:380px}
    .ui-tab-off{font-size:10.5px;font-weight:600;padding:1px 7px;border-radius:999px;border:1px dashed var(--ui-line);color:var(--ui-muted)}

    /* Outbound bubble fix: stored direction is 'outbound', CSS only matched 'outgoing' */
    .ui-message-outbound{align-self:flex-end;background:var(--ui-accent);color:#fff;border-radius:14px 14px 3px 14px}
    .ui-message-outbound a{color:#fff}

    /* Per-channel thread themes: scoped to the open conversation pane only */
    .ui-thread-whatsapp .ui-chat-header{background:#f0f2f5}
    .ui-thread-whatsapp .ui-history{background:#efeae2;gap:6px}
    .ui-thread-whatsapp .ui-message{background:#fff;color:#111b21;border-radius:0 8px 8px 8px;box-shadow:0 1px .5px #0b141a21;padding:6px 9px 8px}
    .ui-thread-whatsapp .ui-message-outbound,.ui-thread-whatsapp .ui-message-outgoing{background:#d9fdd3;color:#111b21;border-radius:8px 0 8px 8px}
    .ui-thread-whatsapp .ui-message-meta{color:#667781;opacity:1}
    .ui-thread-whatsapp .ui-composer{background:#f0f2f5}
    .ui-thread-whatsapp .ui-composer textarea{background:#fff;border:0;border-radius:8px}
    .ui-thread-whatsapp #send-button{background:#00a884;border-color:#00a884}

    .ui-thread-messenger .ui-history{background:#fff;gap:4px}
    .ui-thread-messenger .ui-message{background:#f0f0f0;color:#050505;border-radius:18px;padding:8px 12px}
    .ui-thread-messenger .ui-message-outbound,.ui-thread-messenger .ui-message-outgoing{background:linear-gradient(135deg,#0084ff,#a033ff);color:#fff;border-radius:18px}
    .ui-thread-messenger .ui-message-meta{opacity:.65}
    .ui-thread-messenger .ui-composer textarea{border-radius:20px;background:#f0f2f5;border:0}
    .ui-thread-messenger #send-button{background:#0084ff;border-color:#0084ff;border-radius:20px}

    .ui-thread-instagram .ui-history{background:#fff;gap:4px}
    .ui-thread-instagram .ui-message{background:#efefef;color:#000;border-radius:22px;padding:9px 14px}
    .ui-thread-instagram .ui-message-outbound,.ui-thread-instagram .ui-message-outgoing{background:linear-gradient(135deg,#7638fa,#d300c5 60%,#ff0069);color:#fff;border-radius:22px}
    .ui-thread-instagram .ui-message-meta{opacity:.65}
    .ui-thread-instagram .ui-composer textarea{border-radius:22px;background:#fff;border:1px solid #dbdbdb}
    .ui-thread-instagram #send-button{background:#0095f6;border-color:#0095f6;border-radius:20px}

    .dark .ui-thread-whatsapp .ui-chat-header,.dark .ui-thread-whatsapp .ui-composer{background:#202c33}
    .dark .ui-thread-whatsapp .ui-history{background:#0b141a}
    .dark .ui-thread-whatsapp .ui-message:not(.ui-message-outbound):not(.ui-message-outgoing){background:#202c33;color:#e9edef}
    .dark .ui-thread-whatsapp .ui-message-outbound,.dark .ui-thread-whatsapp .ui-message-outgoing{background:#005c4b;color:#e9edef}
    .dark .ui-thread-whatsapp .ui-message-meta{color:#ffffff99}
    .dark .ui-thread-whatsapp .ui-composer textarea{background:#2a3942;color:#e9edef}
    .dark .ui-thread-messenger .ui-history,.dark .ui-thread-instagram .ui-history{background:#000}
    .dark .ui-thread-messenger .ui-message:not(.ui-message-outbound):not(.ui-message-outgoing){background:#303030;color:#e4e6eb}
    .dark .ui-thread-instagram .ui-message:not(.ui-message-outbound):not(.ui-message-outgoing){background:#262626;color:#f5f5f5}
    .dark .ui-thread-messenger .ui-composer textarea,.dark .ui-thread-instagram .ui-composer textarea{background:#242526;border-color:#363636;color:#e4e6eb}
    /* Header assignee + status segmented control */
    .ui-chat-actions{display:flex;align-items:center;gap:10px;flex-shrink:0}
    .ui-assignee,.ui-assign-card{display:flex;align-items:center;gap:8px;text-align:left;border:0;margin:0;padding:0 10px 0 0}
    .ui-assignee{border-right:1px solid var(--ui-line)}
    .ui-assignee-avatar{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:999px;background:#e6f4fb;color:#0e90d9;font-size:12px;font-weight:600;flex-shrink:0}
    .is-empty .ui-assignee-avatar{background:#f3f4f6;color:#9ca3af;border:1px dashed #d1d5db}
    .ui-assignee-copy{display:flex;flex-direction:column;min-width:0;line-height:1.2}
    .ui-assignee-label{font-size:11px;color:var(--ui-muted)}
    .ui-assignee-name{font-size:13px;font-weight:600;color:var(--ui-ink);max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .is-empty .ui-assignee-name{color:var(--ui-muted);font-weight:500}
    .ui-status-seg{display:inline-flex;padding:2px;border:1px solid var(--ui-line);border-radius:8px;background:#f9fafb}
    .ui-status-opt{display:inline-flex;align-items:center;gap:6px;height:28px;padding:0 10px;border:0;border-radius:6px;background:transparent;font-size:12px;font-weight:500;color:#6b7280;cursor:pointer;white-space:nowrap;transition:background .15s,color .15s}
    .ui-status-opt:hover{color:#111827;background:#fff}
    .ui-status-opt.is-active{background:#fff;color:#111827;box-shadow:0 1px 2px rgba(0,0,0,.08)}
    .ui-status-dot{width:7px;height:7px;border-radius:999px;background:currentColor;opacity:.9}
    .ui-status-opt-open .ui-status-dot{color:#16a34a;background:#16a34a}
    .ui-status-opt-pending .ui-status-dot{background:#f59e0b}
    .ui-status-opt-closed .ui-status-dot{background:#9ca3af}
    .ui-status-opt:focus-visible,.ui-unassign-btn:focus-visible{outline:2px solid #0e90d9;outline-offset:1px}
    .dark .ui-status-seg{background:#111827;border-color:#374151}
    .dark .ui-status-opt.is-active,.dark .ui-status-opt:hover{background:#1f2937;color:#f9fafb}
    .dark .ui-assignee-avatar{background:rgba(14,144,217,.15)}
    @media (max-width:1100px){.ui-assignee-copy{display:none}.ui-status-opt{padding:0 8px}}

    /* Right sidebar polish */
    .ui-profile{font-size:13px}
    .ui-side-panel section{padding:16px;border-bottom:1px solid var(--ui-line)}
    .ui-side-panel h2{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;color:var(--ui-muted);margin:0 0 10px}
    .ui-profile-head{display:flex;align-items:center;gap:12px}
    .ui-profile-head h2{font-size:15px;font-weight:600;text-transform:none;letter-spacing:0;color:var(--ui-ink);margin:0 0 4px}
    .ui-dl{display:grid;grid-template-columns:96px 1fr;row-gap:8px;column-gap:12px;margin:0}
    .ui-dl dt{font-size:12px;color:var(--ui-muted)}
    .ui-dl dd{margin:0;font-size:13px;color:var(--ui-ink);overflow-wrap:anywhere}
    .ui-quick-actions{display:grid;grid-template-columns:1fr 1fr;gap:8px}
    .ui-quick-actions>*{justify-content:center;font-size:12px}
    .ui-assign-card{padding:10px;border:1px solid var(--ui-line);border-radius:8px;margin-bottom:10px}
    .ui-assign-card .ui-assignee-copy{flex:1}
    .ui-unassign-btn{border:0;background:transparent;color:#dc2626;font-size:12px;font-weight:500;cursor:pointer;padding:4px 6px;border-radius:6px}
    .ui-unassign-btn:hover{background:#fef2f2}
    .ui-record{display:grid;grid-template-columns:64px 1fr auto;align-items:center;gap:8px;padding:10px 0;border-bottom:1px solid var(--ui-line)}
    .ui-record:last-child{border-bottom:0}
    .ui-record-label{font-size:12px;color:var(--ui-muted)}
    .ui-record-value{font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .ui-record .secondary-button{padding:4px 10px;font-size:12px}
    .ui-side-empty{display:flex;flex-direction:column;align-items:center;text-align:center;gap:10px;padding:16px 8px;color:var(--ui-muted)}
    .ui-side-empty p{font-size:12px;max-width:220px;margin:0}
    /* Manage Channels */
    .ui-channels{padding-top:24px}
    .ui-channels-head{margin-bottom:16px}
    .ui-channels-head h2{font-size:15px;font-weight:600;margin:0}
    .ui-channels-head p{font-size:13px;color:var(--ui-muted);margin:4px 0 0}
    .ui-channels-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;align-items:stretch}
    .ui-channels-card{display:flex;flex-direction:column;min-height:228px;background:var(--ui-bg);border:1px solid var(--ui-line);border-radius:12px;overflow:hidden;transition:box-shadow .15s,border-color .15s}
    .ui-channels-card:hover{border-color:#d3d9e6;box-shadow:0 4px 16px rgba(16,24,40,.06)}
    .ui-channels-card.is-connected{border-top:3px solid var(--ui-accent)}
    .ui-channels-body{flex:1;display:flex;flex-direction:column;padding:20px 20px 16px;min-width:0}
    .ui-channels-top{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px}
    .ui-workspace .ui-channels-icon{width:40px;height:40px;border-radius:10px}
    .ui-channels-icon .ui-channel-logo{width:22px;height:22px}
    .ui-workspace .ui-channels-title{font-size:15px;font-weight:600;line-height:1.3;margin:0;color:var(--ui-ink)}
    .ui-workspace .ui-channels-account{font-size:12px;color:var(--ui-accent);font-weight:500;margin:3px 0 0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .ui-workspace .ui-channels-desc{font-size:13px;line-height:1.55;color:var(--ui-muted);margin:8px 0 0;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden}
    .ui-channels-badge{display:inline-flex;align-items:center;gap:6px;font-size:11px;font-weight:600;line-height:1;padding:5px 9px;border-radius:999px;white-space:nowrap;background:var(--ui-soft);color:var(--ui-muted);border:1px solid var(--ui-line)}
    .ui-channels-badge i{width:6px;height:6px;border-radius:50%;background:#a0a9bb}
    .ui-channels-badge.is-on{background:var(--ui-accent-soft);color:var(--ui-accent);border-color:var(--ui-accent-line)}
    .ui-channels-badge.is-on i{background:var(--ui-accent)}
    .ui-channels-foot{padding:14px 20px;border-top:1px solid var(--ui-line);background:var(--ui-soft)}
    .ui-channels-foot form{margin:0}
    .ui-workspace .ui-channels-btn{display:inline-flex;align-items:center;justify-content:center;width:100%;height:36px;padding:0 14px;font-size:13px;font-weight:600;border-radius:8px;cursor:pointer;transition:background .15s,border-color .15s,color .15s}
    .ui-workspace .ui-channels-connect{background:var(--ui-accent);border:1px solid var(--ui-accent);color:#fff}
    .ui-workspace .ui-channels-connect:hover:not(:disabled){background:#0b7cbc;border-color:#0b7cbc}
    .ui-workspace .ui-channels-disconnect{background:var(--ui-bg);border:1px solid var(--ui-line);color:#b42318}
    .ui-workspace .ui-channels-disconnect:hover:not(:disabled){background:#fef3f2;border-color:#fecdca}
    .dark .ui-channels-disconnect{color:#f97066}
    .dark .ui-workspace .ui-channels-disconnect:hover:not(:disabled){background:#3a1a1a;border-color:#7a2e2e}
    @media (max-width:1024px){.ui-channels-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media (max-width:640px){.ui-channels-grid{grid-template-columns:1fr}.ui-channels-card{min-height:0}}

    /* ---- Fixed-viewport inbox workspace (scoped to .ui-inbox; Manage Channels unaffected) ---- */
    @media(min-width:768px){
        .ui-workspace.ui-inbox{display:flex;flex-direction:column;height:calc(100vh - var(--ui-chrome));height:calc(100dvh - var(--ui-chrome));min-height:520px;overflow:hidden}
        .ui-inbox > .ui-header,.ui-inbox > .ui-tabs,.ui-inbox > .ui-filters,.ui-inbox > header,.ui-inbox > nav{flex:0 0 auto}
        .ui-inbox > .ui-connect-gate{flex:1 1 auto;min-height:0;overflow:auto}
        .ui-inbox .ui-columns{flex:1 1 auto;min-height:0;height:auto;overflow:hidden}
        .ui-inbox .ui-list{display:flex;flex-direction:column;min-height:0;overflow:hidden}
        .ui-inbox .ui-list > *{flex:0 0 auto}
        .ui-inbox .ui-list > .ui-conversations{flex:1 1 auto;min-height:0;max-height:none;overflow-y:auto;overscroll-behavior:contain}
        .ui-inbox .ui-chat-layout{min-height:0;height:100%;overflow:hidden}
        .ui-inbox .ui-chat{min-height:0;height:100%;overflow:hidden}
        .ui-inbox .ui-chat-header{flex:0 0 auto}
        .ui-inbox .ui-history{flex:1 1 auto;min-height:0;max-height:none;overflow-y:auto;overscroll-behavior:contain}
        .ui-inbox .ui-composer{flex:0 0 auto;margin-top:0}
        .ui-inbox .ui-profile{min-height:0;height:100%;max-height:none;overflow-y:auto;overscroll-behavior:contain}
        .ui-inbox .ui-side-tabs-root{min-height:0}
        .ui-inbox .ui-empty{min-height:0;height:100%;overflow-y:auto}
    }
    @media(min-width:768px) and (max-width:1250px){
        .ui-inbox .ui-chat-layout{overflow-y:auto}
        .ui-inbox .ui-chat{height:100%;min-height:100%}
        .ui-inbox .ui-history{min-height:0}
        .ui-inbox .ui-profile{height:auto;overflow:visible}
    }
    /* Inbox page only: lock document scroll so only inner panels scroll */
    @media(min-width:768px){
        html:has(.ui-inbox),body:has(.ui-inbox){height:100%;overflow:hidden}
        .ui-workspace.ui-inbox{box-sizing:border-box;height:calc(100vh - var(--ui-chrome,90px));height:calc(100dvh - var(--ui-chrome,90px));min-height:0}
        .ui-inbox > *{min-height:0}
        .ui-inbox .ui-columns{display:grid;grid-template-rows:minmax(0,1fr)}
        .ui-inbox .ui-chat-layout{grid-template-rows:minmax(0,1fr)}
        .ui-inbox .ui-chat{display:flex;flex-direction:column}
    }
    .ui-inbox .ui-record{grid-template-columns:84px minmax(0,1fr) auto}
    .ui-inbox .ui-record-link{text-decoration:none}
    .ui-inbox .ui-record-link:hover{text-decoration:underline}
    .ui-inbox .ui-record-actions{display:flex;gap:8px;white-space:nowrap}
    .ui-inbox .ui-link-btn{display:inline-flex;align-items:center;gap:4px;height:26px;padding:0 10px;border:1px solid var(--ui-line);border-radius:6px;background:var(--ui-bg);font-size:12px;font-weight:600;line-height:1;color:var(--ui-ink);cursor:pointer;white-space:nowrap;transition:background .15s,border-color .15s,color .15s,box-shadow .15s}
    .ui-inbox .ui-link-btn span{font-size:11px;opacity:.85}
    .ui-inbox .ui-link-btn:hover{border-color:var(--ui-accent-line);color:var(--ui-accent);background:var(--ui-accent-soft);text-decoration:none}
    .ui-inbox .ui-link-btn:focus-visible{outline:none;box-shadow:0 0 0 2px var(--ui-accent-line)}
    .ui-inbox .ui-link-btn:active{transform:translateY(1px)}
    .ui-inbox .ui-link-btn:disabled{opacity:.5;cursor:not-allowed}
    .ui-inbox .ui-link-btn.ui-link-primary{color:var(--ui-accent);border-color:var(--ui-accent-line);background:var(--ui-accent-soft)}
    .ui-inbox .ui-link-btn.ui-link-primary:hover{background:var(--ui-accent);color:#fff;border-color:var(--ui-accent)}
    .ui-inbox .ui-link-btn.ui-link-danger{color:#dc2626}
    .ui-inbox .ui-link-btn.ui-link-danger:hover{background:#fef2f2;border-color:#fca5a5;color:#b91c1c}
    .ui-inbox .ui-record-actions,.ui-inbox .ui-link-btn+.ui-link-btn{margin-left:6px}
    .ui-inbox .ui-link-danger{color:#dc2626}
    .ui-inbox .ui-crm-search{grid-column:1/-1;margin-top:4px}
    .ui-inbox .ui-crm-search input{width:100%;border:1px solid var(--ui-line);border-radius:6px;padding:6px 8px;font-size:13px;background:transparent}
    .ui-inbox .ui-crm-results{list-style:none;margin:4px 0 0;padding:0;max-height:200px;overflow-y:auto;border:1px solid var(--ui-line);border-radius:6px}
    .ui-inbox .ui-crm-results li{border-bottom:1px solid var(--ui-line);padding:0}
    .ui-inbox .ui-crm-results li:last-child{border:0}
    .ui-inbox .ui-crm-results li.ui-muted{padding:8px;font-size:12px}
    .ui-inbox .ui-crm-results button{display:flex;flex-direction:column;width:100%;text-align:left;padding:7px 8px;background:none;border:0;cursor:pointer;font-size:13px}
    .ui-inbox .ui-crm-results button:hover{background:var(--ui-soft)}
    .ui-inbox .ui-crm-results small{color:var(--ui-muted);font-size:11px}
    .ui-inbox .ui-related-head{margin:10px 0 2px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;color:var(--ui-muted)}
    .ui-inbox .ui-related-row{display:flex;align-items:center;gap:8px;padding:5px 0;font-size:13px;min-width:0}
    .ui-inbox .ui-related-row a{color:var(--ui-accent);flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .ui-inbox .ui-timeline{list-style:none;margin:0;padding:0 0 0 12px;border-left:2px solid var(--ui-line)}
    .ui-inbox .ui-tl-item{position:relative;padding:0 0 14px 10px;font-size:13px}
    .ui-inbox .ui-tl-item::before{content:"";position:absolute;left:-18px;top:4px;width:10px;height:10px;border-radius:50%;background:var(--ui-accent);border:2px solid var(--ui-bg,#fff)}
    .ui-inbox .ui-tl-note::before{background:#d97706}
    .ui-inbox .ui-tl-top{display:flex;align-items:center;gap:6px;font-size:12px}
    .ui-inbox .ui-tl-top time{margin-left:auto}
    .ui-inbox .ui-tl-type{font-weight:600}
    .ui-inbox .ui-tl-badge{font-size:10px;padding:0 5px;border-radius:4px;background:#fef3c7;color:#92400e}
    .ui-inbox .ui-tl-title{display:block;margin-top:2px;color:var(--ui-accent)}
    .ui-inbox .ui-tl-comment{margin:3px 0 0;white-space:pre-wrap;word-break:break-word}
    .ui-inbox .ui-tl-meta{margin:2px 0 0;font-size:11px}
    .ui-inbox button.ui-composer-tab{background:none;border-left:0;border-right:0;border-top:0;cursor:pointer}
    .ui-inbox .ui-composer.is-note{background:#fffbeb}
    .ui-inbox .ui-composer.is-note [data-composer-mode=note]{color:#b45309;border-color:#b45309}
    .ui-inbox .ui-composer.is-note textarea{background:#fffbeb}

    /* Notes tab */
    .ui-inbox .ui-side-tabs-root:has(.ui-side-radio[data-side-tab=notes]:checked) .ui-side-panel[data-side-panel=notes]{display:block}
    .ui-inbox .ui-side-tabs-root:has(.ui-side-radio[data-side-tab=notes]:checked) .ui-side-tab[data-side-tab=notes]{border-bottom-color:var(--ui-accent)!important;color:var(--ui-accent)}
    .ui-inbox .ui-tab-count{display:inline-block;min-width:16px;margin-left:4px;padding:0 5px;border-radius:999px;background:#fef3c7;color:#b45309;font-size:10px;line-height:16px;text-align:center}
    .ui-inbox .ui-note-form{border:1px solid #fcd34d;background:#fffbeb;border-radius:8px;padding:8px;margin-bottom:12px}
    .ui-inbox .ui-note-form textarea{width:100%;resize:vertical;min-height:64px;border:0;background:#fffbeb;font-size:13px;outline:none;color:inherit}
    .ui-inbox .ui-note-form-foot{display:flex;align-items:center;justify-content:space-between;gap:8px;font-size:11px}
    .ui-inbox .ui-btn-note{height:28px;padding:0 12px;border-radius:6px;border:0;background:#d97706;color:#fff;font-size:12px;font-weight:600;cursor:pointer}
    .ui-inbox .ui-btn-note:hover{background:#b45309}
    .ui-inbox .ui-btn-note:disabled{opacity:.6;cursor:wait}
    .ui-inbox .ui-notes-list{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:8px}
    .ui-inbox .ui-note-item{border-left:3px solid #f59e0b;background:#fffbeb;border-radius:6px;padding:8px 10px}
    .ui-inbox .ui-note-body{white-space:pre-wrap;word-break:break-word;font-size:13px;margin:0 0 4px}
    .dark .ui-inbox .ui-note-form,.dark .ui-inbox .ui-note-form textarea,.dark .ui-inbox .ui-note-item{background:rgba(245,158,11,.08)}
    /* Notes tab — layout fix (overrides earlier generic rules) */
    .ui-inbox .ui-side-tablist{overflow-x:auto;scrollbar-width:none}
    .ui-inbox .ui-side-tablist::-webkit-scrollbar{display:none}
    .ui-inbox .ui-side-tab{padding:12px 8px;flex:none;overflow:visible}
    .ui-inbox .ui-side-tab .ui-tab-count{display:inline-block;height:16px;min-width:16px;margin-left:4px;padding:0 5px;line-height:16px;background:#fef3c7;color:#b45309}
    .ui-inbox .ui-note-form textarea{display:block;margin:0 0 6px;padding:4px 2px!important;min-height:64px;border:0!important;box-shadow:none!important;background:transparent!important;border-radius:0}
    .ui-inbox .ui-notes-list{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:10px}
    .ui-inbox .ui-notes-list > li{margin:0}
    .ui-inbox .ui-note-item{display:block;border:1px solid #fde68a;border-left:3px solid #f59e0b;background:#fffbeb;border-radius:8px;padding:10px 12px;min-width:0}
    .ui-inbox .ui-note-head{display:flex;align-items:center;gap:8px;min-width:0;margin-bottom:6px}
    .ui-inbox .ui-note-avatar{flex:none;width:22px;height:22px;border-radius:50%;display:inline-grid;place-items:center;background:#f59e0b;color:#fff;font-size:11px;font-weight:700}
    .ui-inbox .ui-note-author{font-size:12px;font-weight:600;color:var(--ui-ink);min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .ui-inbox .ui-note-time{margin-left:auto;flex:none;font-size:11px;color:var(--ui-muted);white-space:nowrap}
    .ui-inbox .ui-note-body{white-space:pre-wrap;overflow-wrap:anywhere;font-size:13px;line-height:1.5;color:var(--ui-ink);margin:0}
    .ui-inbox .ui-notes-empty{padding:16px 8px;text-align:center;font-size:12px;color:var(--ui-muted);border:1px dashed var(--ui-line);border-radius:8px}
    .dark .ui-inbox .ui-note-item{border-color:rgba(245,158,11,.3)}
    /* Connected-to bar (chat header) + record type badges */
    .ui-inbox .ui-connbar{flex:0 0 auto;display:flex;align-items:center;flex-wrap:wrap;gap:6px;padding:6px 20px;border-bottom:1px solid var(--ui-line);background:var(--ui-bg);font-size:12px;min-width:0}
    .ui-inbox .ui-connbar-label{color:var(--ui-muted);font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:.04em;margin-right:2px}
    .ui-inbox .ui-conn-chip{display:inline-flex;align-items:center;gap:6px;max-width:240px;height:24px;padding:0 8px 0 3px;border:1px solid var(--ui-line);border-radius:999px;background:var(--ui-bg);color:var(--ui-ink);text-decoration:none;transition:border-color .15s,background .15s}
    .ui-inbox .ui-conn-chip:hover{border-color:var(--ui-accent-line);background:var(--ui-accent-soft)}
    .ui-inbox .ui-conn-name{min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:500}
    .ui-inbox .ui-conn-type{display:inline-block;padding:1px 7px;border-radius:999px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.03em;line-height:16px;background:var(--ui-soft);color:var(--ui-muted)}
    .ui-inbox .ui-conn-type.ui-conn-lead,.ui-inbox .ui-conn-lead .ui-conn-type{background:#dbeafe;color:#1d4ed8}
    .ui-inbox .ui-conn-type.ui-conn-person,.ui-inbox .ui-conn-person .ui-conn-type{background:#dcfce7;color:#15803d}
    .ui-inbox .ui-conn-type.ui-conn-organization,.ui-inbox .ui-conn-organization .ui-conn-type{background:#ede9fe;color:#6d28d9}
    .ui-inbox .ui-conn-none{color:var(--ui-muted);font-style:italic}
    .ui-inbox .ui-conn-manage{margin-left:auto;cursor:pointer;color:var(--ui-accent);font-weight:600;font-size:12px;padding:2px 6px;border-radius:6px}
    .ui-inbox .ui-conn-manage:hover{background:var(--ui-accent-soft)}
    .ui-inbox .ui-record{grid-template-columns:96px minmax(0,1fr) auto}
    .ui-inbox .ui-record-label .ui-conn-type{font-size:9.5px}
    .ui-ai{margin:0 0 8px;padding:8px 10px;border:1px solid var(--ui-line);border-radius:8px;background:linear-gradient(180deg,rgba(99,102,241,.05),transparent)}
    .ui-composer.is-note .ui-ai,.ui-composer.is-note .ui-quick{display:none}
    .ui-ai-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:6px}
    .ui-ai-label{font-size:11px;font-weight:600;letter-spacing:.04em;text-transform:uppercase;color:var(--ui-muted)}
    .ui-ai-refresh{font-size:12px;padding:2px 8px;border-radius:6px;color:var(--ui-accent);border:1px solid transparent}
    .ui-ai-refresh:hover{border-color:var(--ui-line);background:rgba(0,0,0,.03)}
    .ui-ai-refresh:disabled{opacity:.5;cursor:wait}
    .ui-ai-list{display:flex;flex-wrap:wrap;gap:6px;max-height:96px;overflow-y:auto;font-size:12px}
    .ui-ai-chip{max-width:100%;text-align:left;font-size:12.5px;line-height:1.35;padding:6px 10px;border:1px solid var(--ui-line);border-radius:8px;background:var(--ui-bg,#fff);transition:border-color .15s,box-shadow .15s}
    .ui-ai-chip:hover{border-color:var(--ui-accent);box-shadow:0 1px 3px rgba(0,0,0,.08)}
    .ui-ai[data-state=loading] .ui-ai-list{opacity:.7}
    /* Header assignee chip */
    .ui-inbox .ui-assignee-chip{display:inline-flex;align-items:center;gap:8px;height:34px;padding:0 12px 0 4px;border:1px solid var(--ui-line);border-radius:999px;background:var(--ui-bg);margin-right:4px;max-width:220px}
    .ui-inbox .ui-assignee-chip-icon{display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:999px;background:#e6f4fb;color:#0e90d9;flex-shrink:0}
    .ui-inbox .ui-assignee-chip.is-empty{border-style:dashed;background:transparent}
    .ui-inbox .ui-assignee-chip.is-empty .ui-assignee-chip-icon{background:#f3f4f6;color:#6b7280}
    .ui-inbox .ui-assignee-chip-copy{display:flex;flex-direction:column;min-width:0;line-height:1.15}
    .ui-inbox .ui-assignee-chip-label{font-size:10px;letter-spacing:.02em;text-transform:uppercase;color:var(--ui-muted)}
    .ui-inbox .ui-assignee-chip-name{font-size:13px;font-weight:600;color:var(--ui-ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .ui-inbox .ui-assignee-chip.is-empty .ui-assignee-chip-name{color:#6b7280;font-weight:500}
    .dark .ui-inbox .ui-assignee-chip-icon{background:rgba(14,144,217,.15)}
    .dark .ui-inbox .ui-assignee-chip.is-empty .ui-assignee-chip-icon{background:rgba(255,255,255,.06);color:#9ca3af}
    @media (max-width:1100px){.ui-inbox .ui-assignee-chip{padding-right:4px}.ui-inbox .ui-assignee-chip-copy{display:none}}
    @media (max-width:767px){.ui-inbox .ui-assignee-chip{display:none}}

    /* Notes tab — unified sidebar tab polish (final overrides) */
    .ui-inbox .ui-side-tablist{display:flex;gap:2px;padding:0 8px;overflow-x:auto;overflow-y:hidden;scrollbar-width:thin;border-bottom:1px solid var(--ui-line)}
    .ui-inbox .ui-side-tab{flex:1 0 auto;display:inline-flex;align-items:center;justify-content:center;gap:5px;height:42px;padding:0 8px;font-size:12.5px;font-weight:600;line-height:1;white-space:nowrap;overflow:visible;text-overflow:clip;color:var(--ui-muted);cursor:pointer;border-bottom:2px solid transparent!important;margin-bottom:-1px;transition:color .15s,border-color .15s,background .15s}
    .ui-inbox .ui-side-tab:hover{color:var(--ui-ink);background:var(--ui-soft)!important}
    .ui-inbox .ui-side-radio:focus-visible + .ui-side-radio, .ui-inbox .ui-side-tab:focus-visible{outline:2px solid var(--ui-accent);outline-offset:-2px}
    .ui-inbox .ui-side-tabs-root:has(.ui-side-radio[data-side-tab=overview]:checked) .ui-side-tab[data-side-tab=overview],
    .ui-inbox .ui-side-tabs-root:has(.ui-side-radio[data-side-tab=crm]:checked) .ui-side-tab[data-side-tab=crm],
    .ui-inbox .ui-side-tabs-root:has(.ui-side-radio[data-side-tab=work]:checked) .ui-side-tab[data-side-tab=work],
    .ui-inbox .ui-side-tabs-root:has(.ui-side-radio[data-side-tab=notes]:checked) .ui-side-tab[data-side-tab=notes]{color:var(--ui-accent);border-bottom-color:var(--ui-accent)!important;background:transparent!important;box-shadow:none!important}
    .ui-inbox .ui-side-tab-count{flex:none;display:inline-flex;align-items:center;justify-content:center;height:16px;min-width:16px;padding:0 5px;border-radius:999px;background:#fef3c7;color:#b45309;font-size:10px;font-weight:700;line-height:1}
    .ui-inbox .ui-side-tabs-root:has(.ui-side-radio[data-side-tab=notes]:checked) .ui-side-tab-count{background:#f59e0b;color:#fff}
    .dark .ui-inbox .ui-side-tab-count{background:rgba(245,158,11,.2);color:#fbbf24}
    .ui-inbox .ui-side-panel[data-side-panel=notes] .ui-notes-panel{padding:16px;border-bottom:0}
    .ui-inbox .ui-notes-panel h2{display:flex;align-items:baseline;justify-content:space-between;gap:8px}
    .ui-inbox .ui-notes-sub{font-size:10.5px;font-weight:500;text-transform:none;letter-spacing:0;color:var(--ui-muted)}
    .ui-inbox .ui-note-form{box-sizing:border-box;width:100%;padding:10px;margin:0 0 14px;border-radius:8px}
    .ui-inbox .ui-note-form:focus-within{border-color:#f59e0b;box-shadow:0 0 0 3px rgba(245,158,11,.15)}
    .ui-inbox .ui-note-form textarea{box-sizing:border-box;width:100%}
    .ui-inbox .ui-note-form-foot{flex-wrap:wrap}
    .ui-inbox .ui-note-form-foot .ui-muted{font-size:11px;min-width:0}
    .ui-inbox .ui-btn-note{margin-left:auto}
    .ui-inbox .ui-note-item{box-sizing:border-box;width:100%}
    .ui-inbox .ui-note-head{flex-wrap:wrap;row-gap:2px}
    .ui-inbox .ui-note-time{white-space:normal;text-align:right}
    /* CRM Records rows: name gets flexible space, actions wrap below instead of overflowing */
    .ui-inbox .ui-record{display:grid;grid-template-columns:auto minmax(0,1fr);column-gap:10px;row-gap:6px;align-items:center}
    .ui-inbox .ui-record-label{min-width:0}
    .ui-inbox .ui-record-value{flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .ui-inbox .ui-record-actions{grid-column:1 / -1;display:flex;flex-wrap:wrap;justify-content:flex-end;gap:6px;margin-left:0;min-width:0}
    .ui-inbox .ui-record-actions .ui-link-btn{margin-left:0}
    /* Full linked-record names everywhere (connbar, CRM Records, Related) */
    .ui-inbox .ui-conn-chip{max-width:min(360px,100%);min-width:0}
    .ui-inbox .ui-conn-chip .ui-conn-type,.ui-inbox .ui-related-row .ui-conn-type,.ui-inbox .ui-record-label .ui-conn-type{flex:0 0 auto;white-space:nowrap}
    .ui-inbox .ui-conn-name{flex:1 1 auto}
    .ui-inbox .ui-related-row{flex-wrap:wrap;row-gap:4px}
    .ui-inbox .ui-related-row a{flex:1 1 120px}
    .ui-inbox .ui-related-row .ui-link-btn{margin-left:auto}
    .ui-inbox .ui-related-row .ui-record-label{flex:0 0 100%;font-size:11px}
    /* WhatsApp-style thread wallpaper: original tiled doodle SVG (not WhatsApp's asset); WhatsApp threads only */
    .ui-inbox .ui-thread-whatsapp .ui-history{background-color:#efeae2;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='160' height='160' viewBox='0 0 160 160'%3E%3Cg fill='none' stroke='%234a4a3a' stroke-opacity='.14' stroke-width='1.4' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M14 18h20a4 4 0 0 1 4 4v10a4 4 0 0 1-4 4H24l-6 5v-5h-4a4 4 0 0 1-4-4V22a4 4 0 0 1 4-4z'/%3E%3Cpath d='M78 14l3 6 7 1-5 5 1 7-6-3-6 3 1-7-5-5 7-1z'/%3E%3Cpath d='M130 30c-4-6-14-4-12 4 1 5 12 11 12 11s11-6 12-11c2-8-8-10-12-4z'/%3E%3Ccircle cx='30' cy='84' r='9'/%3E%3Cpath d='M26 82h.01M34 82h.01M26 88c2 2 6 2 8 0'/%3E%3Cpath d='M72 74h22v16H72zM72 74l11 9 11-9'/%3E%3Cpath d='M120 76v18M114 82l6-6 6 6M112 100h16'/%3E%3Cpath d='M18 132a10 10 0 0 1 20 0M28 122v-6M22 140h12'/%3E%3Cpath d='M70 128l8-8 8 8-8 8zM96 118c4 0 6 4 4 7'/%3E%3Ccircle cx='128' cy='130' r='7'/%3E%3Cpath d='M128 125v5l3 2'/%3E%3Cpath d='M50 50l4 4M54 50l-4 4M104 48a3 3 0 1 0 0 .1M150 60l3 3M40 108h4M146 110a2 2 0 1 0 0 .1'/%3E%3C/g%3E%3C/svg%3E");background-size:160px 160px;background-repeat:repeat;background-position:0 0}
    .dark .ui-inbox .ui-thread-whatsapp .ui-history,.ui-inbox.dark .ui-thread-whatsapp .ui-history{background-color:#0b141a;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='160' height='160' viewBox='0 0 160 160'%3E%3Cg fill='none' stroke='%23ffffff' stroke-opacity='.05' stroke-width='1.4' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M14 18h20a4 4 0 0 1 4 4v10a4 4 0 0 1-4 4H24l-6 5v-5h-4a4 4 0 0 1-4-4V22a4 4 0 0 1 4-4z'/%3E%3Cpath d='M78 14l3 6 7 1-5 5 1 7-6-3-6 3 1-7-5-5 7-1z'/%3E%3Cpath d='M130 30c-4-6-14-4-12 4 1 5 12 11 12 11s11-6 12-11c2-8-8-10-12-4z'/%3E%3Ccircle cx='30' cy='84' r='9'/%3E%3Cpath d='M26 82h.01M34 82h.01M26 88c2 2 6 2 8 0'/%3E%3Cpath d='M72 74h22v16H72zM72 74l11 9 11-9'/%3E%3Cpath d='M120 76v18M114 82l6-6 6 6M112 100h16'/%3E%3Cpath d='M18 132a10 10 0 0 1 20 0M28 122v-6M22 140h12'/%3E%3Cpath d='M70 128l8-8 8 8-8 8zM96 118c4 0 6 4 4 7'/%3E%3Ccircle cx='128' cy='130' r='7'/%3E%3Cpath d='M128 125v5l3 2'/%3E%3Cpath d='M50 50l4 4M54 50l-4 4M104 48a3 3 0 1 0 0 .1M150 60l3 3M40 108h4M146 110a2 2 0 1 0 0 .1'/%3E%3C/g%3E%3C/svg%3E")}
    /* Compact WhatsApp-style composer */
    .ui-inbox .ui-composer{margin:0 12px 10px;padding:0 10px 8px;border-radius:10px}
    .ui-inbox .ui-composer-tabs{display:flex;align-items:center;gap:14px;margin:0 -10px 6px;padding:0 10px;min-width:0}
    .ui-inbox .ui-composer-tab{padding:6px 0;font-size:12.5px;flex:0 0 auto}
    .ui-inbox .ui-composer-hint{margin-left:auto;min-width:0;font-size:11px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .ui-inbox .ui-suggest-row{display:flex;align-items:center;gap:8px;margin:0 0 6px;overflow-x:auto;overflow-y:hidden;scrollbar-width:thin;white-space:nowrap}
    .ui-inbox .ui-suggest-row .ui-quick{display:flex;flex-wrap:nowrap;flex:0 0 auto;gap:4px;padding:0;border:0}
    .ui-inbox .ui-suggest-row .ui-chip{padding:2px 8px;font-size:11.5px;white-space:nowrap;flex:0 0 auto}
    .ui-inbox .ui-ai{display:flex;align-items:center;gap:6px;flex:0 0 auto;margin:0;padding:0 0 0 8px;border:0;border-left:1px solid var(--ui-line);border-radius:0;background:none}
    .ui-inbox .ui-ai-head{display:flex;align-items:center;gap:2px;margin:0;flex:0 0 auto}
    .ui-inbox .ui-ai-label{font-size:10.5px}
    .ui-inbox .ui-ai-refresh{padding:0 5px;font-size:13px;line-height:20px}
    .ui-inbox .ui-ai-list{display:flex;flex-wrap:nowrap;gap:4px;max-height:none;overflow:visible;font-size:11.5px;white-space:nowrap}
    .ui-inbox .ui-ai-chip{flex:0 0 auto;max-width:260px;padding:2px 9px;font-size:11.5px;line-height:1.4;border-radius:999px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .ui-inbox .ui-composer.is-note .ui-suggest-row{display:none}
    .ui-inbox #message-form{display:flex;align-items:flex-end;gap:8px;margin:0}
    .ui-inbox .ui-composer textarea#message-input{flex:1;min-width:0;min-height:0;height:auto;resize:none;overflow-y:hidden;line-height:20px;padding:8px 12px!important;border-radius:18px;font-size:14px;box-sizing:border-box}
    .ui-inbox #send-button{flex:0 0 auto;height:36px;padding:0 16px;border-radius:18px;margin:0}
    /* Krayin "Powered by" footer on the inbox: start it after the fixed sidebar so it no longer
       runs under the sidebar's collapse toggle (which left an odd bordered strip bottom-left). */
    @media (min-width:1024px){
        body:has(.ui-inbox) #app .fixed.bottom-0.left-0.right-0:has(> .border-t.py-5.text-center){left:200px;transition:left .08s}
        body:has(.ui-inbox) #app .sidebar-collapsed .fixed.bottom-0.left-0.right-0:has(> .border-t.py-5.text-center){left:70px}
    }
</style>
@endPushOnce
