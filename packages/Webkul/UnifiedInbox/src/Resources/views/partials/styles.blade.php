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
    .ui-thread-whatsapp .ui-history{background:#efeae2 radial-gradient(#0000000a 1px,transparent 1px) 0 0/20px 20px;gap:6px}
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
    .dark .ui-thread-whatsapp .ui-history{background:#0b141a radial-gradient(#ffffff08 1px,transparent 1px) 0 0/20px 20px}
    .dark .ui-thread-whatsapp .ui-message:not(.ui-message-outbound):not(.ui-message-outgoing){background:#202c33;color:#e9edef}
    .dark .ui-thread-whatsapp .ui-message-outbound,.dark .ui-thread-whatsapp .ui-message-outgoing{background:#005c4b;color:#e9edef}
    .dark .ui-thread-whatsapp .ui-message-meta{color:#ffffff99}
    .dark .ui-thread-whatsapp .ui-composer textarea{background:#2a3942;color:#e9edef}
    .dark .ui-thread-messenger .ui-history,.dark .ui-thread-instagram .ui-history{background:#000}
    .dark .ui-thread-messenger .ui-message:not(.ui-message-outbound):not(.ui-message-outgoing){background:#303030;color:#e4e6eb}
    .dark .ui-thread-instagram .ui-message:not(.ui-message-outbound):not(.ui-message-outgoing){background:#262626;color:#f5f5f5}
    .dark .ui-thread-messenger .ui-composer textarea,.dark .ui-thread-instagram .ui-composer textarea{background:#242526;border-color:#363636;color:#e4e6eb}
</style>
@endPushOnce
